<?php

declare(strict_types=1);

namespace SmsTelnyx;

/**
 * Admin-session check for the mass-notice page (same mechanism as the UCRM Plugin SDK).
 */
final class AdminAuth
{
    public static function currentUser(): ?array
    {
        $base = Config::ucrmUrl();
        if ($base === '') {
            return null;
        }
        $clean = fn (string $n) => preg_replace('~[^a-zA-Z0-9-]~', '', is_string($_COOKIE[$n] ?? null) ? $_COOKIE[$n] : '');
        $cookie = sprintf('PHPSESSID=%s; nms-crm-php-session-id=%s; nms-session=%s',
            $clean('PHPSESSID'), $clean('nms-crm-php-session-id'), $clean('nms-session'));
        try {
            $u = Http::request('GET', $base . 'current-user', ['Cookie: ' . $cookie], null, ! Http::isSecureLocalhost($base));
        } catch (HttpException $e) {
            if (in_array($e->status, [401, 403], true)) {
                return null;
            }
            throw $e;
        }
        return $u ?: null;
    }

    /** Admin (not client) with edit rights on clients. */
    public static function allowed(?array $u): bool
    {
        return $u !== null && empty($u['isClient']) && (($u['permissions']['clients/clients'] ?? '') === 'edit');
    }

    public static function csrf(array $u): string
    {
        return hash_hmac('sha256', ($u['userId'] ?? '') . '|' . date('Y-m-d'), Config::str('pluginAppKey', 'x'));
    }

    public static function checkCsrf(array $u, string $token): bool
    {
        $prev = hash_hmac('sha256', ($u['userId'] ?? '') . '|' . date('Y-m-d', time() - 86400), Config::str('pluginAppKey', 'x'));
        return hash_equals(self::csrf($u), $token) || hash_equals($prev, $token);
    }
}

/**
 * Resolves recipients for a mass notice.
 * Audience: {type: all|pop|tag|overdue, pops[], tags[], overdueDays, includeSuspended}
 */
final class Audience
{
    public const ACTIVE = 1;
    public const SUSPENDED = 3;

    public static function options(): array
    {
        $tags = array_map(fn ($t) => ['id' => $t['id'], 'name' => $t['name']], Crm::get('client-tags'));
        $pops = [];
        if (Crm::nmsAvailable()) {
            try {
                foreach (Crm::nms('sites', ['type' => 'site']) as $s) {
                    if (($s['identification']['type'] ?? '') === 'site') {
                        $pops[] = (string) $s['identification']['name'];
                    }
                }
                sort($pops, SORT_NATURAL | SORT_FLAG_CASE);
            } catch (\Throwable $e) {
                Log::warn('UISP site list failed: ' . $e->getMessage());
            }
        }
        return ['tags' => $tags, 'pops' => array_values(array_unique($pops)), 'nms' => Crm::nmsAvailable()];
    }

