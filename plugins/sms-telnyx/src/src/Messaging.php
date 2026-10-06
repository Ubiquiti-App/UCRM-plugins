<?php

declare(strict_types=1);

namespace SmsTelnyx;

/**
 * Phone number normalization and contact selection.
 */
final class Phone
{
    /** Returns E.164 (+1XXXXXXXXXX for US/Canada) or null if it can't be made valid. */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        $plus = str_starts_with(ltrim($raw, "\u{202A}\u{202B}\u{202C}\u{202D}\u{202E}\u{200E}\u{200F} "), '+');
        $digits = preg_replace('~\D~', '', $raw) ?? '';
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }
        if ($plus && strlen($digits) >= 8 && strlen($digits) <= 15) {
            return '+' . $digits;
        }
        return null;
    }

    public static function pretty(string $e164): string
    {
        if (preg_match('~^\+1(\d{3})(\d{3})(\d{4})$~', $e164, $m)) {
            return sprintf('(%s) %s-%s', $m[1], $m[2], $m[3]);
        }
        return $e164;
    }

    /**
     * Picks the client's SMS number. $purpose 'billing' prefers billing contacts, otherwise general contacts;
     * falls back to any contact with a phone.
     */
    public static function forClient(array $client, string $purpose = 'contact'): ?string
    {
        $contacts = $client['contacts'] ?? [];
        $flag = $purpose === 'billing' ? 'isBilling' : 'isContact';
        foreach ([true, false] as $strict) {
            foreach ($contacts as $c) {
                if (($c['phone'] ?? '') === '') {
                    continue;
                }
                if ($strict && empty($c[$flag])) {
                    continue;
                }
                $n = self::normalize($c['phone']);
                if ($n) {
                    return $n;
                }
            }
        }
        return null;
    }
}

final class OptOut
{
    public static function isOptedOut(string $e164): bool
    {
        return isset(Store::read('optouts')[$e164]);
    }

    public static function add(string $e164, string $source): void
    {
        Store::update('optouts', function (array $d) use ($e164, $source) {
            $d[$e164] = ['at' => date('c'), 'source' => $source];
            return $d;
        });
        Log::info("Opt-out recorded for {$e164} ({$source})");
    }

    public static function remove(string $e164, string $source): void
    {
        Store::update('optouts', function (array $d) use ($e164) {
            unset($d[$e164]);
            return $d;
        });
        Log::info("Opt-in (START) recorded for {$e164} ({$source})");
    }

    public static function all(): array
    {
        return Store::read('optouts');
    }
}

/**
 * Telnyx Messaging API v2.
 */
final class Telnyx
{
    private const API = 'https://api.telnyx.com/v2/';
    public const ERR_OPTED_OUT = ['40300', '40301'];

    /**
     * @return array{ok:bool,id:?string,status:string,error:?string,optedOut:bool}
     */
    public static function send(string $to, string $text): array
    {
        $body = ['from' => Config::str('telnyxFromNumber'), 'to' => $to, 'text' => $text];
        if (($p = Config::str('telnyxMessagingProfileId')) !== '') {
            $body['messaging_profile_id'] = $p;
        }
        if (($hook = Config::str('pluginPublicUrl')) !== '') {
            $body['webhook_url'] = $hook . (str_contains($hook, '?') ? '&' : '?') . 'hook=telnyx';
        }
        try {
            $r = Http::request('POST', self::API . 'messages', ['Authorization: Bearer ' . Config::str('telnyxApiKey')], $body);
            $d = $r['data'] ?? [];
            $status = (string) ($d['to'][0]['status'] ?? 'queued');
            Log::debug("Telnyx accepted {$to}: id " . ($d['id'] ?? '?') . " status {$status}");
            return ['ok' => true, 'id' => $d['id'] ?? null, 'status' => $status, 'error' => null, 'optedOut' => false];
        } catch (HttpException $e) {
            $err = $e->body['errors'][0] ?? [];
            $code = (string) ($err['code'] ?? $e->status);
            $msg = trim(($err['title'] ?? '') . ': ' . ($err['detail'] ?? $e->getMessage()), ': ');
            $opted = in_array($code, self::ERR_OPTED_OUT, true);
            if ($opted) {
                OptOut::add($to, 'telnyx-block');
            }
            Log::warn("Telnyx send to {$to} failed ({$code}): {$msg}");
            return ['ok' => false, 'id' => null, 'status' => 'failed', 'error' => "{$code} {$msg}", 'optedOut' => $opted];
        }
    }

