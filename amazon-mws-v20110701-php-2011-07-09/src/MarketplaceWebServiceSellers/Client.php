<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     MarketplaceWebServiceSellers
 *  @copyright   Copyright 2009 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2011-07-01
 */
/******************************************************************************* 
 *  
 *  Marketplace Web Service Sellers PHP5 Library
 *  Generated: Tue Jul 05 17:50:53 GMT 2011
 * 
 */

/**
 *  @see MarketplaceWebServiceSellers_Interface
 */
require_once ('MarketplaceWebServiceSellers/Interface.php');

/**
 * This contains the Sellers section of the Marketplace Web Service.
 * 
 * MarketplaceWebServiceSellers_Client is an implementation of MarketplaceWebServiceSellers
 *
 */
class MarketplaceWebServiceSellers_Client implements MarketplaceWebServiceSellers_Interface
{

    const SERVICE_VERSION = '2011-07-01';
    const MWS_CLIENT_VERSION = '2011-07-09';

    /** @var string */
    private  $_awsAccessKeyId = null;

    /** @var string */
    private  $_awsSecretAccessKey = null;

    /** @var array */
    private  $_config = array ('ServiceURL' => null,
                               'UserAgent' => 'MarketplaceWebServiceSellers PHP5 Library',
                               'SignatureVersion' => 2,
                               'SignatureMethod' => 'HmacSHA256',
                               'ProxyHost' => null,
                               'ProxyPort' => -1,
                               'MaxErrorRetry' => 3
                               );

    /**
     * Construct new Client
     *
     * @param string $awsAccessKeyId AWS Access Key ID
     * @param string $awsSecretAccessKey AWS Secret Access Key
     * @param array $config configuration options.
     * Valid configuration options are:
     * <ul>
     * <li>ServiceURL</li>
     * <li>UserAgent</li>
     * <li>SignatureVersion</li>
     * <li>TimesRetryOnError</li>
     * <li>ProxyHost</li>
     * <li>ProxyPort</li>
     * <li>MaxErrorRetry</li>
     * </ul>
     */

    public function __construct($awsAccessKeyId, $awsSecretAccessKey, $applicationName, $applicationVersion, $config = null)
    {
        iconv_set_encoding('output_encoding', 'UTF-8');
        iconv_set_encoding('input_encoding', 'UTF-8');
        iconv_set_encoding('internal_encoding', 'UTF-8');

        $this->_awsAccessKeyId = $awsAccessKeyId;
        $this->_awsSecretAccessKey = $awsSecretAccessKey;
        if (!is_null($config)) $this->_config = array_merge($this->_config, $config);
        
        $this->setUserAgentHeader($applicationName, $applicationVersion);
    }
    
    private function setUserAgentHeader(
    	$applicationName,
    	$applicationVersion,
    	$attributes = null) {

        if (is_null($attributes)) {
        	$attributes = array ();
        }
        
        $this->_config['UserAgent'] = 
        	$this->constructUserAgentHeader($applicationName, $applicationVersion, $attributes);
    }
    
    private function constructUserAgentHeader($applicationName, $applicationVersion, $attributes = null) {
    	if (is_null($applicationName) || $applicationName === "") {
    		throw new InvalidArgumentException('$applicationName cannot be null');
    	}
    	
    	if (is_null($applicationVersion) || $applicationVersion === "") {
    		throw new InvalidArgumentException('$applicationVersion cannot be null');
    	}
    	
    	$userAgent = 
    		$this->quoteApplicationName($applicationName)
    		. '/'
    		. $this->quoteApplicationVersion($applicationVersion);
    		
        $userAgent .= ' (';
        $userAgent .= 'Language=PHP/' . phpversion();
        $userAgent .= '; ';
        $userAgent .= 'Platform=' . php_uname('s') . '/' . php_uname('m') . '/' . php_uname('r');
        $userAgent .= '; ';
        $userAgent .= 'MWSClientVersion=' . self::MWS_CLIENT_VERSION;
        
        foreach ($attributes as $key => $value) {
        	if (empty($value)) {
        		throw new InvalidArgumentException("Value for $key cannot be null or empty.");
        	}
        	
        	$userAgent .= '; '
        	    . $this->quoteAttributeName($key)
        	    . '='
        	    . $this->quoteAttributeValue($value);
        }
        
        $userAgent .= ')';
        
        return $userAgent;
    }
    
