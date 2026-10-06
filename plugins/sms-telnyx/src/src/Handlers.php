<?php

declare(strict_types=1);

namespace SmsTelnyx;

/**
 * CRM webhook events → templated SMS (port of the Twilio plugin's behavior).
 */
final class EventNotifier
{
    public static function handle(array $event): array
    {
        $uuid = (string) ($event['uuid'] ?? '');
        $name = (string) ($event['eventName'] ?? '');
        if (($event['changeType'] ?? '') === 'test') {
            Log::info('Webhook test successful.');
            return ['ok' => true, 'test' => true];
        }
        // Confirms the event really came from this CRM (a forged POST won't exist here).
        Crm::get('webhook-events/' . rawurlencode($uuid));

        $key = 'event_' . str_replace('.', '_', $name);
        $template = Config::str($key);
        if ($template === '') {
            Log::debug("No text configured for {$name}");
            return ['ok' => true, 'skipped' => 'no template'];
        }

        $entity = (string) ($event['entity'] ?? '');
        $id = (int) ($event['entityId'] ?? 0);
        $data = [];
        $clientId = null;
        switch ($entity) {
            case 'client':
                $clientId = $id;
                break;
            case 'invoice':
                $data['invoice'] = Crm::get('invoices/' . $id);
                $clientId = $data['invoice']['clientId'] ?? null;
                break;
            case 'payment':
                $data['payment'] = Crm::get('payments/' . $id);
                $clientId = $data['payment']['clientId'] ?? null;
                break;
            case 'service':
                $data['service'] = Crm::get('clients/services/' . $id);
                $clientId = $data['service']['clientId'] ?? null;
                if (($data['service']['suspensionReasonId'] ?? null) !== null) {
                    $data['service']['stopReason'] = Crm::get('service-suspension-reasons/' . $data['service']['suspensionReasonId'])['name'] ?? '';
                }
                break;
        }
        if (! $clientId) {
            Log::warn("{$name}: no client for {$entity} {$id}");
            return ['ok' => false, 'skipped' => 'no client'];
        }
        $data['client'] = Crm::get('clients/' . (int) $clientId);
        if (isset($event['extraData']['message'])) {
            $data['client']['message'] = (string) $event['extraData']['message'];
        }

        $purpose = in_array($entity, ['invoice', 'payment'], true) || $name === 'service.suspend' ? 'billing' : 'contact';
        $to = Phone::forClient($data['client'], $purpose);
        $who = Template::clientName($data['client']);
        if (! $to) {
            Log::warn("{$name}: {$who} has no usable phone number");
            return ['ok' => false, 'skipped' => 'no phone'];
        }
        if (OptOut::isOptedOut($to)) {
            Log::info("{$name}: {$who} {$to} opted out — not sent");
            return ['ok' => true, 'skipped' => 'opted out'];
        }
        $text = Telnyx::compose(Template::render($template, $data));
        $r = Telnyx::send($to, $text);
        Telnyx::record(['kind' => 'event', 'event' => $name, 'clientId' => $clientId, 'to' => $to, 'id' => $r['id'],
            'status' => $r['status'], 'error' => $r['error']]);
        Log::info(sprintf('%s → %s %s: %s', $name, $who, $to, $r['ok'] ? 'sent' : 'FAILED ' . $r['error']));
        return $r;
    }
}

/**
 * Telnyx webhooks: inbound messages (STOP/START/HELP/replies) and delivery receipts.
 */
final class Inbound
{
    private const STOP = ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT', 'REVOKE', 'OPTOUT', 'OPT-OUT'];
    private const START = ['START', 'UNSTOP', 'SUBSCRIBE', 'YES', 'OPTIN', 'OPT-IN'];
    private const HELP = ['HELP', 'INFO'];

