<?php

class KbAmazonApi
{

    const RETURN_TYPE_ARRAY = 1;
    const RETURN_TYPE_OBJECT = 2;

    private $protocol = 'SOAP';
    private $requestConfig = array();
    private $_sessionKey = 'kbAmz';
    private $responseConfig = array(
        'returnType' => self::RETURN_TYPE_ARRAY,
        'responseGroup' => 'Small',
        'optionalParameters' => array()
    );
    private $possibleLocations = array('de', 'com', 'co.uk', 'ca', 'fr', 'co.jp', 'it', 'cn', 'es', 'in');
    protected $webserviceWsdl = 'http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl';
    protected $webserviceEndpoint = 'https://webservices.amazon.%%COUNTRY%%/onca/%%PROTOCOL%%?Service=AWSECommerceService';

    public function __construct($accessKey, $secretKey, $country = 'com', $associateTag)
    {
        $this->setProtocol();


        $this->webserviceEndpoint = str_replace(
                '%%PROTOCOL%%', strtolower($this->protocol), $this->webserviceEndpoint
        );

        if (empty($accessKey) || empty($secretKey)) {
            throw new Exception('No Access Key or Secret Key has been set');
        }

        $this->requestConfig['accessKey'] = $accessKey;
        $this->requestConfig['secretKey'] = $secretKey;
        $this->associateTag($associateTag);
        $this->country($country);
    }

    private function setProtocol()
    {
        global $amzStore;

        $db_protocol_setting = 'soap';
        if ($db_protocol_setting == 'soap') {
            if (extension_loaded('soap')) {
                $this->protocol = 'SOAP';
            } else {
                $this->protocol = 'XML';
            }
        }

        if ($db_protocol_setting == 'auto') {
            if (!extension_loaded('soap')) {
                $this->protocol = 'XML';
            }
        }

        if ($db_protocol_setting == 'xml') {
            $this->protocol = 'XML';
        }
    }

    public function search($pattern, $nodeId = null)
    {
        if (false === isset($this->requestConfig['category'])) {
            throw new Exception('No Category given: Please set it up before');
        }

        $browseNode = array();
        if (null !== $nodeId && true === $this->validateNodeId($nodeId)) {
            $browseNode = array('BrowseNode' => $nodeId);
        }

        $params = $this->buildRequestParams('ItemSearch', array_merge(
                        array(
            'Keywords' => $pattern,
            'SearchIndex' => $this->requestConfig['category']
                        ), $browseNode
        ));

        return $this->returnData(
                        $this->performTheRequest("ItemSearch", $params)
        );
    }

    function cartThem($selectedItems)
    {
        $result = false;
        if (!empty($selectedItems) && is_array($selectedItems)) {
            if (!isset($_SESSION[$this->_sessionKey]["cartId"])) {
                $firstItem = array_shift($selectedItems);
                $result = $this->cartCreate($firstItem['ASIN'], $firstItem['Quantity']);
            }
            if (count($selectedItems)) {
                foreach ($selectedItems as $item) {
                    $result = $this->cartAdd($item['ASIN'], $item['Quantity']);
                }
            }
        }
        return $result;
    }

    function cartCreate($offerListingId, $quantity = 1)
    {
        if (is_array($offerListingId)) {
            $params = $this->buildRequestParams('CartCreate', array('Items' => $offerListingId));
        } else {
            $params = $this->buildRequestParams('CartCreate', array('Items' =>
                array(
                    'Item' => array('ASIN' => $offerListingId, 'Quantity' => $quantity)
                )
                    )
            );
        }


        $response = $this->returnData(
                $this->performTheRequest("CartCreate", $params)
        );

        $response = $response['Cart'];
        if (isset($response['Request']['Errors'])) {
            return $response;
        }

        $_SESSION[$this->_sessionKey] = array(
            'HMAC' => $response['HMAC'],
            'cartId' => $response['CartId'],
            'PurchaseUrl' => $response['PurchaseURL'],
        );

        return $this->__formatCartItems($response);
    }

    function cartAdd($offerListingId, $quantity = 1, $HMAC = null, $cartId = null)
    {
        if (!$HMAC) {
            $HMAC = $_SESSION[$this->_sessionKey]['HMAC'];
        }
        if (!$cartId) {
            $cartId = $_SESSION[$this->_sessionKey]['cartId'];
        }

        if (!$HMAC || !$cartId) {
            return false;
        }

        $params = $this->buildRequestParams('CartAdd', array(
            'CartId' => $cartId,
            'HMAC' => $HMAC,
            'Items' =>
            array(
                'Item' => array('ASIN' => $offerListingId, 'Quantity' => $quantity)
            )
                )
        );
        $response = $this->returnData(
                $this->performTheRequest("CartAdd", $params)
        );

        if (isset($response['Cart']['Request']['Errors'])) {
            return $response;
        }



        return $this->__formatCartItems($response['Cart']);
    }

