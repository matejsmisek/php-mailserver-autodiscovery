<?php

namespace Balgor\MailServerAutodiscovery\Tester;

use Balgor\MailServerAutodiscovery\Services\MailServerInterface;

/**
 * Description of Server
 *
 * @author matej.smisek
 */
class Server
{

    protected $socket;
    protected $connected = false;
    protected $timeout = 30;
    protected $history;
    protected $lastResponse;

    /**
     *
     * @var MailServerInterface
     */
    protected $server;

    public function __construct(MailServerInterface $server)
    {
        $this->server = $server;
    }

    public function connectOLD()
    {
        if (!($socket = fsockopen(($this->server->getEncryption() === MailServerInterface::ENCRYPTION_SSL ? 'tls://' : 'tcp://') . $this->server->getHost(), $this->server->getPort(), $errno, $errstr, 15))) {
            throw new \RuntimeException("Error connecting to '$host' ($errno) ($errstr)");
        }
        if (!stream_set_timeout($socket, 2)) {
            throw new \RuntimeException("Error setting Timeout");
        }
//        var_dump($socket);
        $this->socket = $socket;
    }

    public function connect()
    {

        $host = ($this->server->getEncryption() === MailServerInterface::ENCRYPTION_SSL ? 'tls://' : 'tcp://') . $this->server->getHost();
        $port = $this->server->getPort();

        if (function_exists('stream_socket_client')) {
            $context = stream_context_create([]);
//            set_error_handler([$this, 'errorHandler']);
            $this->socket = stream_socket_client(
                    $host . ':' . $port,
                    $errno,
                    $errstr,
                    $this->timeout,
                    STREAM_CLIENT_CONNECT,
                    $context
            );
//            restore_error_handler();
        } else {
//            set_error_handler([$this, 'errorHandler']);
            $this->socket = fsockopen($host, $port, $errno, $errstr, $this->timeout);
//            restore_error_handler();
        }
        if (!is_resource($this->socket)) {
            throw new \RuntimeException("Error connecting to '$host' ($errno) ($errstr)");
            ;
        }
        if (!stream_set_timeout($this->socket, 2)) {
            throw new \RuntimeException("Error setting Timeout");
        }
        $this->connected = true;
        $this->lastResponse = $this->readResponse();
        $this->history .= $this->lastResponse;
        return true;
    }

    public function close()
    {
        @fclose($this->socket);
    }

    protected function sendCommand($command)
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException("Socket is not a stream");
        }
        $this->history .= $command . "\r\n";
        fwrite($this->socket, $command . "\r\n");
        $this->lastResponse = $this->readResponse();
        $this->history .= $this->lastResponse;
    }

    /**
     * Reads all lines of server response
     * 
     * Based on PHPMailer SMTP reader
     * https://github.com/PHPMailer/PHPMailer
     * 
     * 
     * @return string
     * @throws \RuntimeException
     */
    protected function readResponse()
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException("Socket is not a stream");
        }

        $timelimit = time() + 300;

        $data = '';

        while (is_resource($this->socket) && !feof($this->socket)) {

            $str = @fgets($this->socket, 512);
            $data .= $str;

            if ($this->server->getType() === MailServerInterface::TYPE_SMTP) {
                if (!isset($str[3]) || (isset($str[3]) && $str[3] == ' ')) {
                    break;
                }
            }
            if ($this->server->getType() === MailServerInterface::TYPE_POP3) {
                if (empty(trim($str))) {
                    break;
                }
            }
            $info = stream_get_meta_data($this->socket);
            if ($info['timed_out']) {
                echo "Timedout\n";
                break;
            }

            if (time() > $timelimit) {
                echo "Long read\n";
                break;
            }
        }
        return $data;
    }

    public function getHistory()
    {
        return $this->history;
    }

}
