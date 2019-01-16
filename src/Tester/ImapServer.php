<?php

namespace Balgor\MailServerAutodiscovery\Tester;

use Balgor\MailServerAutodiscovery\Services\MailServerInterface;

/**
 * Description of PopServer
 *
 * @author matej.smisek
 */
class ImapServer extends Server
{

    public function check()
    {
        $result = false;
        try {
            $this->connect();
            if ($this->checkResponse() === false) {
                throw new \RuntimeException('Error connecting');
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

            $this->sendCommand('1 LOGIN ' . $username . ' ' . $password);
            if ($this->checkResponse() === false) {
                throw new \RuntimeException('Error USER');
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

        $this->sendCommand('1 STARTTLS');
        if ($this->checkResponse() === false) {
            throw new \RuntimeException('Error Not supporting STARTTLS');
        }
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new \RuntimeException('Error STARTTLS socket');
        }
    }

    protected function checkResponse()
    {
        if (substr($this->lastResponse, 2, 2) === 'OK') {
            return true;
        }

        return false;
    }

}