   /**
    * Collapse multiple whitespace characters into a single ' ' character.
    * @param $s
    * @return string
    */
   private function collapseWhitespace($s) {
       return preg_replace('/ {2,}|\s/', ' ', $s);
   }

    /**
     * Collapse multiple whitespace characters into a single ' ' and backslash escape '\',
     * and '/' characters from a string.
     * @param $s
     * @return string
     */
    private function quoteApplicationName($s) {
	    $quotedString = $this->collapseWhitespace($s);
	    $quotedString = preg_replace('/\\\\/', '\\\\\\\\', $quotedString);
	    $quotedString = preg_replace('/\//', '\\/', $quotedString);
	
	    return $quotedString;
    }

    /**
     * Collapse multiple whitespace characters into a single ' ' and backslash escape '\',
     * and '(' characters from a string.
     *
     * @param $s
     * @return string
     */
    private function quoteApplicationVersion($s) {
	    $quotedString = $this->collapseWhitespace($s);
	    $quotedString = preg_replace('/\\\\/', '\\\\\\\\', $quotedString);
	    $quotedString = preg_replace('/\\(/', '\\(', $quotedString);
	
	    return $quotedString;
    }

    /**
     * Collapse multiple whitespace characters into a single ' ' and backslash escape '\',
     * and '=' characters from a string.
     *
     * @param $s
     * @return unknown_type
     */
    private function quoteAttributeName($s) {
	    $quotedString = $this->collapseWhitespace($s);
	    $quotedString = preg_replace('/\\\\/', '\\\\\\\\', $quotedString);
	    $quotedString = preg_replace('/\\=/', '\\=', $quotedString);
	
	    return $quotedString;
    }

    /**
     * Collapse multiple whitespace characters into a single ' ' and backslash escape ';', '\',
     * and ')' characters from a string.
     *
     * @param $s
     * @return unknown_type
     */
    private function quoteAttributeValue($s) {
	    $quotedString = $this->collapseWhitespace($s);
	    $quotedString = preg_replace('/\\\\/', '\\\\\\\\', $quotedString);
	    $quotedString = preg_replace('/\\;/', '\\;', $quotedString);
	    $quotedString = preg_replace('/\\)/', '\\)', $quotedString);
	
	    return $quotedString;
	}

