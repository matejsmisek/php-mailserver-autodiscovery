<?php

namespace Balgor\MailServerAutodiscovery\Services\Guess;
use Balgor\MailServerAutodiscovery\Services\MailServer;
use Balgor\MailServerAutodiscovery\Services\MailServerInterface;
/**
 * Description of MailServer
 *
 * @author matej.smisek
 */
class GuessedMailServer extends MailServer
{

    
    public function guessFromEmail($email, $type)
    {
        $this->type = $type;
        $this->host = $type.'.'.substr($email, strrpos($email, '@') + 1);
        $this->port = 993;
        $this->encryption = MailServerInterface::ENCRYPTION_SSL;
        $this->authentication = MailServerInterface::AUTHENTICATION_PLAINTEXT;
        $this->username = $email;
    }
   
}
