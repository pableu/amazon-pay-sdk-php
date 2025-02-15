<?php
namespace AmazonPay;

/* Class HttpCurl
 * Handles Curl POST function for all requests
 */

class HttpCurl implements HttpCurlInterface
{
    private $config = array();
    private $header = false;
    private $accessToken = null;
    private $curlResponseInfo = null;
    private $headerArray = array();

    /* Takes user configuration array as input
     * Takes configuration for API call or IPN config
     */

    public function __construct($config = null)
    {
        $this->config = $config;
    }

    /* Setter for boolean header to get the user info */

    public function setHttpHeader()
    {
        $this->header = true;
    }

    /* Setter for Access token to get the user info */

    public function setAccessToken($accesstoken)
    {
        $this->accessToken = $accesstoken;
    }

    /* Add the common Curl Parameters to the curl handler $ch
     * Also checks for optional parameters if provided in the config
     * config['cabundle_file']
     * config['proxy_port']
     * config['proxy_host']
     * config['proxy_username']
     * config['proxy_password']
     */

    protected function commonCurlParams($url,$userAgent)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!is_null($this->config['cabundle_file'])) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->config['cabundle_file']);
        }

        if (!empty($userAgent))
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

        if ($this->config['proxy_host'] != null && $this->config['proxy_port'] != -1) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['proxy_host'] . ':' . $this->config['proxy_port']);
        }

        if ($this->config['proxy_username'] != null && $this->config['proxy_password'] != null) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['proxy_username'] . ':' . $this->config['proxy_password']);
        }

        return $ch;
    }

    /* POST using curl for the following situations
     * 1. API calls
     * 2. IPN certificate retrieval
     * 3. Get User Info
     */

    public function httpPost($url, $userAgent = null, $parameters = null)
    {
        $ch = $this->commonCurlParams($url,$userAgent);
      
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = $this->execute($ch);
        return $response;
    }

    /* GET using curl for the following situations
     * 1. IPN certificate retrieval
     * 2. Get User Info
     */

    public function httpGet($url, $userAgent = null)
    {
        $ch = $this->commonCurlParams($url,$userAgent);

        // Setting the HTTP header with the Access Token only for Getting user info
        if ($this->header) {
            $this->headerArray[] = 'x-amz-access-token : ' . $this->accessToken;
        }

        $response = $this->execute($ch);
        return $response;
    }

    /* Execute Curl request */
    /* Protected because will be used by PSP module */
    protected function execute($ch)
    {
        $response = '';

        // Ensure we never send the "Expect: 100-continue" header
        $this->headerArray[] = 'Expect:';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headerArray);

        $response = curl_exec($ch);
        if ($response === false) {
            $error_msg = "Unable to post request, underlying exception of " . curl_error($ch);
            curl_close($ch);
            throw new \Exception($error_msg);
        } else {
            $this->curlResponseInfo = curl_getinfo($ch);
        }
        curl_close($ch);
        return $response;
    }

    /* Get the output of Curl Getinfo */

    public function getCurlResponseInfo()
    {
        return $this->curlResponseInfo;
    }
}