    // Public API ------------------------------------------------------------//


            
    /**
     * List Marketplace Participations 
     * This operation can be used to list all Marketplaces that a seller can sell in.
     * The operation returns a List of Participation elements and a List of Marketplace
     * elements. The SellerId is the only parameter required by this operation.
     * 
     * @see http://docs.amazonwebservices.com/${docPath}ListMarketplaceParticipations.html
     * @param mixed $request array of parameters for MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsRequest request
     * or MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsRequest object itself
     * @see MarketplaceWebServiceSellers_Model_ListMarketplaceParticipations
     * @return MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsResponse MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsResponse
     *
     * @throws MarketplaceWebServiceSellers_Exception
     */
    public function listMarketplaceParticipations($request)
    {
        if (!$request instanceof MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsRequest) {
            require_once ('MarketplaceWebServiceSellers/Model/ListMarketplaceParticipationsRequest.php');
            $request = new MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsRequest($request);
        }
        require_once ('MarketplaceWebServiceSellers/Model/ListMarketplaceParticipationsResponse.php');
        return MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsResponse::fromXML($this->_invoke($this->_convertListMarketplaceParticipations($request)));
    }


            
    /**
     * List Marketplace Participations By Next Token 
     * If ListMarketplaces cannot return all the marketplaces in one go, it will
     * provide a nextToken.  That nextToken can be used with this operation to
     * retrieve the next batch of Marketplaces for that SellerId.
     * 
     * @see http://docs.amazonwebservices.com/${docPath}ListMarketplaceParticipationsByNextToken.html
     * @param mixed $request array of parameters for MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenRequest request
     * or MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenRequest object itself
     * @see MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextToken
     * @return MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenResponse MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenResponse
     *
     * @throws MarketplaceWebServiceSellers_Exception
     */
    public function listMarketplaceParticipationsByNextToken($request)
    {
        if (!$request instanceof MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenRequest) {
            require_once ('MarketplaceWebServiceSellers/Model/ListMarketplaceParticipationsByNextTokenRequest.php');
            $request = new MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenRequest($request);
        }
        require_once ('MarketplaceWebServiceSellers/Model/ListMarketplaceParticipationsByNextTokenResponse.php');
        return MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsByNextTokenResponse::fromXML($this->_invoke($this->_convertListMarketplaceParticipationsByNextToken($request)));
    }


            
    /**
     * Get Service Status 
     * Returns the service status of a particular MWS API section. The operation
     * takes no input. All API sections within the API are required to implement this operation.
     * 
     * @see http://docs.amazonwebservices.com/${docPath}GetServiceStatus.html
     * @param mixed $request array of parameters for MarketplaceWebServiceSellers_Model_GetServiceStatusRequest request
     * or MarketplaceWebServiceSellers_Model_GetServiceStatusRequest object itself
     * @see MarketplaceWebServiceSellers_Model_GetServiceStatus
     * @return MarketplaceWebServiceSellers_Model_GetServiceStatusResponse MarketplaceWebServiceSellers_Model_GetServiceStatusResponse
     *
     * @throws MarketplaceWebServiceSellers_Exception
     */
    public function getServiceStatus($request)
    {
        if (!$request instanceof MarketplaceWebServiceSellers_Model_GetServiceStatusRequest) {
            require_once ('MarketplaceWebServiceSellers/Model/GetServiceStatusRequest.php');
            $request = new MarketplaceWebServiceSellers_Model_GetServiceStatusRequest($request);
        }
        require_once ('MarketplaceWebServiceSellers/Model/GetServiceStatusResponse.php');
        return MarketplaceWebServiceSellers_Model_GetServiceStatusResponse::fromXML($this->_invoke($this->_convertGetServiceStatus($request)));
    }

        // Private API ------------------------------------------------------------//

    /**
     * Invoke request and return response
     */
    private function _invoke(array $parameters)
    {
        $actionName = $parameters["Action"];
        $response = array();
        $responseBody = null;
        $statusCode = 200;

        /* Submit the request and read response body */
        try {
        	
        	if (empty($this->_config['ServiceURL'])) {
        		throw new MarketplaceWebServiceSellers_Exception(
        			array ('ErrorCode' => 'InvalidServiceURL',
        				   'Message' => "Missing serviceUrl configuration value. You may obtain a list of valid MWS URLs by consulting the MWS Developer's Guide, or reviewing the sample code published along side this library."));
        	}

            /* Add required request parameters */
            $parameters = $this->_addRequiredParameters($parameters);

            $shouldRetry = true;
            $retries = 0;
            do {
                try {
                        $response = $this->_httpPost($parameters);
                        if ($response['Status'] === 200) {
                            $shouldRetry = false;
                        } else {
                            if ($response['Status'] === 500 || $response['Status'] === 503) {
                            	
                            	require_once('MarketplaceWebServiceSellers/Model/ErrorResponse.php');
                            	$errorResponse = MarketplaceWebServiceSellers_Model_ErrorResponse::fromXML($response['ResponseBody']);
                            	
                            	$errors = $errorResponse->getError();
                            	$shouldRetry = ($errors[0]->getCode() === 'RequestThrottled') ? false : true;
                            	
                            	if ($shouldRetry) {
                            		$this->_pauseOnRetry(++$retries, $response['Status']);
                            	} else {
                            		throw $this->_reportAnyErrors($response['ResponseBody'], $response['Status']);
                            	}
                            	
//                                $shouldRetry = true;
//                                $this->_pauseOnRetry(++$retries, $response['Status']);
                            } else {
                                throw $this->_reportAnyErrors($response['ResponseBody'], $response['Status']);
                            }
                       }
                /* Rethrow on deserializer error */
                } catch (Exception $e) {
                    require_once ('MarketplaceWebServiceSellers/Exception.php');
                    if ($e instanceof MarketplaceWebServiceSellers_Exception) {
                        throw $e;
                    } else {
                        require_once ('MarketplaceWebServiceSellers/Exception.php');
                        throw new MarketplaceWebServiceSellers_Exception(array('Exception' => $e, 'Message' => $e->getMessage()));
                    }
                }

            } while ($shouldRetry);

        } catch (MarketplaceWebServiceSellers_Exception $se) {
            throw $se;
        } catch (Exception $t) {
            throw new MarketplaceWebServiceSellers_Exception(array('Exception' => $t, 'Message' => $t->getMessage()));
        }

        return $response['ResponseBody'];
    }