    function cartUpdate($cartItemId, $quantity, $HMAC = null, $cartId = null)
    {
        if (!$HMAC) {
            $HMAC = isset($_SESSION[$this->_sessionKey]['HMAC']) ? $_SESSION[$this->_sessionKey]['HMAC'] : '';
        }
        if (!$cartId) {
            $cartId = isset($_SESSION[$this->_sessionKey]['cartId']) ? $_SESSION[$this->_sessionKey]['cartId'] : '';
        }
        if (!$HMAC || !$cartId) {
            return false;
        }

        $params = $this->buildRequestParams('CartModify', array(
            'CartId' => $cartId,
            'HMAC' => $HMAC,
            'Items' =>
            array(
                'Item' => array('CartItemId' => $cartItemId, 'Quantity' => $quantity)
            )
                )
        );

        $response = $this->returnData(
                $this->performTheRequest("CartModify", $params)
        );
        return $this->__formatCartItems($response['Cart']['Request']['CartModifyRequest']);
    }

    function cartGet($HMAC = null, $cartId = null)
    {
        if (!$HMAC) {
            $HMAC = isset($_SESSION[$this->_sessionKey]['HMAC']) ? $_SESSION[$this->_sessionKey]['HMAC'] : '';
        }
        if (!$cartId) {
            $cartId = isset($_SESSION[$this->_sessionKey]['cartId']) ? $_SESSION[$this->_sessionKey]['cartId'] : '';
        }
        if (!$HMAC || !$cartId) {
            return false;
        }

        $params = $this->buildRequestParams('CartGet', array(
            'CartId' => $cartId,
            'HMAC' => $HMAC
                )
        );

        return $this->returnData(
                        $this->performTheRequest("CartGet", $params)
        );
    }

    function cartIsActive($cart = null)
    {
        if (!$cart) {
            $cart = $this->__lastCart;
        }
        return ($cart && isset($cart['CartId']));
    }

    function cartHasItems($cart = null)
    {
        if (!$cart) {
            $cart = $this->__lastCart;
        }
        return ($cart && isset($cart['CartItems']));
    }

    function cartKill()
    {
        unset($_SESSION[$this->_sessionKey]);
    }

    function __formatCartItems($cart)
    {

        unset($cart['Request']);
        if (isset($cart['CartItems'])) {
            $_cartItem = $cart['CartItems']['CartItem'];
            $items = array_keys($_cartItem);
            if (!is_numeric(array_shift($items))) {
                $cart['CartItems']['CartItem'] = array($_cartItem);
            }
        }
        $this->__lastCart = $cart;
        return $cart;
    }

    public function lookup($asin, $addParams = array())
    {
        $addParams['ItemId'] = $asin;
        
        $params = $this->buildRequestParams('ItemLookup', $addParams);

        return $this->returnData(
                $this->performTheRequest("ItemLookup", $params)
        );
    }

    public function browseNodeLookup($nodeId)
    {
        $this->validateNodeId($nodeId);

        $params = $this->buildRequestParams('BrowseNodeLookup', array(
            'BrowseNodeId' => $nodeId
        ));

        return $this->returnData(
                        $this->performTheRequest("BrowseNodeLookup", $params)
        );
    }

    public function similarityLookup($asin)
    {
        $params = $this->buildRequestParams('SimilarityLookup', array(
            'ItemId' => $asin
        ));

        return $this->returnData(
                        $this->performTheRequest("SimilarityLookup", $params)
        );
    }

    protected function buildRequestParams($function, array $params)
    {
        $associateTag = array();

        if (false === empty($this->requestConfig['associateTag'])) {
            $associateTag = array('AssociateTag' => $this->requestConfig['associateTag']);
        }

        return array_merge(
                $associateTag, array(
            'AWSAccessKeyId' => $this->requestConfig['accessKey'],
            'Request' => array_merge(
                    array('Operation' => $function), $params, $this->responseConfig['optionalParameters'], array('ResponseGroup' => $this->prepareResponseGroup())
        )));
    }

    protected function prepareResponseGroup()
    {
        if (false === strstr($this->responseConfig['responseGroup'], ','))
            return $this->responseConfig['responseGroup'];

        return explode(',', $this->responseConfig['responseGroup']);
    }

