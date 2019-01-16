<?php

namespace Balgor\MailServerAutodiscovery;

/**
 * PHP CURL wrapper to allow easily create API requests
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

    /**
     * Creates CURL connections and executes it
     * 
     * 
     * @return mixed returns response body or FALSE on failure
     */
    public function send()
    {
        $curl_connection = curl_init();
        $curl_headers = [];


        // Properly set request data based on HTTP method
        switch ($this->method) {
            case self::METHOD_GET:
                if ($this->data !== false && !empty($this->data)) {//                   
                    $url = sprintf("%s?%s", $url, http_build_query($this->data));
                }
                break;
            case self::METHOD_POST:
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
            case self::METHOD_DELETE:
                curl_setopt($curl_connection, CURLOPT_CUSTOMREQUEST, $this->method);
                curl_setopt($curl_connection, CURLOPT_POST, true);
                if ($this->data !== false) {
                    curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $this->data);
                    $curl_headers[] = 'Content-Length: ' . strlen($this->data);
                }
                break;            
        }

        // Set content type based on shortcut
        switch ($this->contentType) {
            case self::CONTENT_JSON:
                $curl_headers[] = 'Content-Type: application/json';
                break;
            case self::CONTENT_XML:
                $curl_headers[] = 'Content-Type: text/xml';
                break;
        }

        //Follow any redirects
        curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_connection, CURLOPT_URL, $this->url);
        curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);

        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
        
        //If ssl is disabled, skip certificate verification
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



        $this->response = curl_exec($curl_connection);
        $this->returnCode = curl_getinfo($curl_connection, CURLINFO_HTTP_CODE);

        if (curl_errno($curl_connection) !== 0) {
            $this->error = curl_error($curl_connection);
            curl_close($curl_connection);
            return false;
        }
        curl_close($curl_connection);
        return $this->response;
    }
    /**
     * Disable SSL certificate verification to allow connection to self-signed connections. Use wisely
     * 
     * @return $this
     */
    public function disableSSL()
    {
        $this->disableSSL = true;
        return $this;
    }

    /**
     * Sets request method, default is GET
     * 
     * @param string $method which method to use (GET, POST, PUT, PATCH, DELETE)
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    
    /**
     * Which URL to send request
     * 
     * @param string $url URL to request to
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Request body data
     * 
     * Use array if using standard form-part content type
     * Use string if sending xml / json data
     * 
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    
    /**
     * Manually sets request headers.
     * Use associative array in form of 'header-name' => 'content'
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    
    /**
     * Shortcut to set content-type header.
     * 
     * So far it supports XML or JSON data format
     * 
     * @param string $contentType  XML / JSON
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    
    /**
     * Sets username for basic auth login
     * 
     * if password is not set, auth will not be enabled
     * 
     * @param string $authUser
     * @return $this
     */
    public function setAuthUser($authUser)
    {
        $this->authUser = $authUser;
        return $this;
    }
    /**
     * Sets password for basic auth login
     * 
     * if username is not set, auth will not be enabled
     * 
     * @param type $authPassword
     * @return $this
     */
    public function setAuthPassword($authPassword)
    {
        $this->authPassword = $authPassword;
        return $this;
    }

    
    /**
     * HTTP response code
     * 
     * equals 0 if request has not been send() yet
     * 
     * 
     * @return int
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }

    /**
     * Gets last CURL error
     * 
     * equals null if request has not been send() yet
     * 
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
    
    /**
     * Gets CURL response body
     * 
     * equals null if request has not been send() yet
     * 
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

}
