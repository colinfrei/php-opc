<?php

class OpcClient
{
    private $servers;
    private $map;

    private $sockets = array();
    private $serverData = array();
    /** @var array array of data on channels, in the format of $server => [$channel => highestPosition] */
    private $channelData = array();

    /**
     * @param array $servers An array of servers in this format: 'server1' => ['host' => '127.0.0.1', 'port' => 7890]
     * @param array $map A mapping of the pixels to coordinates: x => y => ['server1', channel, position]
     */
    public function __construct(array $servers, array $map)
    {
        $this->servers = $servers;
        $this->map = $map;

        $this->openSockets();
        $this->initChannelData();
    }

    private function openSockets()
    {
        foreach ($this->servers as $serverName => $server)
        {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            echo 'Connecting to socket ' . $serverName . ': ' . socket_connect($socket, $server['host'], $server['port']) . PHP_EOL;

            $this->sockets[$serverName] = $socket;
        }
    }

    private function initChannelData()
    {
        foreach ($this->map as $xCoordinate => $yCoordinates) {
            foreach ($yCoordinates as $yCoordinate => $data) {
                if (!isset($this->channelData[$data[0]])) {
                    $this->channelData[$data[0]] = array();
                }

                if (!isset($this->channelData[$data[0]][$data[1]]) || $data[2] > $this->channelData[$data[0]][$data[1]]) {
                    $this->channelData[$data[0]][$data[1]] = $data[2];
                }
            }
        }
    }

    /**
     * @param string $server Identifier of the server
     * @param array $leds accepts an array of arrays, with each like [r, g, b]
     * @param integer $channel
     */
    private function sendMsg($server, array $leds, $channel)
    {
        $lights = '';
        foreach ($leds as $led) {
            $lights .= pack('CCC', $led[0], $led[1], $led[2]);
        }

        $header = pack('C', $channel) . pack('C', 0) . pack('n', strlen($lights));
        echo 'Sending data to ' . $server . ' on channel ' . $channel . ':' . PHP_EOL;
        var_dump($header . $lights);


        $sentData = socket_write($this->sockets[$server], $header . $lights);
        echo 'sent bytes: ' . strlen($header . $lights) . PHP_EOL;
        echo 'received bytes: ' . $sentData . PHP_EOL;
    }

    public function setFullColor($r, $g, $b)
    {
        foreach ($this->channelData as $server => $channelHighest) {
            $lightData = array_fill(1, max($channelHighest), array($r, $g, $b));

            $this->sendMsg($server, $lightData, 0);
        }
    }

    public function turnOff()
    {
        $this->setFullColor(0, 0, 0);
    }

    public function setPixel($x, $y, array $color)
    {
        $mapData = $this->map[$x][$y];

        if (!isset($this->serverData[$mapData[0]])) {
            $this->serverData[$mapData[0]] = array();
        }

        if (!isset($this->serverData[$mapData[0]][$mapData[1]])) {
            $this->serverData[$mapData[0]][$mapData[1]] = array();
        }

        $this->serverData[$mapData[0]][$mapData[1]][$mapData[2]] = $color;

        return $this;
    }

    public function sendToScreen()
    {
        // TODO: reset screen?
        foreach ($this->serverData as $server => $channels) {
            foreach ($channels as $channel => $positions) {
                $channelData = [];
                $highestPositionInChannel = max(array_keys($positions));
                for ($i = 1; $i <= $highestPositionInChannel; $i++) {
                    if (!isset($positions[$i])) {
                        $positions[$i] = [0, 0, 0];
                    }

                    $channelData[] = $positions[$i];
                }

                $this->sendMsg($server, $channelData, $channel);
            }
        }

        $this->serverData = array();
    }

    public function closeSockets()
    {
        foreach ($this->sockets as $serverName => $socket) {
            echo 'shutting down ' . $serverName . PHP_EOL;

            socket_shutdown($socket);
            socket_close($socket);
        }
    }
}