    protected function performXMLRequest($function, $params)
    {

        $_params = $params['Request'];

        $params = array_merge($params, $_params);
        unset($params['Request']);

        if (is_array($params['ResponseGroup'])) {
            $params['ResponseGroup'] = implode(",", $params['ResponseGroup']);
        }

        $sign_params = array();
        if ($params['Operation'] == 'ItemLookup') {
            $sign_params['Operation'] = $params['Operation'];
            $sign_params['ItemId'] = $params['ItemId'];
            $sign_params['ResponseGroup'] = $params['ResponseGroup'];
        }

        if ($params['Operation'] == 'ItemSearch') {
            $sign_params['Operation'] = $params['Operation'];
            $sign_params['Keywords'] = $params['Keywords'];
            $sign_params['SearchIndex'] = $params['SearchIndex'];
            $sign_params['ResponseGroup'] = $params['ResponseGroup'];
        }

        if ($params['Operation'] == 'CartCreate') {
            $sign_params['Operation'] = $params['Operation'];


            if (count($params['Items']) > 0) {
                $c = 1;
                foreach ($params['Items'] as $key => $value) {
                    $sign_params['Item.' . $c . '.ASIN'] = $value['ASIN'];
                    $sign_params['Item.' . $c . '.Quantity'] = $value['Quantity'];
                    $c++;
                }
            }
        }

        if ($params['Operation'] == 'CartModify') {
            $sign_params['Operation'] = $params['Operation'];
            $sign_params['CartId'] = $params['CartId'];
            $sign_params['HMAC'] = $params['HMAC'];


            if (count($params['Items']) > 0) {
                $c = 1;
                foreach ($params['Items'] as $key => $value) {
                    $sign_params['Item.' . $c . '.CartItemId'] = $value['CartItemId'];
                    $sign_params['Item.' . $c . '.Quantity'] = $value['Quantity'];
                    $c++;
                }
            }
        }

        if ($params['Operation'] == 'CartAdd') {
            $sign_params['Operation'] = $params['Operation'];
            $sign_params['CartId'] = $params['CartId'];
            $sign_params['HMAC'] = $params['HMAC'];


            if (count($params['Items']) > 0) {
                $c = 1;
                foreach ($params['Items'] as $key => $value) {
                    $sign_params['Item.' . $c . '.ASIN'] = $value['ASIN'];
                    $sign_params['Item.' . $c . '.Quantity'] = $value['Quantity'];
                    $c++;
                }
            }
        }

        if ($params['Operation'] == 'CartGet') {
            $sign_params['Operation'] = $params['Operation'];
            $sign_params['CartId'] = $params['CartId'];
            $sign_params['HMAC'] = $params['HMAC'];
        }

        $amzLink = $this->aws_signed_request(
                $this->responseConfig['country'], $sign_params, $this->requestConfig['accessKey'], $this->requestConfig['secretKey'], $this->requestConfig['associateTag']
        );

        $ret = wp_remote_request($amzLink);
        return json_decode(json_encode((array) simplexml_load_string($ret['body'])), 1);
    }

