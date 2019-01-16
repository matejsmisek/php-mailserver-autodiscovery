<?php

namespace Balgor\MailServerAutodiscovery\Tester;

use Balgor\MailServerAutodiscovery\Services\MailServerInterface;

/**
 * Description of PopServer
 *
 * @author matej.smisek
 */
class SmtpServer extends Server
{

    public function check()
    {
        $result = false;
        try {
            $this->connect();
            if ($this->checkResponse(220) === false) {
                throw new \RuntimeException('Error connecting');
            }
            $this->sendCommand('EHLO ' . $this->server->getHost());
            if ($this->checkResponse(250) === false) {
                throw new \RuntimeException('Error Ehloing');
            }
            if ($this->server->getEncryption() === MailServerInterface::ENCRYPTION_STARTTLS) {
                $this->enableStarttls();
            }
            $result = true;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
        return $result;
    }

    public function authenticate($username, $password)
    {
        $result = false;
        try {

            $this->sendCommand('AUTH LOGIN');
            if ($this->checkResponse(334) === false) {
                throw new \RuntimeException('Error Login');
            }
            $this->sendCommand(base64_encode($username));
            if ($this->checkResponse(334) === false) {
                throw new \RuntimeException('Error Login');
            }
            $this->sendCommand(base64_encode($password));
            if ($this->checkResponse(235) === false) {
                throw new \RuntimeException('Error Login');
            }
            $result = true;
        } catch (\Exception $e) {
            return false;
        } finally {
            $this->close();
        }
        return $result;
    }

    protected function enableStarttls()
    {

        $this->sendCommand('STARTTLS');
        if ($this->checkResponse(220) === false) {
            throw new \RuntimeException('Error Not supporting STARTTLS');
        }
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new \RuntimeException('Error STARTTLS socket');
        }
    }

    protected function checkResponse($code)
    {
        if (substr($this->lastResponse, 0, 3) == $code) {
            return true;
        }

        return false;
    }

}
