<?php

/*
----------------------------------
 ------  Created: 111426   ------
 ------  AJ Baker         ------
----------------------------------
*/

error_reporting(E_ERROR | E_PARSE);

if (!function_exists('logger')) {
    require $argv[1] ?? __DIR__ . '/../root/app/www/public/functions/logger.php';
}

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function truncateMiddle($value, $maximum)
{
    if (strlen($value) <= $maximum) {
        return $value;
    }
    return substr($value, 0, 7) . '..........' . substr($value, -7);
}

function str_contains_any($value, $needles)
{
    foreach ($needles as $needle) {
        if (str_contains($value, $needle)) {
            return true;
        }
    }
    return false;
}

class FixtureLog
{
    public $context;
    public static array $records = [];
    private string $path;

    public function stream_open($path, $mode, $options, &$openedPath)
    {
        $this->path = $path;
        self::$records[$path] ??= '';
        return true;
    }

    public function stream_write($data)
    {
        self::$records[$this->path] .= $data;
        return strlen($data);
    }

    public function stream_flush()
    {
        return true;
    }

    public function url_stat($path, $flags)
    {
        return ['size' => strlen(self::$records[$path] ?? '')];
    }
}

class FixtureShell
{
    public string $lines;

    public function exec($command)
    {
        return str_starts_with($command, 'wc -l ') ? (substr_count($this->lines, "\n") + 1) . ' fixture' : $this->lines;
    }
}

class Starr
{
    public array $lookups = [];

    public function getAppFromProxiedKey($value, $truncated = false)
    {
        $this->lookups[] = [$value, $truncated];
        return $value === 'fixture-ui-key' ? [
            'proxiedAppDetails' => ['id' => 17, 'apikey' => str_repeat('v', 32)],
            'starrAppDetails' => ['starr' => 4],
            'access' => []
        ] : [];
    }

    public function getStarrInterfaceNameFromId($id)
    {
        return 'sonarr';
    }

    public function isAllowedEndpoint($app, $access, $endpoint)
    {
        return ['allowed' => true, 'starrEndpoint' => $endpoint];
    }

    public function isAllowedEndpointMethod($access, $endpoint, $method)
    {
        return true;
    }
}

stream_wrapper_register('fixturelog', FixtureLog::class);
$settingsTable = ['logRotationSize' => 1000];
$LOG_ROTATE_SIZE = 2;
$sentinel = 'fixture-' . str_repeat('s', 40);
$_SERVER = ['REQUEST_METHOD' => 'PUT', 'HTTP_USER_AGENT' => $sentinel];

foreach ([202, 400, 200] as $status) {
    $destination = 'fixturelog://access-' . $status;
    logger($destination, [
        'req' => 42,
        'consumerId' => 17,
        'backendId' => 4,
        'starr' => $sentinel,
        'apikey' => $sentinel,
        'endpoint' => '/api/v3/notification/4?password=' . $sentinel,
        'proxyCode' => 200,
        'starrCode' => $status,
        'starrRequest' => [
            'payload' => ['password' => $sentinel],
            'response' => $sentinel,
            'responseHeaders' => ['Authorization' => $sentinel],
            'error' => $sentinel,
            'url' => 'https://fixture.invalid/?apikey=' . $sentinel
        ]
    ]);
    $line = FixtureLog::$records[$destination];
    check(!str_contains($line, $sentinel), 'credential sentinel leaked for backend ' . $status);
    check(!str_contains($line, 'key:'), 'credential fragment field remains');
    check(str_contains($line, 'consumer:17; backend:4;'), 'consumer/backend metadata missing');
    check(str_contains($line, 'endpoint:/api/v3/notification/4;'), 'path metadata missing');
    check(str_contains($line, 'starrCode:' . $status . ';'), 'backend status changed');
}

logger('fixturelog://access-text', [
    'req' => 43, 'consumerId' => 17, 'backendId' => 4,
    'text' => $sentinel, 'event' => $sentinel, 'proxyCode' => 400
]);
check(!str_contains(FixtureLog::$records['fixturelog://access-text'], $sentinel), 'access text/event reflected');