    /**
     * @return array{recipients: list<array>, skipped: list<array>}
     */
    public static function resolve(array $a): array
    {
        $type = (string) ($a['type'] ?? '');
        $clients = [];
        foreach (Crm::all('clients') as $c) {
            if (empty($c['isArchived']) && empty($c['isLead'])) {
                $clients[(int) $c['id']] = $c;
            }
        }
        $statuses = [self::ACTIVE];
        if (! empty($a['includeSuspended'])) {
            $statuses[] = self::SUSPENDED;
        }
        $services = Crm::all('clients/services', ['statuses' => $statuses]);
        $withService = [];
        foreach ($services as $s) {
            $withService[(int) $s['clientId']][] = $s;
        }

        $extra = [];     // per-client template data: site.pop, overdue.*
        $purpose = 'contact';
        switch ($type) {
            case 'all':
                $ids = array_keys($withService);
                break;

            case 'tag':
                $want = array_map('intval', (array) ($a['tags'] ?? []));
                $ids = [];
                foreach ($clients as $id => $c) {
                    foreach ($c['tags'] ?? [] as $t) {
                        if (in_array((int) $t['id'], $want, true)) {
                            $ids[] = $id;
                            break;
                        }
                    }
                }
                if (! empty($a['activeOnly'])) {
                    $ids = array_values(array_filter($ids, fn ($id) => isset($withService[$id])));
                }
                break;

            case 'pop':
                $want = array_map('strtolower', (array) ($a['pops'] ?? []));
                $sitePop = self::sitePopMap();
                $ids = [];
                foreach ($withService as $cid => $svcs) {
                    $pop = null;
                    foreach ($svcs as $s) {
                        $sid = (string) ($s['unmsClientSiteId'] ?? '');
                        if ($sid !== '' && isset($sitePop[$sid])) {
                            $pop = $sitePop[$sid];
                            break;
                        }
                    }
                    if ($pop === null) {           // fallback: CRM tag named like the POP
                        foreach ($clients[$cid]['tags'] ?? [] as $t) {
                            if (in_array(strtolower($t['name']), $want, true)) {
                                $pop = strtolower($t['name']);
                                break;
                            }
                        }
                    }
                    if ($pop !== null && in_array(strtolower($pop), $want, true)) {
                        $ids[] = $cid;
                        $extra[$cid]['site'] = ['pop' => strtoupper($pop)];
                    }
                }
                break;

            case 'overdue':
                // Qualify on the oldest unpaid invoice; report everything currently past due.
                $days = max(0, (int) ($a['overdueDays'] ?? 1));
                $today = strtotime('today');
                $cut = $today - $days * 86400;
                $agg = [];
                foreach (Crm::all('invoices', ['statuses' => [1, 2]]) as $inv) {
                    $due = strtotime(substr((string) ($inv['dueDate'] ?? $inv['createdDate'] ?? ''), 0, 10));
                    if (! $due || $due >= $today) {
                        continue;             // not yet past due
                    }
                    $cid = (int) $inv['clientId'];
                    $agg[$cid]['count'] = ($agg[$cid]['count'] ?? 0) + 1;
                    $agg[$cid]['amount'] = ($agg[$cid]['amount'] ?? 0) + (float) ($inv['amountToPay'] ?? 0);
                    $agg[$cid]['oldest'] = min($agg[$cid]['oldest'] ?? PHP_INT_MAX, $due);
                }
                $ids = [];
                foreach ($agg as $cid => $g) {
                    if ($g['oldest'] > $cut) {
                        continue;
                    }
                    $ids[] = $cid;
                    $extra[$cid]['overdue'] = ['count' => $g['count'], 'amount' => number_format($g['amount'], 2),
                        'days' => (int) floor(($today - $g['oldest']) / 86400), 'oldestDueDate' => date('M j, Y', $g['oldest'])];
                }
                $purpose = 'billing';
                break;

            default:
                throw new \InvalidArgumentException('Unknown audience type');
        }

        $recipients = [];
        $skipped = [];
        $seen = [];
        $optouts = OptOut::all();
        foreach (array_unique($ids) as $cid) {
            $c = $clients[$cid] ?? null;
            if (! $c) {
                continue;             // archived / lead
            }
            $name = Template::clientName($c);
            $phone = Phone::forClient($c, $purpose);
            if (! $phone) {
                $skipped[] = ['clientId' => $cid, 'name' => $name, 'reason' => 'no valid phone'];
                continue;
            }
            if (isset($optouts[$phone])) {
                $skipped[] = ['clientId' => $cid, 'name' => $name, 'phone' => $phone, 'reason' => 'opted out'];
                continue;
            }
            if (isset($seen[$phone])) {
                $skipped[] = ['clientId' => $cid, 'name' => $name, 'phone' => $phone, 'reason' => 'duplicate of ' . $seen[$phone]];
                continue;
            }
            $seen[$phone] = $name;
            $recipients[] = ['clientId' => $cid, 'name' => $name, 'phone' => $phone, 'client' => $c, 'extra' => $extra[$cid] ?? []];
        }
        usort($recipients, fn ($x, $y) => strcasecmp($x['name'], $y['name']));
        return ['recipients' => $recipients, 'skipped' => $skipped];
    }

