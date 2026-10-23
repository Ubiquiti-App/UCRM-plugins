<?php

// php -d zend.assertions=1 plugins/mkt-queue-sync/tests/queue_check.php

declare(strict_types=1);

require __DIR__ . '/../src/vendor/autoload.php';

use MikrotikQueueSync\RouterOsApi;
use MikrotikQueueSync\Synchronizer;

ini_set('assert.exception', '1');

$config = [
    'limitAtPercentage' => '20/50',
    'burstLimitPercentage' => '10/20',
    'burstTime' => '8/16',
];
$service = [
    'uploadSpeed' => 5,
    'downloadSpeed' => 10,
    'trafficShapingOverrideEnabled' => false,
];
$plan = [
    'dataUsageLimit' => 3,
    'uploadBurst' => 1000,
    'downloadBurst' => 2000,
];

// Queue values (Mbps -> bps), upload/download
assert(Synchronizer::queueAttributes($service, $plan, $config) === [
    'max-limit' => '5000000/10000000',
    'limit-at' => '1000000/5000000',
    'priority' => '3/3',
    'burst-limit' => '5500000/12000000',
    'burst-threshold' => '1000000/2000000',
    'burst-time' => '8s/16s',
]);

// Override: only the direction that is set, empty override = plan speed
$override = [
    'trafficShapingOverrideEnabled' => true,
    'uploadSpeedOverride' => 20,
    'downloadSpeedOverride' => null,
] + $service;
assert(Synchronizer::queueAttributes($override, $plan, $config)['max-limit'] === '20000000/10000000');

// Plan not found: priority 8, no burst-threshold
$noPlan = Synchronizer::queueAttributes($service, null, $config);
assert($noPlan['priority'] === '8/8' && $noPlan['burst-threshold'] === '0/0');

// Config validation
assert(Synchronizer::validateConfig($config) === []);
assert(count(Synchronizer::validateConfig([
    'limitAtPercentage' => '20-50',
] + $config)) === 1);
assert(count(Synchronizer::validateConfig([])) === 3);

// Free version limit: first N services by ID, original order kept
$services = [[
    'id' => 30,
], [
    'id' => 10,
], [
    'id' => 20,
], [
    'id' => 40,
]];
assert(array_column(Synchronizer::limitServices($services, 2), 'id') === [10, 20]);
assert(count(Synchronizer::limitServices($services, 100)) === 4);

// sync_with_ucrm ranges: single IP, CIDR, A-B
assert(Synchronizer::ipInRange('10.0.0.5', '10.0.0.5'));
assert(! Synchronizer::ipInRange('10.0.0.6', '10.0.0.5'));
assert(Synchronizer::ipInRange('10.0.0.200', '10.0.0.0/24') && ! Synchronizer::ipInRange('10.0.1.1', '10.0.0.0/24'));
assert(Synchronizer::ipInRange('192.168.1.1', '0.0.0.0/0'));
assert(Synchronizer::ipInRange('10.0.0.50', '10.0.0.1-10.0.0.50') && ! Synchronizer::ipInRange('10.0.0.51', '10.0.0.1-10.0.0.50'));
$filtered = Synchronizer::filterBySyncRanges([
    [
        'target' => '10.0.0.1/32,10.0.1.1/32',
    ],
    [
        'target' => '10.0.2.1/32',
    ],
], ['10.0.0.0/24', '10.0.0.0/23']); // overlapping ranges: 10.0.0.1 only once
assert(array_column($filtered, 'target') === ['10.0.0.1/32,10.0.1.1/32']);

// Parent and dynamic queues are never managed
$queues = [
    [
        'name' => 'Tower',
        'target' => '10.0.0.0/24',
        'parent' => 'none',
        'dynamic' => 'false',
    ],
    [
        'name' => 'Ana - Service ID:2',
        'target' => '10.0.0.2/32',
        'parent' => 'Tower',
        'dynamic' => 'false',
    ],
    [
        'name' => '<pppoe-x>',
        'target' => '10.0.0.3/32',
        'parent' => 'none',
        'dynamic' => 'true',
    ],
];
assert(array_column(Synchronizer::serviceQueues($queues), 'name') === ['Ana - Service ID:2']);
assert(Synchronizer::targetKey('10.0.0.9/32,10.0.0.1/32') === Synchronizer::targetKey('10.0.0.1/32,10.0.0.9/32'));

// Charset round trip (UISP is UTF-8)
$cyrillic = [
    'name' => 'Иван Петров',
    'id' => 7,
];
$cp1251 = RouterOsApi::convert($cyrillic, 'CP1251', 'UTF-8');
assert(bin2hex(substr($cp1251['name'], 0, 4)) === 'c8e2e0ed');
assert(RouterOsApi::convert($cp1251, 'UTF-8', 'CP1251') === $cyrillic);

echo "OK\n";
