<?php
namespace Balgor\MailServerAutodiscovery\Services;

/**
 *
 * @author matej.smisek
 */
interface MailServerInterface
{

    const TYPE_IMAP = 'imap';
    const TYPE_POP3 = 'pop3';
    const TYPE_SMTP = 'smtp';
    
    const ENCRYPTION_NONE = 'none';
    const ENCRYPTION_STARTTLS = 'starttls';
    const ENCRYPTION_SSL = 'ssl';
    
    const AUTHENTICATION_PLAINTEXT = 'plaintext';
    const AUTHENTICATION_ENCRYPTED = 'encrypted';
    
    public function getType(): string;

    public function getHost(): string;

    public function getPort(): int;

    public function getEncryption(): string;

    public function getAuthentication(): string;

    public function getUsername(): string;
    
    public function getPHPImapString(): string;
    
    public function isValidServer(): ?bool;
    
    public function setValidServer($validity): self;
    
    public function isValidLogin(): ?bool;
    
    public function setValidLogin($validity): self;
}