    public static function handle(array $payload): array
    {
        $type = (string) ($payload['data']['event_type'] ?? '');
        $p = $payload['data']['payload'] ?? [];
        if ($type === 'message.finalized' || $type === 'message.sent') {
            return self::receipt($p);
        }
        if ($type !== 'message.received') {
            Log::debug("Telnyx event ignored: {$type}");
            return ['ok' => true, 'ignored' => $type];
        }
        $from = Phone::normalize((string) ($p['from']['phone_number'] ?? ''));
        $text = trim((string) ($p['text'] ?? ''));
        if (! $from) {
            return ['ok' => false, 'error' => 'no sender'];
        }
        $word = strtoupper(preg_replace('~[^A-Za-z-]~', '', $text) ?? '');

        if (in_array($word, self::STOP, true)) {
            OptOut::add($from, 'keyword ' . $word);
            if (Config::bool('keywordConfirmations')) {
                self::reply($from, 'You are unsubscribed and will receive no further messages. Reply START to resubscribe.');
            }
            return ['ok' => true, 'action' => 'optout'];
        }
        if (in_array($word, self::START, true)) {
            OptOut::remove($from, 'keyword ' . $word);
            if (Config::bool('keywordConfirmations')) {
                self::reply($from, 'You are resubscribed to text notices. Reply STOP to opt out.');
            }
            return ['ok' => true, 'action' => 'optin'];
        }
        if (in_array($word, self::HELP, true)) {
            if (($help = Config::str('helpReply')) !== '') {
                self::reply($from, $help);
            }
            return ['ok' => true, 'action' => 'help'];
        }
        return self::forward($from, $text, count($p['media'] ?? []));
    }

    private static function forward(string $from, string $text, int $media): array
    {
        $to = Phone::normalize(Config::str('forwardRepliesTo'));
        $client = Phonebook::lookup($from);
        $who = $client ? Template::clientName($client) . ' (#' . $client['id'] . ')' : 'unknown';
        Log::info("Reply from {$who} " . Phone::pretty($from) . ': ' . mb_substr($text, 0, 200));
        Store::update('replies', function (array $d) use ($from, $text, $client) {
            $d[] = ['at' => date('c'), 'from' => $from, 'clientId' => $client['id'] ?? null, 'text' => $text];
            return array_slice($d, -1000);
        });
        if (! $to || $to === $from) {
            return ['ok' => true, 'action' => 'logged'];
        }
        $body = "↩ SMS reply from {$who} " . Phone::pretty($from) . ":\n" . ($text !== '' ? $text : '(no text)')
            . ($media ? "\n[+{$media} attachment(s) — see Telnyx portal]" : '');
        $r = Telnyx::send($to, mb_substr($body, 0, 1500));
        return ['ok' => $r['ok'], 'action' => 'forwarded'];
    }

    private static function reply(string $to, string $text): void
    {
        $r = Telnyx::send($to, $text);
        Telnyx::record(['kind' => 'auto', 'to' => $to, 'id' => $r['id'], 'status' => $r['status'], 'error' => $r['error']]);
    }

    private static function receipt(array $p): array
    {
        $id = (string) ($p['id'] ?? '');
        $status = (string) ($p['to'][0]['status'] ?? '');
        $err = $p['errors'][0] ?? null;
        if ($id === '' || $status === '') {
            return ['ok' => true];
        }
        Store::update('dlr', function (array $d) use ($id, $status, $err) {
            $d[$id] = ['status' => $status, 'error' => $err ? (($err['code'] ?? '') . ' ' . ($err['title'] ?? '')) : null, 'at' => date('c')];
            return array_slice($d, -5000, null, true);
        });
        if (in_array($status, ['delivery_failed', 'sending_failed'], true)) {
            Log::warn("Delivery failed for message {$id}: " . json_encode($err));
        }
        return ['ok' => true, 'dlr' => $status];
    }
}

/**
 * Phone → client lookup, cached for an hour.
 */
final class Phonebook
{
    public static function lookup(string $e164): ?array
    {
        $cache = Store::read('phonebook');
        if (($cache['built'] ?? 0) < time() - 3600) {
            $map = [];
            foreach (Crm::all('clients') as $c) {
                foreach ($c['contacts'] ?? [] as $ct) {
                    $n = Phone::normalize($ct['phone'] ?? null);
                    if ($n && ! isset($map[$n])) {
                        $map[$n] = ['id' => $c['id'], 'companyName' => $c['companyName'] ?? '', 'firstName' => $c['firstName'] ?? '',
                            'lastName' => $c['lastName'] ?? ''];
                    }
                }
            }
            $cache = ['built' => time(), 'map' => $map];
            Store::update('phonebook', fn () => $cache);
        }
        return $cache['map'][$e164] ?? null;
    }
}