    /** UISP endpoint site id → parent POP name. */
    private static function sitePopMap(): array
    {
        if (! Crm::nmsAvailable()) {
            return [];
        }
        $map = [];
        try {
            foreach (Crm::nms('sites') as $s) {
                $idn = $s['identification'] ?? [];
                if (($idn['type'] ?? '') === 'endpoint' && ! empty($idn['parent']['name'])) {
                    $map[(string) $idn['id']] = (string) $idn['parent']['name'];
                }
            }
        } catch (\Throwable $e) {
            Log::warn('UISP site map failed, POP targeting falls back to tags: ' . $e->getMessage());
        }
        return $map;
    }
}

/**
 * Mass notices: preview → create job → send in batches (driven by the admin page) → history.
 */
final class MassNotice
{
    public const BATCH = 10;

    public static function renderFor(array $r, string $message): string
    {
        return Telnyx::compose(Template::render($message, ['client' => $r['client']] + $r['extra']));
    }

    public static function preview(array $in): array
    {
        $message = trim((string) ($in['message'] ?? ''));
        if ($message === '') {
            throw new \InvalidArgumentException('Message is empty');
        }
        $res = Audience::resolve($in['audience'] ?? []);
        $rows = [];
        $segments = 0;
        foreach ($res['recipients'] as $r) {
            $text = self::renderFor($r, $message);
            $seg = Telnyx::segments($text);
            $segments += $seg;
            $rows[] = ['clientId' => $r['clientId'], 'name' => $r['name'], 'phone' => Phone::pretty($r['phone']), 'text' => $text, 'segments' => $seg];
        }
        $cost = (float) Config::get('costPerSegment', 0.007);
        return ['count' => count($rows), 'segments' => $segments, 'cost' => round($segments * $cost, 2), 'rows' => $rows,
            'skipped' => $res['skipped'], 'quiet' => self::quietNow()];
    }

    public static function create(array $in, array $user): array
    {
        $message = trim((string) ($in['message'] ?? ''));
        $urgent = ! empty($in['urgent']);
        if (self::quietNow() && ! $urgent) {
            throw new \InvalidArgumentException('Quiet hours (9 PM–8 AM): mark the notice as urgent to send now, or wait until morning.');
        }
        $res = Audience::resolve($in['audience'] ?? []);
        $id = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
        $queue = [];
        foreach ($res['recipients'] as $r) {
            $queue[] = ['clientId' => $r['clientId'], 'name' => $r['name'], 'to' => $r['phone'], 'text' => self::renderFor($r, $message),
                'status' => 'pending'];
        }
        $job = ['id' => $id, 'created' => date('c'), 'by' => $user['username'] ?? '?', 'title' => mb_substr((string) ($in['title'] ?? ''), 0, 80),
            'audience' => $in['audience'] ?? [], 'message' => $message, 'urgent' => $urgent, 'queue' => $queue,
            'skipped' => $res['skipped'], 'state' => 'sending'];
        Store::update('job-' . $id, fn () => $job);
        self::index($job);
        Log::info(sprintf('Mass notice %s created by %s: %d recipients, %d skipped', $id, $job['by'], count($queue), count($res['skipped'])));
        return self::summary($job);
    }

    /** Sends the next batch; safe to call concurrently (job file is locked while sending). */
    public static function batch(string $id): array
    {
        $id = preg_replace('~[^0-9a-f-]~', '', $id) ?? '';
        $job = Store::update('job-' . $id, function (array $job) {
            if (($job['state'] ?? '') !== 'sending') {
                return $job;
            }
            $n = 0;
            foreach ($job['queue'] as &$q) {
                if ($q['status'] !== 'pending') {
                    continue;
                }
                if ($n++ >= self::BATCH) {
                    break;
                }
                if (OptOut::isOptedOut($q['to'])) {
                    $q['status'] = 'skipped';
                    $q['error'] = 'opted out';
                    continue;
                }
                $r = Telnyx::send($q['to'], $q['text']);
                $q['status'] = $r['ok'] ? 'sent' : 'failed';
                $q['msgId'] = $r['id'];
                $q['error'] = $r['error'];
                usleep(250000);
            }
            unset($q);
            if (! array_filter($job['queue'], fn ($q) => $q['status'] === 'pending')) {
                $job['state'] = 'done';
                $job['finished'] = date('c');
            }
            return $job;
        });
        if (! $job) {
            throw new \InvalidArgumentException('Unknown job');
        }
        self::index($job);
        if (($job['state'] ?? '') === 'done' && empty($job['logged'])) {
            $s = self::summary($job);
            Log::info(sprintf('Mass notice %s finished: %d sent, %d failed, %d skipped', $id, $s['sent'], $s['failed'], $s['skippedCount']));
            Store::update('job-' . $id, fn ($j) => $j + ['logged' => true]);
        }
        return self::summary($job);
    }

