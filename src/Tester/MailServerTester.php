<?php

namespace Balgor\MailServerAutodiscovery\Tester;

use Balgor\MailServerAutodiscovery\Services\MailServiceInterface;
use Balgor\MailServerAutodiscovery\Services\MailServerInterface;
use Balgor\MailServerAutodiscovery\Tester\PopServer;
use Balgor\MailServerAutodiscovery\Tester\ImapServer;
use Balgor\MailServerAutodiscovery\Tester\SmtpServer;
use Balgor\MailServerAutodiscovery\Services\Guess\GuessedMailServer;
use Balgor\MailServerAutodiscovery\Services\Guess\GuessedMailService;

/**
 * Description of MailServerTester
 *
 * @author matej.smisek
 */
class MailServerTester
{

    public function checkService(MailServiceInterface $service, $password = false)
    {
        if ($service->getIncomingServer() !== null) {
            if ($service->getIncomingServer()->getType() === MailServerInterface::TYPE_POP3) {
                $server = new PopServer($service->getIncomingServer());
                $this->checkServer($server, $service->getIncomingServer(), $password);               
//                echo $server->getHistory();
            }

            if ($service->getIncomingServer()->getType() === MailServerInterface::TYPE_IMAP) {
                $server = new ImapServer($service->getIncomingServer());
                $this->checkServer($server, $service->getIncomingServer(), $password);
//                echo $server->getHistory();
            }
        }

        if ($service->getOutgoingServer() !== null && $service->getOutgoingServer()->getType() === MailServerInterface::TYPE_SMTP) {
            $server = new SmtpServer($service->getOutgoingServer());
            $this->checkServer($server, $service->getOutgoingServer(), $password);
//            echo $server->getHistory();
        }
    }

    protected function checkServer(Server $serverHelper, MailServerInterface $server, $password = false)
    {
//        echo "Checking server\n";
        $server->setValidServer($serverHelper->check());
        if ($password !== false && $server->isValidServer() === true) {
//            echo "Checking login\n";
            $server->setValidLogin($serverHelper->authenticate($server->getUsername(), $password));
        }
    }

    public function autodetectService(GuessedMailServer $server)
    {
        if ($service->getIncomingServer()->getType() === MailServerInterface::TYPE_POP3) {
            $server = new PopServer($service->getIncomingServer());
            return $server->check();
        }
    }

}