    /**
     * Look for additional error strings in the response and return formatted exception
     */
    private function _reportAnyErrors($responseBody, $status, Exception $e =  null)
    {
        $ex = null;
        if (!is_null($responseBody) && strpos($responseBody, '<') === 0) {
            if (preg_match('@<RequestId>(.*)</RequestId>.*<Error><Code>(.*)</Code><Message>(.*)</Message></Error>.*(<Error>)?@mi',
                $responseBody, $errorMatcherOne)) {

                $requestId = $errorMatcherOne[1];
                $code = $errorMatcherOne[2];
                $message = $errorMatcherOne[3];

                require_once ('MarketplaceWebServiceSellers/Exception.php');
                $ex = new MarketplaceWebServiceSellers_Exception(array ('Message' => $message, 'StatusCode' => $status, 'ErrorCode' => $code,
                                                           'ErrorType' => 'Unknown', 'RequestId' => $requestId, 'XML' => $responseBody));

            } elseif (preg_match('@<Error><Code>(.*)</Code><Message>(.*)</Message></Error>.*(<Error>)?.*<RequestID>(.*)</RequestID>@mi',
                $responseBody, $errorMatcherTwo)) {

                $code = $errorMatcherTwo[1];
                $message = $errorMatcherTwo[2];
                $requestId = $errorMatcherTwo[4];
                require_once ('MarketplaceWebServiceSellers/Exception.php');
                $ex = new MarketplaceWebServiceSellers_Exception(array ('Message' => $message, 'StatusCode' => $status, 'ErrorCode' => $code,
                                                              'ErrorType' => 'Unknown', 'RequestId' => $requestId, 'XML' => $responseBody));
            } elseif (preg_match('@<Error><Type>(.*)</Type><Code>(.*)</Code><Message>(.*)</Message>.*</Error>.*(<Error>)?.*<RequestId>(.*)</RequestId>@mi',
                $responseBody, $errorMatcherThree)) {

                $type = $errorMatcherThree[1];
                $code = $errorMatcherThree[2];
                $message = $errorMatcherThree[3];
                $requestId = $errorMatcherThree[5];
                require_once ('MarketplaceWebServiceSellers/Exception.php');
                $ex = new MarketplaceWebServiceSellers_Exception(array ('Message' => $message, 'StatusCode' => $status, 'ErrorCode' => $code,
                                                              'ErrorType' => $type, 'RequestId' => $requestId, 'XML' => $responseBody));

            } else {
                require_once ('MarketplaceWebServiceSellers/Exception.php');
                $ex = new MarketplaceWebServiceSellers_Exception(array('Message' => 'Internal Error', 'StatusCode' => $status));
            }
        } else {
            require_once ('MarketplaceWebServiceSellers/Exception.php');
            $ex = new MarketplaceWebServiceSellers_Exception(array('Message' => 'Internal Error', 'StatusCode' => $status));
        }
        return $ex;
    }



