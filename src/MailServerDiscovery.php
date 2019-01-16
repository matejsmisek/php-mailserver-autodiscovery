<?php
namespace Balgor\MailServerAutodiscovery;

use Balgor\MailServerAutodiscovery\Services\MailServiceInterface;
use Balgor\MailServerAutodiscovery\Services\MailServerInterface;
use Balgor\MailServerAutodiscovery\Services\Mozzila\MozzilaNotationMailService;
use Balgor\MailServerAutodiscovery\Services\Guess\GuessedMailService;
use Balgor\MailServerAutodiscovery\Services\Outlook\OutlookNotationMailService;

use Balgor\MailServerAutodiscovery\Tester\MailServerTester;

/**
 * 
 * @author matej.smisek
 */
class MailServerDiscovery
{

    const URL_ISPDB = "https://autoconfig.thunderbird.net/v1.1/";
    const URL_MOZZILA = "https://autoconfig.%s/mail/config-v1.1.xml?emailaddress=%s";
    const URL_OUTLOOK_BASE = "https://%s/Autodiscover/Autodiscover.xml";
    const URL_OUTLOOK_SUBDOMAIN = "https://autodiscover.%s/Autodiscover/Autodiscover.xml";
    const DNS_SRV_AUTODISCOVER = '_autodiscover._tcp.';

    protected $options = [];

    const OPTION_PREFERRED_TYPE = 'prefered_type';
    const DEFAULTS = [
        self::OPTION_PREFERRED_TYPE => MailServerInterface::TYPE_IMAP,
    ];

    
    /**
     * Options are:
     *  - OPTION_PREFERRED_TYPE = If autoconfig return both pop3 and imap server, which one is preffered
     *  
     * 
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->options = array_merge(self::DEFAULTS, $options);
    }

    
    /**
     * Tries to fetch email server configuration from provider, if nothing is found, "guesses" configuration based on most secure values (SSL)
     * 
     * Order of in which the check is performed:
     *  - Mozzila ISPDB Database
     *  - Mozzila Autoconfig from domain part of email (after @)
     *  - Mozzila Autoconfig from MX DNS record from domain part of email
     *  - Outlook Autodiscovery which includes fetching SRV records
     * 
     * @param string $email
     * @return MailServiceInterface
     * @throws RuntimeError
     */
    public function discover(string $email): MailServiceInterface
    {
        if (($filtered = filter_var($email, FILTER_VALIDATE_EMAIL)) === false) {
            throw new \Exception('Not an email');
        }
        $domain = substr($email, strrpos($email, '@') + 1);

        if (($service = $this->getISPDBAutoconfig($email, $domain)) !== null) {
            return $service;
        }
        if (($service = $this->getMozzilaAutoconfig($email, $domain)) !== null) {
            return $service;
        }
        if (($service = $this->getMozzilaAutoconfigFromMxRecord($email, $domain)) !== null) {
            return $service;
        }
        if (($service = $this->getOutlookAutodiscovery($email, $domain)) !== null) {
            return $service;
        }
        return $this->guessService($email);
    }

    /**
     * Performs discovery and tries to connect to resulting services.
     * Each server in service has fields validServer set TRUE if server is successfully connected, FALSE if not
     * validLogin then validates user and tries to login, only if server is valid (TRUE). If login test was not performed value will be NULL
     * 
     * WARNING
     * Based on found service configuration this method may send password over plain text connection.
     * If you are concerned, run first discovery and after manual check if SSL is enabled, perform validation separately
     *
     * 
     * @param string $email
     * @param string $password if not provided only checks server connection
     * @return MailServiceInterface configured and tested service
     */
    public function discoverAndValidate(string $email, string $password = null)
    {
        $service = $this->discover($email);
        $validator = new MailServerTester();
        $validator->checkService($service, $password ?? false);
        return $service;
    }
    
    /**
     * Fetches MX records from $domain and returns the one with most priority
     * 
     * null if nothing is found
     * 
     * @param string $domain
     * @return array MX record
     */
    protected function getMxRecord($domain)
    {

        $mxs = dns_get_record($domain, DNS_MX);
        if (empty($mxs)) {
            return null;
        }
        if (count($mxs) > 1) {
            usort($mxs, function ($a, $b) {
                return $a['pri'] > $b['pri'];
            });
        }
        return array_shift($mxs);
    }

    
    /**
     * Searches Mozzila (Thunderbird) ISPDB for any records from email domain
     * 
     * @param string $email
     * @param string $domain
     * @return MozzilaNotationMailService Configured server or null if nothing is found
     */
    protected function getISPDBAutoconfig(string $email, string $domain)
    {
        $request = Request::create()->setUrl(self::URL_ISPDB . $domain);
        if (($data = $request->send()) !== false && ($xml = $this->createXml($data, $email)) !== null) {
            $service = new MozzilaNotationMailService();
            $service->parseXml($xml, $this->options[self::OPTION_PREFERRED_TYPE]);
            return $service;
        }
        return null;
    }
    
