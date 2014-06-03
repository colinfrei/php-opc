<?php

require_once('OpcClient.php');

$useLocal = false;
if (!$useLocal) {
    $servers = [
        'bb1' => [
            'host' => '192.168.80.85',
            'port' => 7890
        ]
    ];
} else {
    $servers = [
        'bb1' => [
            'host' => '127.0.0.1',
            'port' => 7890
        ],
        /*'bb2' => [
            'host' => '127.0.0.1',
            'port' => 7891
        ]*/
    ];

}

/**
 * x-coordinate =>
 *   y-coordinate => [server, channel, position]
 */
/*$map = [
    1 => [
        1 => ['bb1', 1, 1],
        2 => ['bb1', 1, 2],
        3 => ['bb1', 1, 3],
        4 => ['bb1', 1, 4],
        5 => ['bb1', 1, 5],
        6 => ['bb1', 2, 1],
        7 => ['bb1', 2, 2],
        8 => ['bb1', 2, 3],
        9 => ['bb1', 2, 4],
        10 => ['bb1', 2, 5],
        11 => ['bb1', 3, 1],
        12 => ['bb1', 3, 2],
        13 => ['bb1', 3, 3],
        14 => ['bb1', 3, 4],
        15 => ['bb1', 3, 5],
        16 => ['bb1', 4, 1],
        17 => ['bb1', 4, 2],
        18 => ['bb1', 4, 3],
        19 => ['bb1', 4, 4],
    ]
];*/

$map = array();
$x = 0;
for ($i = 1; $i <= 100; $i++) { //240
    $innerMap = array();
    for ($j = 1; $j <= 10; $j++) { //96
        $innerMap[$j] = array('bb1', 1, ++$x);
    }

    $map[$i] = $innerMap;
}

$opc = new OpcClient($servers, $map);

$opc->setPixel(1, 1, array(255, 50, 255));
$opc->setPixel(1, 2, array(50, 255, 0));
$opc->setPixel(1, 3, array(0, 255, 255));
$opc->setPixel(1, 4, array(255, 100, 255));
$opc->setPixel(1, 5, array(0, 255, 0));

$opc->sendToScreen();

$opc->closeSockets();