    /**
     * Perform HTTP post with exponential retries on error 500 and 503
     *
     */
    private function _httpPost(array $parameters)
    {

        $query = $this->_getParametersAsString($parameters);
        $url = parse_url ($this->_config['ServiceURL']);
	$uri = array_key_exists('path', $url) ? $url['path'] : null;
        if (!isset ($uri)) {
                $uri = "/";
        }
        $post  = "POST " . $uri . " HTTP/1.0\r\n";
        $post .= "Host: " . $url['host'] . "\r\n";
        $post .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
        $post .= "Content-Length: " . strlen($query) . "\r\n";
        $post .= "User-Agent: " . $this->_config['UserAgent'] . "\r\n";
        $post .= "\r\n";
        $post .= $query;
        $port = array_key_exists('port',$url) ? $url['port'] : null;
        $scheme = '';

        switch ($url['scheme']) {
            case 'https':
                $scheme = 'ssl://';
                $port = $port === null ? 443 : $port;
                break;
            default:
                $scheme = '';
                $port = $port === null ? 80 : $port;
        }

        $response = '';
        if ($socket = @fsockopen($scheme . $url['host'], $port, $errno, $errstr, 10)) {

            fwrite($socket, $post);

            while (!feof($socket)) {
                $response .= fgets($socket, 1160);
            }
            fclose($socket);

            list($other, $responseBody) = explode("\r\n\r\n", $response, 2);
            $other = preg_split("/\r\n|\n|\r/", $other);
            list($protocol, $code, $text) = explode(' ', trim(array_shift($other)), 3);
        } else {
            throw new Exception ("Unable to establish connection to host " . $url['host'] . " $errstr");
        }


        return array ('Status' => (int)$code, 'ResponseBody' => $responseBody);
    }

    /**
     * Exponential sleep on failed request
     * @param retries current retry
     * @throws MarketplaceWebServiceSellers_Exception if maximum number of retries has been reached
     */
    private function _pauseOnRetry($retries, $status)
    {
        if ($retries <= $this->_config['MaxErrorRetry']) {
            $delay = (int) (pow(4, $retries) * 100000) ;
            usleep($delay);
        } else {
            require_once ('MarketplaceWebServiceSellers/Exception.php');
            throw new MarketplaceWebServiceSellers_Exception (array ('Message' => "Maximum number of retry attempts reached :  $retries", 'StatusCode' => $status));
        }
    }

    /**
     * Add authentication related and version parameters
     */
    private function _addRequiredParameters(array $parameters)
    {
        $parameters['AWSAccessKeyId'] = $this->_awsAccessKeyId;
        $parameters['Timestamp'] = $this->_getFormattedTimestamp();
        $parameters['Version'] = self::SERVICE_VERSION;
        $parameters['SignatureVersion'] = $this->_config['SignatureVersion'];
        if ($parameters['SignatureVersion'] > 1) {
            $parameters['SignatureMethod'] = $this->_config['SignatureMethod'];
        }
        $parameters['Signature'] = $this->_signParameters($parameters, $this->_awsSecretAccessKey);

        return $parameters;
    }

    /**
     * Convert paremeters to Url encoded query string
     */
    private function _getParametersAsString(array $parameters)
    {
        $queryParameters = array();
        foreach ($parameters as $key => $value) {
            $queryParameters[] = $key . '=' . $this->_urlencode($value);
        }
        return implode('&', $queryParameters);
    }


