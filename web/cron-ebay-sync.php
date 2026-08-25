<?php
/**
 * Endpoint cron eBay sync (dev/prod ready)
 * URL: /cron-ebay-sync.php?token=...&target_group_id=2&dry_run=1&batch_limit=50
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Paris');

@ini_set('display_errors', '0');
@ini_set('html_errors', '0');

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

function cron_ebay_sync_respond(int $httpCode, array $payload): void
{
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cron_ebay_sync_request_value(string $key, string $default = ''): string
{
    if (isset($_POST[$key])) {
        return (string) $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return (string) $_GET[$key];
    }
    return $default;
}

try {
    $expectedToken = trim((string) getenv('EBAY_SYNC_TRIGGER_TOKEN'));
    if ($expectedToken === '') {
        cron_ebay_sync_respond(500, array(
            'ok' => false,
            'code' => 'token_not_configured',
            'message' => 'EBAY_SYNC_TRIGGER_TOKEN non configuré côté serveur.',
        ));
    }

    $providedToken = trim(cron_ebay_sync_request_value('token'));
    if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        cron_ebay_sync_respond(403, array(
            'ok' => false,
            'code' => 'forbidden',
            'message' => 'Token invalide.',
        ));
    }

    $targetGroupId = (int) cron_ebay_sync_request_value('target_group_id', '0');
    if ($targetGroupId <= 0) {
        cron_ebay_sync_respond(400, array(
            'ok' => false,
            'code' => 'validation_error',
            'message' => 'target_group_id est obligatoire et doit être > 0.',
        ));
    }

    $dryRunRaw = strtolower(trim(cron_ebay_sync_request_value('dry_run', '0')));
    $dryRun = ($dryRunRaw === '1' || $dryRunRaw === 'true' || $dryRunRaw === 'yes' || $dryRunRaw === 'on') ? 1 : 0;

    $batchLimit = (int) cron_ebay_sync_request_value('batch_limit', '0');
    if ($batchLimit < 0) {
        $batchLimit = 0;
    }

    include __DIR__ . '/config.php';
    include __DIR__ . '/include/var.inc.php';
    include __DIR__ . '/class/mysqli.class.php';
    include __DIR__ . '/lib/utils.lib.php';

    $babase = new DataBase();
    $babase->DbConnect();
    global $babase;

    require_once __DIR__ . '/class/ebay.class.php';

    $result = ebay_rest_run_sync(
        $targetGroupId,
        0,
        'cron',
        $batchLimit,
        array('dryRun' => $dryRun)
    );

    if (!isset($result['ok']) || $result['ok'] !== true) {
        cron_ebay_sync_respond(500, array(
            'ok' => false,
            'code' => 'server_error',
            'message' => isset($result['message']) ? (string) $result['message'] : 'Erreur synchronisation eBay.',
            'data' => isset($result['data']) && is_array($result['data']) ? $result['data'] : array(),
        ));
    }

    $runData = isset($result['data']) && is_array($result['data']) ? $result['data'] : array();

    cron_ebay_sync_respond(200, array(
        'ok' => true,
        'code' => 'ok',
        'message' => isset($result['message']) ? (string) $result['message'] : '',
        'runId' => isset($runData['runId']) ? (int) $runData['runId'] : 0,
        'status' => isset($runData['status']) ? (string) $runData['status'] : '',
        'stats' => isset($runData['stats']) && is_array($runData['stats']) ? $runData['stats'] : array(),
        'data' => $runData,
    ));
} catch (Throwable $e) {
    cron_ebay_sync_respond(500, array(
        'ok' => false,
        'code' => 'server_error',
        'message' => $e->getMessage(),
    ));
}
