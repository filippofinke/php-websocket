<?php
/**
*
* @author Filippo Finke
*/
require 'packet.php';

class Server
{

  /**
  * See https://tools.ietf.org/html/rfc6455
  */
    private $token = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

    private $debug = false;

    public $onmessage;

    private $port;

    public function onMessage($callback)
    {
    	$this->onmessage = $callback;
    }

    private function getSignature($key)
    {
        return base64_encode(sha1($key.$this->token, true));
    }

    private function lastError()
    {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        $this->log("[$errorcode] $errormsg", "ERROR");
    }

    public function debug()
    {
        $this->debug = !$this->debug;
    }

    public function log($text, $type = 'info')
    {
        if ($this->debug) {
            echo date("H:i:s d/m/Y")." ".$type." - ".$text.PHP_EOL;
        }
    }

    public function send($message, $socket)
    {
    }

    public function read($socket)
    {
        $data = "";
        $bytes = 1;
        while ($bytes > 0) {
            $bytes = socket_recv($socket, $data, 2048, 0);
            $packet = new Packet($data);
            $packet->log();
            $this->log($packet->getPayLoadLength()."# ".$packet->getPayloadString());
            if($this->onmessage != "")
            {
            	var_dump($this->onmessage("test"));
            	$this->onmessage($packet->getPayloadString());
            }
        }
        $this->lastError();
        $this->log("Client disconnected");
    }

    public function start()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $this->log($this->lastError());
        } else {
            $this->log("Socket created");
            if (socket_bind($socket, "127.0.0.1", $this->port)) {
                $this->log("Socket is now binded: 127.0.0.1 ".$this->port);
                if (socket_listen($socket)) {
                    $this->log("Socket is listening");
                    socket_set_nonblock($socket);
                    while (true) {
                        $client = socket_accept($socket);
                        if ($client !== false) {
                            $startTime = microtime();
                            $this->log("New client connected - Handshake");
                            $handshake = "";
                            $read = "";
                            $received = false;
                            while (!($read == "" && $received)) {
                                $read = socket_read($client, 1024);
                                if ($read != "") {
                                    $handshake = $handshake."".$read;
                                    $received = true;
                                }
                            }
                            preg_match_all('/Sec-WebSocket-Key: (.*?)\n/s', $handshake, $matches);
                            $key = trim($matches[1][0]);
                            $signature = $this->getSignature($key);
                            $this->log($key." - ".$signature);
                            $response = "HTTP/1.1 101 Switching Protocols\r\n".
                          "Upgrade: websocket\r\n".
                          "Connection: Upgrade\r\n".
                          "Sec-WebSocket-Accept: $signature\r\n\r\n";
                            if (socket_write($client, $response) === false) {
                                $this->lastError();
                            } else {
                                $this->log("Handshake end");

                                $pid = pcntl_fork();
                                if (!$pid) {
                                    socket_set_block($client);
                                    $this->log($key." Process forked");
                                    $this->read($client);
                                    socket_close($client);
                                }
                            }
                        }
                    }
                } else {
                    $this->log($this->lastError());
                }
            } else {
                $this->log($this->lastError());
            }
        }
    }

    public function __construct($port)
    {
        $this->port = $port;
    }
}

$s = new Server($argv[1]);
$s->debug();

$onMessage = function($arg) {
	echo $arg.PHP_EOL;
};
$onMessage("ASD");
$s->onMessage($onMessage);

var_dump($s->onmessage);
$s->onmessage("asd");

//$s->start();