    /**
     * Computes RFC 2104-compliant HMAC signature for request parameters
     * Implements AWS Signature, as per following spec:
     *
     * If Signature Version is 0, it signs concatenated Action and Timestamp
     *
     * If Signature Version is 1, it performs the following:
     *
     * Sorts all  parameters (including SignatureVersion and excluding Signature,
     * the value of which is being created), ignoring case.
     *
     * Iterate over the sorted list and append the parameter name (in original case)
     * and then its value. It will not URL-encode the parameter values before
     * constructing this string. There are no separators.
     *
     * If Signature Version is 2, string to sign is based on following:
     *
     *    1. The HTTP Request Method followed by an ASCII newline (%0A)
     *    2. The HTTP Host header in the form of lowercase host, followed by an ASCII newline.
     *    3. The URL encoded HTTP absolute path component of the URI
     *       (up to but not including the query string parameters);
     *       if this is empty use a forward '/'. This parameter is followed by an ASCII newline.
     *    4. The concatenation of all query string components (names and values)
     *       as UTF-8 characters which are URL encoded as per RFC 3986
     *       (hex characters MUST be uppercase), sorted using lexicographic byte ordering.
     *       Parameter names are separated from their values by the '=' character
     *       (ASCII character 61), even if the value is empty.
     *       Pairs of parameter and values are separated by the '&' character (ASCII code 38).
     *
     */
    private function _signParameters(array $parameters, $key) {
        $signatureVersion = $parameters['SignatureVersion'];
        $algorithm = "HmacSHA1";
        $stringToSign = null;
        if (2 === $signatureVersion) {
            $algorithm = $this->_config['SignatureMethod'];
            $parameters['SignatureMethod'] = $algorithm;
            $stringToSign = $this->_calculateStringToSignV2($parameters);
        } else {
            throw new Exception("Invalid Signature Version specified");
        }
        return $this->_sign($stringToSign, $key, $algorithm);
    }

    /**
     * Calculate String to Sign for SignatureVersion 2
     * @param array $parameters request parameters
     * @return String to Sign
     */
    private function _calculateStringToSignV2(array $parameters) {
        $data = 'POST';
        $data .= "\n";
        $endpoint = parse_url ($this->_config['ServiceURL']);
        $data .= $endpoint['host'];
        $data .= "\n";
        $uri = array_key_exists('path', $endpoint) ? $endpoint['path'] : null;
        if (!isset ($uri)) {
        	$uri = "/";
        }
		$uriencoded = implode("/", array_map(array($this, "_urlencode"), explode("/", $uri)));
        $data .= $uriencoded;
        $data .= "\n";
        uksort($parameters, 'strcmp');
        $data .= $this->_getParametersAsString($parameters);
        return $data;
    }

    private function _urlencode($value) {
		return str_replace('%7E', '~', rawurlencode($value));
    }


    /**
     * Computes RFC 2104-compliant HMAC signature.
     */
    private function _sign($data, $key, $algorithm)
    {
        if ($algorithm === 'HmacSHA1') {
            $hash = 'sha1';
        } else if ($algorithm === 'HmacSHA256') {
            $hash = 'sha256';
        } else {
            throw new Exception ("Non-supported signing method specified");
        }
        return base64_encode(
            hash_hmac($hash, $data, $key, true)
        );
    }


    /**
     * Formats date as ISO 8601 timestamp
     */
    private function _getFormattedTimestamp()
    {
        return gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
    }


    /**
     * Formats date as ISO 8601 timestamp
     */
    private function getFormattedTimestamp($dateTime)
    {
	    return $dateTime->format(DATE_ISO8601);
    }

                                                
    /**
     * Convert ListMarketplaceParticipationsRequest to name value pairs
     */
    private function _convertListMarketplaceParticipations($request) {
        
        $parameters = array();
        $parameters['Action'] = 'ListMarketplaceParticipations';
        if ($request->isSetSellerId()) {
            $parameters['SellerId'] =  $request->getSellerId();
        }

        return $parameters;
    }
        
                                                
    /**
     * Convert ListMarketplaceParticipationsByNextTokenRequest to name value pairs
     */
    private function _convertListMarketplaceParticipationsByNextToken($request) {
        
        $parameters = array();
        $parameters['Action'] = 'ListMarketplaceParticipationsByNextToken';
        if ($request->isSetSellerId()) {
            $parameters['SellerId'] =  $request->getSellerId();
        }
        if ($request->isSetNextToken()) {
            $parameters['NextToken'] =  $request->getNextToken();
        }

        return $parameters;
    }
        
                                                
    /**
     * Convert GetServiceStatusRequest to name value pairs
     */
    private function _convertGetServiceStatus($request) {
        
        $parameters = array();
        $parameters['Action'] = 'GetServiceStatus';
        if ($request->isSetSellerId()) {
            $parameters['SellerId'] =  $request->getSellerId();
        }

        return $parameters;
    }
        
                                                                                        
}
