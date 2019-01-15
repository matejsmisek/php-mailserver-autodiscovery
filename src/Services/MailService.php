<?php

namespace Balgor\MailServerAutodiscovery\Services;

/**
 * Description of MailServer
 *
 * @author matej.smisek
 */
abstract class MailService implements MailServiceInterface
{

    /**
     * Name of E-mail server service
     * 
     * @var string
     */
    protected $name;

    /**
     *
     * @var MailServerInterface
     */
    protected $incomingServer;

    /**
     *
     * @var MailServerInterface
     */
    protected $outgoingServer;

    public function getIncomingServer(): ?MailServerInterface
    {
        
    }

    public function getName(): ?string
    {
        
    }

    public function getOutgoingServer(): ?MailServerInterface
    {
        
    }

}