    /**
     * Searches email provider autoconfig XML record
     * 
     * @param string $email
     * @param string $domain
     * @return MozzilaNotationMailService Configured server or null if nothing is found
     */
    protected function getMozzilaAutoconfig(string $email,string $domain)
    {
        $request = Request::create()->setUrl(sprintf(self::URL_MOZZILA, $domain, $email));
        if (($data = $request->send()) !== false && ($xml = $this->createXml($data, $email)) !== null) {
            $service = new MozzilaNotationMailService();
            $service->parseXml($xml, $this->options[self::OPTION_PREFERRED_TYPE]);
            return $service;
        }
        return null;
    }

    /**
     * Tries to fetch MX records from email domain and searches it for Mozzila autoconfig
     * If nothing is found, tries to strip mx. and mail. subdomains from MX record and tries again
     * 
     * @param type $email
     * @param type $domain
     * @return MozzilaNotationMailService configured service or null if nothing is found
     */
    protected function getMozzilaAutoconfigFromMxRecord(string $email,string $domain)
    {
        $mx = $this->getMxRecord($domain);
        if (empty($mx)) {
            return null;
        }
        $target = $mx['target'];
        if (($service = $this->getMozzilaAutoconfig($email, $target)) !== null) {
            return $service;
        }
        if (strpos($mx['target'],'mx.') !== false) {
            $target = substr($mx['target'], strpos($mx['target'], 'mx.') + 3);
        }
        if (strpos($mx['target'],'mail.') !== false) {
            $target = substr($mx['target'], strpos($mx['target'], 'mail.') + 5);
        }
        if (($service = $this->getMozzilaAutoconfig($email, $target)) !== null) {
            return $service;
        }
 
        return null;
    }

    
    /**
     * Tries all possible Outlook autodiscovery URLS and return first valid or null if none are found
     * 
     * @param type $email
     * @param type $domain
     * @return OutlookNotationMailService configured service or null if nothing is found
     */
    protected function getOutlookAutodiscovery(string $email,string $domain)
    {
        if (($data = $this->tryOutlookUrls($email, $domain)) === null) {
            return null;
        }

        if (($xml = $this->createXml($data, $email)) !== null) {
            $service = new OutlookNotationMailService();
            $service->parseXml($xml, $this->options[self::OPTION_PREFERRED_TYPE]);
            return $service;
        }
        return null;
    }

    /**
     * Return first valid autodiscovery from array of urls, if there is SRV record found, then its fetched first
     * 
     * @param string $email
     * @param string $domain
     * @return string Raw response on 200 HTTP code or null if nothing is found
     */
    protected function tryOutlookUrls($email, $domain)
    {
        $urls = [
            sprintf(self::URL_OUTLOOK_BASE, $domain),
            sprintf(self::URL_OUTLOOK_SUBDOMAIN, $domain)
        ];
        if (($srvUrl = $this->resolveAutodiscoverDns($domain)) !== null) {
            array_unshift($urls, $srvUrl);
        }
        foreach ($urls as $url) {
            $request = Request::create()->setUrl($url)->setMethod(Request::METHOD_POST)->setContentType(Request::CONTENT_XML);
            // Set request data, usually not needed but simulates Outlook behaviour
            $request->setData('<?xml version="1.0" encoding="utf-8"?>
                    <Autodiscover>
                        <Request>
                            <EMailAddress>' . $email . '</EMailAddress>
                            <AcceptableResponseSchema></AcceptableResponseSchema>
                        </Request>
                    </Autodiscover>');
            $request->send();
            if ($request->getReturnCode() === 200) {
                return $request->getResponse();
            }
        }
        return null;
    }

    /**
     * Pools DNS SRV records for any autodiscovery settings
     * 
     * @param string $domain
     * @return string URL from SRV record
     */
    protected function resolveAutodiscoverDns($domain)
    {
        $srv = dns_get_record(self::DNS_SRV_AUTODISCOVER . $domain, DNS_SRV);
        if (count($srv) === 0) {
            return null;
        }
        if (count($srv) > 1) {
            usort($srv, function ($a, $b) {
                return $a['pri'] > $b['pri'];
            });
        }
        // get first element in array and construct URL
        return sprintf(self::URL_OUTLOOK_BASE, array_shift($srv)['target']);
    }

    
    /**
     * Creates SimpleXMLElement from raw response data, if response is not valid, quitely return null to allow to try other services
     * 
     * @param string $rawXml
     * @param string $email
     * @return \SimpleXMLElement|null
     */
    protected function createXml($rawXml, $email): ?\SimpleXMLElement
    {
        if ($rawXml === false) {
            return null;
        }

        $rawXml = str_replace(['%EMAILADDRESS%', '%EMAILLOCALPART%'], [$email, substr($email, 0, strrpos($email, '@'))], $rawXml);

        $use_internal_errors = libxml_use_internal_errors(true);
        libxml_clear_errors(true);
        $xml = simplexml_load_string($rawXml);
        if ($xml !== false) {
            return $xml;
        }
        return null;
    }

    
    /**
     * Creates simple Guessed service based on usual values for email server, prefers SSL connection
     * 
     * @param string $email
     * @return GuessedMailService configured service
     */
    protected function guessService(string $email)
    {
        $service = new GuessedMailService();
        $service->guessFromEmail($email, $this->options[self::OPTION_PREFERRED_TYPE]);
        return $service;
    }

}
