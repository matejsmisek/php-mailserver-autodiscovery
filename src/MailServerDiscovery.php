<?php

namespace Balgor\MailServerAutodiscovery;

use App\Module\Services\MailServiceInterface;
use App\Module\Services\MailServerInterface;
use App\Module\Services\Mozzila\MozzilaNotationMailService;
use App\Module\Services\Guess\GuessedMailService;
use App\Module\Services\Outlook\OutlookNotationMailService;

/**
 * Description of MailServerDiscovery
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

    public function __construct($options = [])
    {
        $this->options = array_merge(self::DEFAULTS, $options);
    }

    public function discover(string $email): MailServiceInterface
    {
        if (($filtered = filter_var($email, FILTER_SANITIZE_EMAIL)) === false) {
            throw \RuntimeException('Not an email');
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
        if (($service = $this->getOutlookAutodiscover($email, $domain)) !== null) {
            return $service;
        }
        return $this->guessService($email);
    }

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

    protected function getMozzilaAutoconfig($email, $domain)
    {
        $request = Request::create()->setUrl(sprintf(self::URL_MOZZILA, $domain, $email));
        if (($data = $request->send()) !== false && ($xml = $this->createXml($data, $email)) !== null) {
            $service = new MozzilaNotationMailService();
            $service->parseXml($xml, $this->options[self::OPTION_PREFERRED_TYPE]);
            return $service;
        }
        return null;
    }

    protected function getMozzilaAutoconfigFromMxRecord($email, $domain)
    {
        $mx = $this->getMxRecord($domain);
        if (empty($mx)) {
            return null;
        }
        $target = $mx['target'];
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

    protected function getOutlookAutodiscover($email, $domain)
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

    protected function guessService($email)
    {
        $service = new GuessedMailService();
        $service->guessFromEmail($email, $this->options[self::OPTION_PREFERRED_TYPE]);
        return $service;
    }

}
