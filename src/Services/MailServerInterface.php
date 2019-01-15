<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
}
