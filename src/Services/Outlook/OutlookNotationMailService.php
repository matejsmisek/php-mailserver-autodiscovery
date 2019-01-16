<?php

namespace Balgor\MailServerAutodiscovery\Services\Outlook;

use Balgor\MailServerAutodiscovery\Services\MailService;
use Balgor\MailServerAutodiscovery\Services\MailServerInterface;

/**
 * Description of MailServer
 *
 * @author matej.smisek
 */
class OutlookNotationMailService extends MailService
{

    public function parseXml(\SimpleXMLElement $xml, $preferredType = MailServerInterface::TYPE_IMAP)
    {
        
        $this->name = (string) $xml->Response->User->DisplayName;
        
        $incomingServers = [];
        foreach ($xml->Response->Account->Protocol as $serverXml) {
            if (!in_array((string) $serverXml->Type, [MailServerInterface::TYPE_IMAP, MailServerInterface::TYPE_POP3, MailServerInterface::TYPE_SMTP])) {
                continue;
            }
            $server = new OutlookNotationMailServer();      
            $server->setPort((integer) $serverXml->Port);
            $server->setHost((string) $serverXml->Server);
            $server->setUsername((string) $serverXml->LoginName);
            $server->setEncryption($this->parseEncryption((string) $serverXml->SSL));
            $server->setAuthentication($this->parseAuthentication((string) $serverXml->SPA));
            if ((string) $serverXml->Type == MailServerInterface::TYPE_SMTP) {                
                $server->setType(MailServerInterface::TYPE_SMTP);
                $this->outgoingServer = $server;
            } else {
                $incomingServers[(string) $serverXml->Type] = $server;
            }
        }
        
        if (array_key_exists($preferredType, $incomingServers)) {
            $this->incomingServer = $incomingServers[$preferredType];
        } elseif (!empty($incomingServers)) {
            $this->incomingServer = array_shift($incomingServers);
        } else {
            throw new \RuntimeException('No Incoming server found');
        }
    }

    protected function parseAuthentication($auth)
    {
        switch (strtolower($auth)) {
            case 'on':
                return MailServerInterface::AUTHENTICATION_ENCRYPTED;
            case 'off':
            default:
                return MailServerInterface::AUTHENTICATION_PLAINTEXT;
        }
    }

    protected function parseEncryption($enc)
    {
        switch (strtolower($enc)) {
            case 'on':
                return MailServerInterface::ENCRYPTION_SSL;          
            case 'off':
            default:
                return MailServerInterface::ENCRYPTION_NONE;
        }
    }

}