$_SERVER['REQUEST_METHOD'] = 'PUT ' . $sentinel;
logger('fixturelog://access-invalid', [
    'req' => 44, 'consumerId' => '17;' . $sentinel,
    'endpoint' => '/api/invalid;' . $sentinel . "\n", 'proxyCode' => 400
]);
$invalid = FixtureLog::$records['fixturelog://access-invalid'];
check(!str_contains($invalid, $sentinel), 'invalid metadata reflected');
check(substr_count($invalid, "\n") === 1, 'metadata forged a log line');
check(str_contains($invalid, 'method:unknown;'), 'unknown method not bounded');

logger('fixturelog://system', ['text' => 'Existing system diagnostic', 'notificationCode' => 204]);
check(str_contains(FixtureLog::$records['fixturelog://system'], 'Existing system diagnostic'), 'system text behavior changed');

$signalrKey = 'fixture-context-' . str_repeat('k', 32);
$proxiedApp = [
    'proxiedAppDetails' => ['id' => 17, 'apikey' => $signalrKey],
    'starrAppDetails' => ['id' => 4]
];
$_SERVER['REQUEST_METHOD'] = 'GET';
foreach (['auth-granted', 401, 405] as $code) {
    $destination = 'fixturelog://signalr-' . $code;
    logger($destination, [
        'req' => 45, 'apikey' => $signalrKey,
        'endpoint' => '/signalr/negotiate?access_token=' . $signalrKey,
        'proxyCode' => $code
    ]);
    $line = FixtureLog::$records[$destination];
    check(str_contains($line, 'consumer:17; backend:4;'), 'SignalR consumer context missing');
    check(str_contains($line, 'proxyCode:' . $code . ';'), 'SignalR status semantics changed');
    check(!str_contains($line, $signalrKey) && !str_contains($line, 'key:'), 'SignalR key material leaked');
}
$signalrLine = trim(FixtureLog::$records['fixturelog://signalr-auth-granted']);
check(!str_contains($signalrLine, 'proxyCode:200;'), 'SignalR auth grant claimed proxy success');

logger('fixturelog://signalr-mismatch', [
    'req' => 46, 'apikey' => strrev($signalrKey), 'proxyCode' => 401
]);
$mismatch = FixtureLog::$records['fixturelog://signalr-mismatch'];
check(!str_contains($mismatch, 'consumer:') && !str_contains($mismatch, 'backend:'), 'mismatched key inherited consumer context');

logger('fixturelog://signalr-missing', ['req' => 47, 'proxyCode' => 401]);
check(!str_contains(FixtureLog::$records['fixturelog://signalr-missing'], 'consumer:'), 'missing key inherited consumer context');

logger('fixturelog://signalr-explicit', [
    'req' => 48, 'apikey' => $signalrKey, 'consumerId' => 99, 'backendId' => 8, 'proxyCode' => 405
]);
check(str_contains(FixtureLog::$records['fixturelog://signalr-explicit'], 'consumer:99; backend:8;'), 'explicit consumer metadata overridden');

logger('fixturelog://signalr-partial', [
    'req' => 49, 'apikey' => $signalrKey, 'consumerId' => 99, 'proxyCode' => 405
]);
check(!str_contains(FixtureLog::$records['fixturelog://signalr-partial'], 'backend:'), 'partial explicit metadata inherited a backend');

logger('fixturelog://signalr-enum', [
    'req' => 50, 'apikey' => $signalrKey,
    'proxyCode' => 'auth-granted;' . $sentinel, 'starrCode' => 'auth-granted'
]);
$enum = FixtureLog::$records['fixturelog://signalr-enum'];
check(!str_contains($enum, $sentinel), 'arbitrary status string reflected');
check(str_contains($enum, 'proxyCode:0; starrCode:0;'), 'auth enum escaped proxy-only whitelist');

$proxiedApp['proxiedAppDetails']['apikey'] = '';
logger('fixturelog://signalr-empty', ['req' => 51, 'apikey' => '', 'proxyCode' => 401]);
check(!str_contains(FixtureLog::$records['fixturelog://signalr-empty'], 'consumer:'), 'empty keys inherited consumer context');
unset($proxiedApp);

