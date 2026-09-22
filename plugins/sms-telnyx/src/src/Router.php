<?php

declare(strict_types=1);

namespace SmsTelnyx;

/**
 * public.php dispatcher:
 *   ?hook=telnyx         Telnyx webhooks (signature-verified)
 *   POST JSON with uuid  CRM webhook events
 *   ?page=notices        Mass-notice admin page (+ ?api=… JSON actions)
 */
final class Router
{
    public static function run(?string $raw = null): void
    {
        $raw ??= (string) file_get_contents('php://input');
        $hook = (string) ($_GET['hook'] ?? '');

        try {
            if ($hook === 'telnyx' || isset($_SERVER['HTTP_TELNYX_SIGNATURE_ED25519'])) {
                self::telnyx($raw);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! isset($_GET['api'])) {
                $j = json_decode($raw, true);
                if (is_array($j) && isset($j['uuid'])) {
                    self::json(EventNotifier::handle($j));
                    return;
                }
            }
            self::admin($raw);
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
            self::status($e instanceof \InvalidArgumentException ? 400 : 500);
            self::json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    private static function telnyx(string $raw): void
    {
        $sig = (string) ($_SERVER['HTTP_TELNYX_SIGNATURE_ED25519'] ?? '');
        $ts = (string) ($_SERVER['HTTP_TELNYX_TIMESTAMP'] ?? '');
        if (! Telnyx::verify($raw, $sig, $ts)) {
            Log::warn('Rejected Telnyx webhook: bad or missing signature (check "Telnyx webhook public key")');
            self::status(401);
            self::json(['ok' => false]);
            return;
        }
        $payload = json_decode($raw, true);
        self::json(Inbound::handle(is_array($payload) ? $payload : []));
    }

    private static function admin(string $raw): void
    {
        $user = AdminAuth::currentUser();
        if (! AdminAuth::allowed($user)) {
            self::status(403);
            self::header('Content-Type: text/html; charset=utf-8');
            echo '<p style="font-family:sans-serif">Sign in to UISP CRM as an admin with client edit rights to use SMS notices.</p>';
            return;
        }
        $api = (string) ($_GET['api'] ?? '');
        if ($api === '') {
            self::header('Content-Type: text/html; charset=utf-8');
            self::header("Content-Security-Policy: default-src 'self' 'unsafe-inline'; frame-ancestors 'self'");
            $csrf = AdminAuth::csrf($user);
            $configured = Config::str('telnyxApiKey') !== '' && Config::str('telnyxFromNumber') !== '';
            require dirname(__DIR__) . '/templates/notices.php';
            return;
        }
        $in = json_decode($raw, true) ?: [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! AdminAuth::checkCsrf($user, (string) ($_SERVER['HTTP_X_CSRF'] ?? ''))) {
            self::status(403);
            self::json(['ok' => false, 'error' => 'Session expired — reload the page.']);
            return;
        }
        $out = match ($api) {
            'options' => Audience::options(),
            'preview' => MassNotice::preview($in),
            'test' => self::test($in, $user),
            'create' => MassNotice::create($in, $user),
            'batch' => MassNotice::batch((string) ($in['id'] ?? '')),
            'cancel' => MassNotice::cancel((string) ($in['id'] ?? '')),
            'history' => ['jobs' => MassNotice::history()],
            'detail' => MassNotice::detail((string) ($_GET['id'] ?? '')),
            'optouts' => ['optouts' => OptOut::all(), 'replies' => array_reverse(array_slice(Store::read('replies'), -50))],
            default => throw new \InvalidArgumentException('Unknown action'),
        };
        self::json(['ok' => true] + $out);
    }

    /** Sends the first recipient's rendered message to the admin's forward number. */
    private static function test(array $in, array $user): array
    {
        $to = Phone::normalize(Config::str('forwardRepliesTo'));
        if (! $to) {
            throw new \InvalidArgumentException('Set "Forward customer replies to" in plugin settings to receive test messages.');
        }
        $p = MassNotice::preview($in);
        $text = $p['rows'][0]['text'] ?? Telnyx::compose((string) ($in['message'] ?? ''));
        $r = Telnyx::send($to, '[TEST] ' . $text);
        Telnyx::record(['kind' => 'test', 'to' => $to, 'id' => $r['id'], 'status' => $r['status'], 'error' => $r['error'], 'by' => $user['username'] ?? '']);
        if (! $r['ok']) {
            throw new \RuntimeException('Telnyx rejected the test: ' . $r['error']);
        }
        return ['sentTo' => Phone::pretty($to)];
    }

    private static function status(int $code): void
    {
        if (! headers_sent()) {
            http_response_code($code);
        }
    }

    private static function header(string $h): void
    {
        if (! headers_sent()) {
            header($h);
        }
    }

    public static function json(array $data): void
    {
        self::header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