    function aws_signed_request($region, $params, $public_key, $private_key, $associate_tag = NULL, $version = '2011-08-01')
    {
        $method = 'GET';
        $host = 'webservices.amazon.' . $region;
        $uri = '/onca/xml';

        $params['Service'] = 'AWSECommerceService';
        $params['AWSAccessKeyId'] = $public_key;
        $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        $params['Version'] = $version;
        if ($associate_tag !== NULL) {
            $params['AssociateTag'] = $associate_tag;
        }

        ksort($params);

        $canonicalized_query = array();
        foreach ($params as $param => $value) {
            $param = str_replace('%7E', '~', rawurlencode($param));
            $value = str_replace('%7E', '~', rawurlencode($value));
            $canonicalized_query[] = $param . '=' . $value;
        }
        $canonicalized_query = implode('&', $canonicalized_query);

        $string_to_sign = $method . "\n" . $host . "\n" . $uri . "\n" . $canonicalized_query;

        $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $private_key, TRUE));

        $signature = str_replace('%7E', '~', rawurlencode($signature));

        $request = 'http://' . $host . $uri . '?' . $canonicalized_query . '&Signature=' . $signature;

        return $request;
    }

    protected function performSoapRequest($function, $params)
    {
        $soapClient = new SoapClient(
                $this->webserviceWsdl, array('exceptions' => 1)
        );

        $soapClient->__setLocation(str_replace(
                        '%%COUNTRY%%', $this->responseConfig['country'], $this->webserviceEndpoint
        ));

        $soapClient->__setSoapHeaders($this->buildSoapHeader($function));

        return $soapClient->__soapCall($function, array($params));
    }

    protected function buildSoapHeader($function)
    {
        $timeStamp = $this->getTimestamp();
        $signature = $this->buildSignature($function . $timeStamp);

        return array(
            new SoapHeader(
                    'http://security.amazonaws.com/doc/2007-01-01/', 'AWSAccessKeyId', $this->requestConfig['accessKey']
            ),
            new SoapHeader(
                    'http://security.amazonaws.com/doc/2007-01-01/', 'Timestamp', $timeStamp
            ),
            new SoapHeader(
                    'http://security.amazonaws.com/doc/2007-01-01/', 'Signature', $signature
            )
        );
    }

    protected function performTheRequest($function, $params)
    {

        if ($this->protocol == 'XML') {
            return $this->returnData(
                            $this->performXMLRequest($function, $params)
            );
        }

        if ($this->protocol == 'SOAP') {
            return $this->returnData(
                            $this->performSoapRequest($function, $params)
            );
        }
    }

    final protected function getTimestamp()
    {
        return gmdate("Y-m-d\TH:i:s\Z");
    }

    final protected function buildSignature($request)
    {

        return base64_encode(hash_hmac("sha256", $request, $this->requestConfig['secretKey'], true));
    }

    final protected function validateNodeId($nodeId)
    {
        if (false === is_numeric($nodeId) || $nodeId <= 0) {
            throw new InvalidArgumentException(sprintf('Node has to be a positive Integer.'));
        }

        return true;
    }

    protected function returnData($object)
    {
        switch ($this->responseConfig['returnType']) {
            case self::RETURN_TYPE_OBJECT:
                return $object;
                break;

            case self::RETURN_TYPE_ARRAY:
                return $this->objectToArray($object);
                break;

            default:
                throw new InvalidArgumentException(sprintf(
                        "Unknwon return type %s", $this->responseConfig['returnType']
                ));
                break;
        }
    }

    protected function objectToArray($object)
    {
        $out = array();
        foreach ($object as $key => $value) {
            switch (true) {
                case is_object($value):
                    $out[$key] = $this->objectToArray($value);
                    break;

                case is_array($value):
                    $out[$key] = $this->objectToArray($value);
                    break;

                default:
                    $out[$key] = $value;
                    break;
            }
        }

        return $out;
    }

    public function optionalParameters($params = null)
    {
        if (null === $params) {
            return $this->responseConfig['optionalParameters'];
        }

        if (false === is_array($params)) {
            throw new InvalidArgumentException(sprintf(
                    "%s is no valid parameter: Use an array with Key => Value Pairs", $params
            ));
        }

        $this->responseConfig['optionalParameters'] = $params;

        return $this;
    }

    public function country($country = null)
    {
        if (null === $country) {
            return $this->responseConfig['country'];
        }

        if (false === in_array(strtolower($country), $this->possibleLocations)) {
            throw new InvalidArgumentException(sprintf(
                    "Invalid Country-Code: %s! Possible Country-Codes: %s", $country, implode(', ', $this->possibleLocations)
            ));
        }

        $this->responseConfig['country'] = strtolower($country);

        return $this;
    }

    public function category($category = null)
    {
        if (null === $category) {
            return isset($this->requestConfig['category']) ? $this->requestConfig['category'] : null;
        }

        $this->requestConfig['category'] = $category;

        return $this;
    }

    public function responseGroup($responseGroup = null)
    {
        if (null === $responseGroup) {
            return $this->responseConfig['responseGroup'];
        }

        $this->responseConfig['responseGroup'] = $responseGroup;

        return $this;
    }

    public function returnType($type = null)
    {
        if (null === $type) {
            return $this->responseConfig['returnType'];
        }

        $this->responseConfig['returnType'] = $type;

        return $this;
    }

    public function associateTag($associateTag = null)
    {
        if (null === $associateTag) {
            return $this->requestConfig['associateTag'];
        }

        $this->requestConfig['associateTag'] = $associateTag;

        return $this;
    }

    public function setReturnType($type)
    {
        return $this->returnType($type);
    }

    public function page($page)
    {
        if (false === is_numeric($page) || $page <= 0) {
            throw new InvalidArgumentException(sprintf(
                    '%s is an invalid page value. It has to be numeric and positive', $page
            ));
        }

        $this->responseConfig['optionalParameters'] = array_merge(
                $this->responseConfig['optionalParameters'], array("ItemPage" => $page)
        );

        return $this;
    }

}
