<?php

namespace Balgor\MailServerAutodiscovery;

/**
 * Description of Poster
 *
 * @author Matej Smisek
 */
class Request
{

    const CONTENT_JSON = 'json';
    const CONTENT_XML = 'xml';
    
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PURGE = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';

    protected $returnCode = 0;
    protected $disableSSL = false;
    protected $method = 'GET';
    protected $url;
    protected $data = [];
    protected $headers = [];
    protected $contentType = false;
    protected $authUser = false;
    protected $authPassword = false;

    protected $error;
    protected $response;
    
    /**
     * Creates an instance of the class.
     *
     * @return Request
     */
    public static function create()
    {
        return new static();
    }
    
    public function send()
    {
        $curl_connection = curl_init();
        $curl_headers = [];

        switch ($this->method) {
            case self::METHOD_GET:
                if ($this->data !== false && !empty($this->data)) {//                   
                    $url = sprintf("%s?%s", $url, http_build_query($this->data));
                }
                break;
            case self::METHOD_POST:
                curl_setopt($curl_connection, CURLOPT_CUSTOMREQUEST, self::METHOD_POST);
                curl_setopt($curl_connection, CURLOPT_POST, true);
                if ($this->data !== false) {
                    curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $this->data);
                    $curl_headers[] = 'Content-Length: ' . strlen($this->data);
                }
                break;
            case self::METHOD_PUT:
                curl_setopt($curl_connection, CURLOPT_CUSTOMREQUEST, self::METHOD_PUT);
                curl_setopt($curl_connection, CURLOPT_PUT, true);
                if ($this->data !== false) {
                    curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $this->data);
                    $curl_headers[] = 'Content-Length: ' . strlen($this->data);
                }
                break;
            case self::METHOD_PATCH:
                curl_setopt($curl_connection, CURLOPT_CUSTOMREQUEST, self::METHOD_PATCH);
                if ($this->data !== false) {
                    curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $this->data);
                    $curl_headers[] = 'Content-Length: ' . strlen($this->data);
                }
                break;
            case self::METHOD_DELETE:
                curl_setopt($curl_connection, CURLOPT_CUSTOMREQUEST, self::METHOD_DELETE);
                if ($this->data !== false && !empty($this->data)) {
                    curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $this->data);
                    $curl_headers[] = 'Content-Length: ' . strlen($this->data);
                }
                break;
        }


        switch ($this->contentType) {
            case self::CONTENT_JSON:
                $curl_headers[] = 'Content-Type: application/json';
                break;
            case self::CONTENT_XML:
                $curl_headers[] = 'Content-Type: text/xml';
                break;
        }

        curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_connection, CURLOPT_URL, $this->url);
        curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
        
//        curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0");
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
        if ($this->disableSSL === true) {
            curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($this->authUser !== false && $this->authPassword !== false) {
            curl_setopt($curl_connection, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl_connection, CURLOPT_USERPWD, $this->authUser . ":" . $this->authPassword);
        }

        if ($this->headers !== false) {
            foreach ($this->headers as $header => $value) {
                $curl_headers[] = $header . ': ' . $value;
            }
        }
        if (!empty($curl_headers)) {
            curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $curl_headers);
        }

//          curl_setopt($curl_connection, CURLOPT_VERBOSE, true);

//        curl_setopt($curl_connection, CURLINFO_HEADER_OUT, true);

        $this->response = curl_exec($curl_connection);
        $this->returnCode = curl_getinfo($curl_connection, CURLINFO_HTTP_CODE);
        
//        $this->_debug = curl_getinfo($curl_connection);
        if (curl_errno($curl_connection) !== 0) {
            $this->error = curl_error($curl_connection);
            return false;
        }
        curl_close($curl_connection);
        return $this->response;
    }

    public function disableSSL()
    {
        $this->disableSSL = true;
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function setAuthUser($authUser)
    {
        $this->authUser = $authUser;
        return $this;
    }

    public function setAuthPassword($authPassword)
    {
        $this->authPassword = $authPassword;
        return $this;
    }

    public function getReturnCode()
    {
        return $this->returnCode;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getResponse()
    {
        return $this->response;
    }



}