    /**
     * Verifies a Telnyx webhook (Ed25519 over "timestamp|rawBody"), rejecting stale timestamps.
     */
    public static function verify(string $rawBody, string $signatureB64, string $timestamp, ?int $now = null): bool
    {
        $key = Config::str('telnyxPublicKey');
        if ($key === '' || $signatureB64 === '' || $timestamp === '' || ! function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }
        if (abs(($now ?? time()) - (int) $timestamp) > 300) {
            return false;
        }
        $sig = base64_decode($signatureB64, true);
        $pk = base64_decode($key, true);
        if ($sig === false || $pk === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES || strlen($pk) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }
        return sodium_crypto_sign_verify_detached($sig, $timestamp . '|' . $rawBody, $pk);
    }

    /** Applies prefix + footer. */
    public static function compose(string $text): string
    {
        $text = trim($text);
        $prefix = (string) Config::get('messagePrefix', '');
        if ($prefix !== '' && ! str_starts_with($text, trim($prefix))) {
            $text = $prefix . $text;
        }
        $footer = Config::str('optOutFooter');
        if ($footer !== '' && ! str_contains($text, $footer)) {
            $text .= ' ' . $footer;
        }
        return $text;
    }

    /** SMS segment count (GSM-7 vs UCS-2). */
    public static function segments(string $text): int
    {
        $gsm = '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r" . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
        $ext = '^{}\\[~]|€';
        $isGsm = true;
        $len = 0;
        foreach (mb_str_split($text) as $ch) {
            if (mb_strpos($gsm, $ch) !== false) {
                $len++;
            } elseif (mb_strpos($ext, $ch) !== false) {
                $len += 2;
            } else {
                $isGsm = false;
                break;
            }
        }
        if (! $isGsm) {
            $len = mb_strlen($text, 'UTF-8');
            return $len <= 70 ? 1 : (int) ceil($len / 67);
        }
        return $len <= 160 ? 1 : (int) ceil($len / 153);
    }

    /** Message log (last 5,000 sends) for history and delivery-status tracking. */
    public static function record(array $row): void
    {
        Store::update('sent', function (array $d) use ($row) {
            $d[] = $row + ['at' => date('c')];
            return array_slice($d, -5000);
        });
    }
}

/**
 * %%entity.field%% replacement. Scalars from client/invoice/payment/service/overdue arrays, plus conveniences:
 * %%client.name%%, %%client.accountOutstanding%% (money formatted), dates as "Sep 22, 2026".
 */
final class Template
{
    public static function render(string $template, array $data): string
    {
        $tokens = [];
        foreach ($data as $type => $values) {
            if (! is_array($values)) {
                continue;
            }
            foreach ($values as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    continue;
                }
                $tokens['%%' . $type . '.' . $k . '%%'] = self::format((string) $k, $v);
            }
        }
        if (isset($data['client']) && is_array($data['client'])) {
            $tokens['%%client.name%%'] = self::clientName($data['client']);
        }
        $out = strtr($template, $tokens);
        // Unknown tokens become empty, then tidy doubled spaces.
        $out = preg_replace('~%%[a-zA-Z0-9_.]+%%~', '', $out) ?? $out;
        return trim(preg_replace('~[ \t]{2,}~', ' ', $out) ?? $out);
    }

    public static function clientName(array $c): string
    {
        $n = trim((string) ($c['companyName'] ?? ''));
        if ($n === '') {
            $n = trim(($c['firstName'] ?? '') . ' ' . ($c['lastName'] ?? ''));
        }
        return $n !== '' ? $n : ('Client #' . ($c['id'] ?? '?'));
    }

    private static function format(string $key, $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_bool($v)) {
            return $v ? 'yes' : 'no';
        }
        if (is_float($v) || (is_numeric($v) && preg_match('~(total|amount|balance|outstanding|price|credit|toPay)~i', $key))) {
            return number_format((float) $v, 2);
        }
        if (is_string($v) && stripos($key, 'date') !== false && preg_match('~^\d{4}-\d{2}-\d{2}~', $v)) {
            $t = strtotime($v);
            return $t ? date('M j, Y', $t) : $v;
        }
        return (string) $v;
    }
}
