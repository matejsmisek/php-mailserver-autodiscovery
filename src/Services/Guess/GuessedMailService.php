<?php

namespace Balgor\MailServerAutodiscovery\Services\Guess;

use Balgor\MailServerAutodiscovery\Services\MailService;
use Balgor\MailServerAutodiscovery\Services\MailServerInterface;

/**
 * Description of MailServer
 *
 * @author matej.smisek
 */
class GuessedMailService extends MailService
{

    public function guessFromEmail($email, $preferredType = 'imap')
    {
        $this->incomingServer = new GuessedMailServer();
        $this->incomingServer->guessFromEmail($email, $preferredType);
        $this->outgoingServer = new GuessedMailServer();
        $this->outgoingServer->guessFromEmail($email, MailServerInterface::TYPE_SMTP);
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setIncomingServer(?MailServerInterface $incomingServer)
    {
        $this->incomingServer = $incomingServer;
        return $this;
    }

    public function setOutgoingServer(?MailServerInterface $outgoingServer)
    {
        $this->outgoingServer = $outgoingServer;
        return $this;
    }

}