define('LOG_LINES_PER_PAGE', 100);
$shell = new FixtureShell();
$shell->lines = implode("\n", [
    'date consumer:17; backend:4; endpoint:/api/v3/notification/4; method:put; proxyCode:200; starrCode:202;',
    'date consumer:170; backend:4; endpoint:/other-consumer; method:get; proxyCode:200;',
    'date consumer:18; backend:4; endpoint:/another-consumer; method:get; proxyCode:200;',
    'date consumer:0; endpoint:/unknown-consumer-id; method:get; proxyCode:200;',
    'date key:fixture-ui-key; ' . $sentinel,
    '2026-01-01T00:00:00.0000000 [60] ua:old-client; key:vvvvvvv..........vvvvvvv; endpoint:/legacy-secret-endpoint; method:put; proxyCode:200; starrCode:202; starrResponse:' . $sentinel . '; file:/api/index.php; line:1;',
    '2026-01-01T00:00:00.0000000 [61] ua:old-client; key:vvvvvvv..........vvvvvvv; endpoint:/legacy-error; method:get; proxyCode:200; starrCode:500; starrResponse:' . $sentinel . '; file:/api/index.php; line:1;',
    '2026-01-01T00:00:00.0000000 [62] ua:forged key:vvvvvvv..........vvvvvvv; key:another-consumer; endpoint:/wrong-legacy-consumer; method:get; proxyCode:200;',
    '2026-01-01T00:00:00.0000000 [63] ua:forged key:vvvvvvv..........vvvvvvv; endpoint:/forged-ua; method:get; proxyCode:401;',
    '2026-01-01T00:00:00.0000000 [64] ua:forged consumer:17; key:another-consumer; endpoint:/forged-consumer; method:get; proxyCode:200; starrResponse:' . $sentinel,
    $signalrLine
]);
$starr = new Starr();
$appsTable = [['id' => 17, 'apikey' => str_repeat('v', 32)], ['id' => 18, 'apikey' => str_repeat('w', 32)]];
$_POST = ['key' => 'fixture-ui-key'];
ob_start();
getLog('/fixture/access.log', 1, 'sample');
$html = ob_get_clean();
check($starr->lookups === [['fixture-ui-key', true]], 'UI did not resolve the existing truncated-key argument once');
check(str_contains($html, '/api/v3/notification/4'), 'consumer log/endpoint view lost its own record');
check(str_contains($html, '/signalr/negotiate') && str_contains($html, 'proxyCode:auth-granted;'), 'consumer view lost SignalR authorization record');
check(!str_contains($html, '/other-consumer'), 'consumer ID boundary leaked another record');
check(!str_contains($html, '/another-consumer'), 'consumer view leaked another record');
check(!str_contains($html, $sentinel), 'consumer view exposed a legacy key record');
check(substr_count($html, 'event:legacy;') === 2, 'safe legacy metadata history lost');
check(str_contains($html, '[60]') && str_contains($html, '[61]'), 'legacy request attribution missing');
check(str_contains($html, 'starrCode:500;'), 'legacy backend error status lost');
check(!str_contains($html, '/legacy-secret-endpoint') && !str_contains($html, '/legacy-error'), 'legacy payload path reflected');
check(!str_contains($html, '[62]') && !str_contains($html, '[63]') && !str_contains($html, '[64]'), 'ambiguous or forged legacy row admitted');

$appsTable[] = ['id' => 19, 'apikey' => str_repeat('v', 7) . str_repeat('x', 18) . str_repeat('v', 7)];
ob_start();
getLog('/fixture/access.log', 1, 'sample');
$ambiguous = ob_get_clean();
check(!str_contains($ambiguous, 'event:legacy;'), 'ambiguous legacy key fragment admitted');
check(str_contains($ambiguous, '/api/v3/notification/4'), 'legacy ambiguity hid native ID records');

$_POST = ['key' => 'unknown-fixture-key'];
ob_start();
getLog('/fixture/access.log', 1, 'sample');
$html = ob_get_clean();
check(!str_contains($html, '/api/v3/notification/4'), 'unknown UI consumer exposed a record');
check(!str_contains($html, '/signalr/negotiate'), 'unknown UI consumer exposed a SignalR record');
check(!str_contains($html, '/unknown-consumer-id'), 'unknown UI consumer exposed a zero-ID record');

stream_wrapper_unregister('fixturelog');
echo "PASS: access secrecy, SignalR attribution/auth status, metadata bounds, system diagnostics, and consumer log isolation\n";