    public static function cancel(string $id): array
    {
        $id = preg_replace('~[^0-9a-f-]~', '', $id) ?? '';
        $job = Store::update('job-' . $id, function (array $job) {
            if (($job['state'] ?? '') === 'sending') {
                $job['state'] = 'cancelled';
                foreach ($job['queue'] as &$q) {
                    if ($q['status'] === 'pending') {
                        $q['status'] = 'cancelled';
                    }
                }
                unset($q);
            }
            return $job;
        });
        self::index($job);
        Log::info("Mass notice {$id} cancelled");
        return self::summary($job);
    }

    public static function detail(string $id): array
    {
        $id = preg_replace('~[^0-9a-f-]~', '', $id) ?? '';
        $job = Store::read('job-' . $id);
        if (! $job) {
            throw new \InvalidArgumentException('Unknown job');
        }
        $dlr = Store::read('dlr');
        foreach ($job['queue'] as &$q) {
            if (! empty($q['msgId']) && isset($dlr[$q['msgId']])) {
                $q['delivery'] = $dlr[$q['msgId']]['status'];
                $q['deliveryError'] = $dlr[$q['msgId']]['error'];
            }
            $q['phone'] = Phone::pretty($q['to']);
        }
        unset($q);
        return self::summary($job) + ['queue' => $job['queue'], 'skipped' => $job['skipped'], 'message' => $job['message']];
    }

    public static function history(): array
    {
        $idx = Store::read('jobs');
        usort($idx, fn ($a, $b) => strcmp($b['id'], $a['id']));
        return array_slice($idx, 0, 50);
    }

    public static function quietNow(): bool
    {
        if (! Config::bool('quietHours')) {
            return false;
        }
        $h = (int) date('G');
        return $h >= 21 || $h < 8;
    }

    private static function summary(array $job): array
    {
        $c = ['sent' => 0, 'failed' => 0, 'pending' => 0, 'skipped' => 0, 'cancelled' => 0];
        foreach ($job['queue'] ?? [] as $q) {
            $c[$q['status']] = ($c[$q['status']] ?? 0) + 1;
        }
        return ['id' => $job['id'] ?? '', 'title' => $job['title'] ?? '', 'created' => $job['created'] ?? '', 'by' => $job['by'] ?? '',
            'state' => $job['state'] ?? '', 'total' => count($job['queue'] ?? []), 'sent' => $c['sent'], 'failed' => $c['failed'],
            'pending' => $c['pending'], 'cancelled' => $c['cancelled'], 'skippedCount' => count($job['skipped'] ?? []) + $c['skipped'],
            'audience' => self::describe($job['audience'] ?? [])];
    }

    private static function index(array $job): void
    {
        $s = self::summary($job);
        Store::update('jobs', function (array $idx) use ($s) {
            $idx = array_values(array_filter($idx, fn ($j) => $j['id'] !== $s['id']));
            $idx[] = $s;
            return array_slice($idx, -200);
        });
    }

    public static function describe(array $a): string
    {
        return match ($a['type'] ?? '') {
            'all' => 'All active clients' . (! empty($a['includeSuspended']) ? ' + suspended' : ''),
            'pop' => 'POP: ' . implode(', ', array_map('strtoupper', (array) ($a['pops'] ?? []))),
            'tag' => 'Tags: ' . implode(', ', (array) ($a['tagNames'] ?? $a['tags'] ?? [])),
            'overdue' => 'Overdue ' . (int) ($a['overdueDays'] ?? 1) . '+ days',
            default => '?',
        };
    }
}
