<?php

namespace Balgor\MailServerAutodiscovery\Services\Mozzila;

use Balgor\MailServerAutodiscovery\Services\MailService;
use Balgor\MailServerAutodiscovery\Services\MailServerInterface;

/**
 * Description of MailServer
 *
 * @author matej.smisek
 */
class MozzilaNotationMailService extends MailService
{

    public function parseXml(\SimpleXMLElement $xml, $preferredType = MailServerInterface::TYPE_IMAP)
    {
        $this->name = (string) $xml->emailProvider->displayName;
        $outgoingServers = [];
        foreach ($xml->emailProvider->outgoingServer as $server) {
            if (((string) $server['type'])  !== MailServerInterface::TYPE_SMTP) {
                continue;
            }
            $outgoing = new MozzilaNotationMailServer();
            $outgoing->setType(MailServerInterface::TYPE_SMTP);
            $outgoing->setPort((integer) $server->port);
            $outgoing->setHost((string) $server->hostname);
            $outgoing->setUsername((string) $server->username);
            $outgoing->setEncryption($this->parseEncryption((string) $server->socketType));
            $outgoing->setAuthentication($this->parseAuthentication((string) $server->authentication));
            $outgoingServers[MailServerInterface::TYPE_SMTP] = $outgoing;
        }

        if (!array_key_exists(MailServerInterface::TYPE_SMTP, $outgoingServers)) {
            throw new \RuntimeException('No SMTP server found');
        }
        $this->outgoingServer = $outgoingServers[MailServerInterface::TYPE_SMTP];


        $incomingServers = [];
        foreach ($xml->emailProvider->incomingServer as $server) {
            if (((string) $server['type']) !== MailServerInterface::TYPE_IMAP && ((string) $server['type']) !== MailServerInterface::TYPE_POP3) {
                continue;
            }
            $incoming = new MozzilaNotationMailServer();
            $incoming->setType((string) $server['type']);
            $incoming->setPort((integer) $server->port);
            $incoming->setHost((string) $server->hostname);
            $incoming->setUsername((string) $server->username);
            $incoming->setEncryption($this->parseEncryption((string) $server->socketType));
            $incoming->setAuthentication($this->parseAuthentication((string) $server->authentication));
            $incomingServers[(string) $server['type']] = $incoming;
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
            case 'password-encrypted':
                return MailServerInterface::AUTHENTICATION_ENCRYPTED;
            case 'password-cleartext':
            default:
                return MailServerInterface::AUTHENTICATION_PLAINTEXT;
        }
    }

    protected function parseEncryption($enc)
    {
        switch (strtoupper($enc)) {
            case 'SSL':
                return MailServerInterface::ENCRYPTION_SSL;
            case 'STARTTLS':
                return MailServerInterface::ENCRYPTION_STARTTLS;
            case 'PLAIN':
            default:
                return MailServerInterface::ENCRYPTION_NONE;
        }
    }

}
