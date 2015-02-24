<?php
/**
 * MOST Web Framework
 * A JavaScript Web Framework
 * http://themost.io
 *
 * Copyright (c) 2015, Kyriakos Barbounakis k.barbounakis@gmail.com, Anthi Oikonomou anthioikonomou@gmail.com
 *
 * Released under the BSD3-Clause license
 * Date: 2015-02-19
 */
require_once 'HTTP/Request2.php';

/**
 * Represents a common HTTP exception
 * Class HttpClientException
 */
class HttpClientException extends Exception {

    // Redefine the exception so message isn't optional
    public function __construct($message = null, $code = 500, Exception $previous = null) {
        // make sure everything is assigned properly
        if (is_null($message))
            $message = 'Internal Server Error';
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return "[{$this->code}] {$this->message}";
    }
}

class DynamicObject {

    public function __construct(array $arguments = array()) {
        if (!empty($arguments)) {
            foreach ($arguments as $property => $argument) {
                $this->{$property} = $argument;
            }
        }
    }
    public function __call($method, $arguments) {
        $arguments = array_merge(array("DynamicObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            throw new Exception("Fatal error: Call to undefined method DynamicObject::{$method}()");
        }
    }
}

/**
 * Class ClientDataService
 */
class ClientDataService
{
    public $cookies;
    /**
     * Gets or sets a string  represents a remote URL that is going to be the target application
     * @var null|string
     */
    public $url;
    /**
     * ClientDataService class constructor.
     * @param string $url - A string that represents a remote URL that is going to be the target application.
     */
    public function __construct($url = null) {
        //set model
        $this->url = $url;
        //init cookies
        $this->cookies = array();
    }

    public function authenticate($username, $password) {
        //init cookies
        $this->cookies = array();
        //init request
        $request = new HTTP_Request2($this->url, HTTP_Request2::METHOD_HEAD);
        try {
            $request->setAuth($username, $password);
            $response = $request->send();
            if (200 == $response->getStatus()) {
                //get cookie
                $cookies = $response->getCookies();
                //add cookie
                foreach($cookies as $cookie) {
                    $this->cookies[$cookie['name']] = $cookie['value'];
                }
                return true;
            } else {
                throw new HttpClientException($response->getReasonPhrase(),$response->getStatus());
            }
        } catch (HTTP_Request2_Exception $e) {
            throw new HttpClientException($e->getMessage(),$e->getCode());
        }
    }

    /**
     * @param string $relativeUrl - A string that represents the relative URL of the target application.
     * @return array|stdClass|*
     * @throws Exception
     */
    public function get($relativeUrl) {
        try {
            if (is_null($this->url)) {
                throw new Exception('Target application base URL cannot be empty at this context.');
            }
            if (is_null($relativeUrl)) {
                throw new Exception('URL cannot be empty at this context.');
            }
            //build target url
            $url = $this->url . $relativeUrl;
            //initialize request
            $request = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
            //append cookies
            foreach(array_keys($this->cookies) as $key) {
                $request->addCookie($key, $this->cookies[$key]);
            }
            try {
                $response = $request->send();
                if (200 == $response->getStatus()) {
                    //validate content type
                    $contentType = $response->getHeader('content-type');
                    if (strpos($contentType,'application/json')==-1)
                        throw new HttpClientException('Invalid response content type.', 500);
                    //try to decode json
                    $res = json_decode($response->getBody());
                    return $res;
                } else {
                    throw new HttpClientException($response->getReasonPhrase(),$response->getStatus());
                }
            } catch (HTTP_Request2_Exception $e) {
                throw new HttpClientException($e->getMessage(),$e->getCode());
            }
        }
        catch(HttpException $e) {
            throw $e;
        }
        catch(Exception $e) {
            print $e;
            throw new HttpClientException('Internal Server Error',500);
        }
    }

    /**
     * @param string $relativeUrl - A string that represents the relative URL of the target application.
     * @param array|* $data
     * @return array|stdClass|*
     * @throws Exception
     */
    public function post($relativeUrl, $data) {
        return $this->execute('POST',$relativeUrl, $data);
    }

    /**
     * @param string $relativeUrl - A string that represents the relative URL of the target application.
     * @param array|* $data
     * @return array|stdClass|*
     * @throws Exception
     */
    public function put($relativeUrl, $data) {
        return $this->execute('PUT',$relativeUrl, $data);
    }

    /**
     * @param string $relativeUrl - A string that represents the relative URL of the target application.
     * @param array|* $data
     * @return array|stdClass|*
     * @throws Exception
     */
    public function remove($relativeUrl, $data) {
        return $this->execute('DELETE',$relativeUrl, $data);
    }

    /**
     * @param string $relativeUrl - A string that represents the relative URL of the target application.
     * @param string $method
     * @param array|* $data
     * @return array|stdClass|*
     * @throws Exception
     */
    private function execute($method, $relativeUrl, $data) {
        try {
            if (is_null($this->url)) {
                throw new Exception('Target application base URL cannot be empty at this context.');
            }
            if (is_null($relativeUrl)) {
                throw new Exception('URL cannot be empty at this context.');
            }
            //build target url
            $url = $this->url . $relativeUrl;
            //initialize request
            $request = new HTTP_Request2($url, $method);
            //append cookies
            foreach(array_keys($this->cookies) as $key) {
                $request->addCookie($key, $this->cookies[$key]);
            }
            try {
                $request->setHeader('Content-Type','application/json');
                $request->setBody(json_encode($data));
                $response = $request->send();
                if (200 == $response->getStatus()) {
                    //validate content type
                    $contentType = $response->getHeader('content-type');
                    if (strpos($contentType,'application/json')!=-1) {
                        //try to decode json
                        $res = json_decode($response->getBody());
                        return $res;
                    }
                    else {
                        return new stdClass();
                    }

                } else {
                    throw new HttpClientException($response->getReasonPhrase(),$response->getStatus());
                }
            } catch (HTTP_Request2_Exception $e) {
                throw new HttpClientException($e->getMessage(),$e->getCode());
            }
        }
        catch(HttpException $e) {
            throw $e;
        }
        catch(Exception $e) {
            print $e;
            throw new HttpClientException('Internal Server Error',500);
        }
    }

}


class ClientDataContext {

    private $url;

    private $service;

    /**
     * ClientDataService class constructor.
     * @param string $url - A string that represents a remote URL that is going to be the target application.
     */
    public function __construct($url) {
        //set model
        $this->url = $url;
        $this->service = new ClientDataService($this->url);
    }

    /**
     * @return array
     */
    public function getCookies() {
        return $this->service->cookies;
    }

    /**
     * @param $array
     * @throws Exception
     */
    public function setCookies($array) {
        if (!is_array($array))
            throw new Exception('Expected array.');
        $this->service->cookies = $array;
    }

    /**
     * @param string $username
     * @param string $password
     * @return ClientDataContext
     * @throws Exception
     */
    public function authenticate($username, $password) {
        if (is_null($this->service))
            $this->service = new ClientDataService($this->url);
        $this->service->authenticate($username, $password);
        return $this;
    }

    /**
     * Gets a ClientDataQueryable instance of the specified model
     * @param string $name
     * @throws Exception
     * @return ClientDataQueryable
     */
    function model($name) {
        if (is_null($name))
            throw new Exception('Model cannot be empty at this context');
        $res = new ClientDataQueryable($name);
        if (is_null($this->service))
            $this->service = new ClientDataService($this->url);
        //set service
        $res->service = $this->service;
        return $res;
    }
}

class DataQueryableOptions
{
    public $filter;
    public $select;
    public $order;
    public $top;
    public $skip;
    public $group;
    public $expand;
    public $inlinecount;
}

/**
 * Class ClientDataQueryable
 */
class ClientDataQueryable
{
    /**
     * Gets or sets the underlying ClientDataService instance
     * @var null|ClientDataService
     */
    public $service;
    /**
     * @var null|string
     */
    protected $model;
    /**
     * Gets or sets in-process operator
     * @var null|string
     */
    protected $left;
    /**
     * Gets or sets in-process operator
     * @var null|string
     */
    protected $op;
    /**
     * Gets or sets in-process operator
     * @var null|string
     */
    protected $lop;
    /**
     * Gets or sets in-process operator
     * @var *
     */
    protected $right;
    /**
     * @var null|DataQueryableOptions
     */
    protected $options;
    /**
     * Gets or sets the target URL based on the current model
     * @var *
     */
    protected $get_url;
    /**
     * Gets or sets the target URL for POST operations based on the current model
     * @var *
     */
    protected $post_url;
    /**
     * Gets or sets the key of the related item if any
     * @var *
     */
    private $key;
    /**
     * ClientDataQueryable class constructor.
     * @param string $model - A string that represents the target model for this object.
     */
    public function __construct($model) {
        //set model
        $this->model = $model;
        //set get url
        $this->get_url = "/$model/index.json";
        //set post url
        $this->post_url = "/$model/edit.json";
        //init options
        $this->options = new DataQueryableOptions();
        //set inline count to true
        $this->options->inlinecount=true;
    }

    const EXCEPTION_INVALID_RIGHT_OP = 'Invalid right operand assignment. Left operand cannot be empty at this context.';
    const EXCEPTION_NOT_NULL = 'Value cannot be null at this context.';

    /**
     * @param int $num
     * @return array|stdClass
     * @throws Exception
     * @throws HttpException
     */
    public function take($num=0) {
        if (is_int($num))
            if ($num>=0)
                $this->top($num);
        //get data
        $model = $this->model;
        return $this->service->get($this->get_url.$this->build_options_query());
    }

    /**
     * @param * $key
     * @return ClientDataQueryable
     */
    public function item($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * @param string $association
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function query($association) {
        if (is_null($this->key))
            throw new Exception('Associated item cannot be empty at this context.');
        $q = new ClientDataQueryable($association);
        //clone service
        $q->service = $this->service;
        //get variables
        $key = $this->key; $model = $this->model;
        //set get url
        $q->get_url = "/$model/$key/$association/index.json";
        //set post url
        $q->post_url = "/$model/$key/$association/edit.json";
        return $q;
    }

    /**
     * @return array|stdClass
     * @throws Exception
     * @throws HttpException
     */
    public function first() {
        $this->skip(0)->top(1);
        //get data
        $model = $this->model;
        return $this->service->get($this->get_url.$this->build_options_query())[0];
    }

    /**
     * @param array|* $data
     * @return array|stdClass|DynamicObject
     * @throws Exception
     * @throws HttpClientException
     * @throws HttpException
     */
    public function update($data) {
        $model = $this->model;
        return $this->service->post($this->post_url, $data);
    }

    /**
     * @param array|* $data
     * @return array|stdClass|DynamicObject
     * @throws Exception
     * @throws HttpClientException
     * @throws HttpException
     */
    public function insert($data) {
        $model = $this->model;
        return $this->service->put($this->post_url, $data);
    }

    /**
     * @param array|* $data
     * @return array|stdClass|DynamicObject
     * @throws Exception
     * @throws HttpClientException
     * @throws HttpException
     */
    public function remove($data) {
        $model = $this->model;
        return $this->service->remove($this->post_url, $data);
    }

    private function build_options_query() {
        if (is_null($this->options))
            return '';
        //enumerate options
        $vars = get_object_vars($this->options);
        $query = array();
        while (list($key, $val) = each($vars)) {
            if (!is_null($val))
                array_push($query, '$'.$key.'='.$val);
        }
        if (count($query)>0) {
            return "?".implode('&',$query);
        }
        else {
            return '';
        }
    }

    /**
     * @param int $num
     * @return ClientDataQueryable
     */
    public function skip($num = 0) {
        $this->options->skip = $num;
        return $this;
    }

    /**
     * @param int $num
     * @return ClientDataQueryable
     */
    public function top($num = 25) {
        if ($num<=0)
            return $this;
        $this->options->top = $num;
        return $this;
    }

    /**
     * @return ClientDataQueryable
     */
    public function paged() {
        $this->options->inlinecount = true;
        return $this;
    }

    /**
     * @param null|string $field
     *  @return ClientDataQueryable
     */
    public function where($field = null) {
        if (is_null($field))
            return $this;
        //set in-process field
        $this->left = $field;
        return $this;
    }

    /**
     * @param null|string $field
     *  @return ClientDataQueryable
     */
    public function also($field = null) {
        $this->lop = 'and';
        if (is_null($field))
            return $this;
        $this->left = $field;
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function either($field = null) {
        $this->lop = 'or';
        if (is_null($field))
            return $this;
        $this->left = $field;
        $this->lop = 'or';
        return $this;
    }

    /**
     * @param null|string|array $field
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function select($field = null) {
        if(is_null($field))
            return $this;
        if (is_string($field)) {
            $this->options->select = $field;
        }
        else if(is_array($field)) {
            if (count($field)>0) {
                $this->options->select = implode(',', $field);
            }
            else {
                $this->options->select = null;
            }
        }
        else
            throw new Exception('Invalid argument. Expected string or array.');
        return $this;
    }

    /**
     * @param string|array $field
     * @return $this
     * @throws Exception
     */
    public function expand($field) {
        if(is_null($field))
            return $this;
        if (is_string($field)) {
            $this->options->expand = $field;
        }
        else if(is_array($field)) {
            if (count($field)>0) {
                $this->options->expand = implode(',', $field);
            }
        }
        else
            throw new Exception('Invalid argument. Expected string or array.');
        return $this;
    }

    /**
     * @param null|string $field
     * @return ClientDataQueryable
     */
    public function orderBy($field = null) {
        if(is_null($field))
            return $this;
        $this->options->order = $field;
        return $this;
    }
    /**
     * @param null|string $field
     * @return ClientDataQueryable
     */
    public function orderByDescending($field = null) {
        if(is_null($field))
            return $this;
        $this->options->order = "$field desc";
        return $this;
    }

    /**
     * @param null|string $field
     * @return ClientDataQueryable
     */
    public function thenBy($field = null) {
        if(is_null($field))
            return $this;
        if (isset($this->options->order))
            $this->options->order .= ",$field";
        else
            $this->options->order=$field;
        return $this;
    }

    /**
     * @param null|string $field
     * @return ClientDataQueryable
     */
    public function thenByDescending($field = null) {
        if(is_null($field))
            return $this;
        if (isset($this->options->order))
            $this->options->order .= ",$field desc";
        else
            $this->options->order="$field desc";
        return $this;
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function equal($value = null) {
        return $this->compare('eq', $value);
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function notEqual($value = null) {
        return $this->compare('ne', $value);
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function greaterThan($value = null) {
        return $this->compare('gt', $value);
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function greaterOrEqual($value = null) {
        return $this->compare('ge', $value);
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function lowerThan($value = null) {
        return $this->compare('lt', $value);
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function lowerOrEqual($value = null) {
        return $this->compare('le', $value);
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function endsWith($value = null) {
        if (is_null($this->left))
            throw new Exception(self::EXCEPTION_INVALID_RIGHT_OP);
        $left = $this->left;
        $escapedValue = $this->escape($value);
        $this->left = "endswith($left,$escapedValue)";
        return $this->compare('eq', true);
    }

    /**
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function startsWith($value = null) {
        if (is_null($this->left))
            throw new Exception(self::EXCEPTION_INVALID_RIGHT_OP);
        $left = $this->left;
        $escapedValue = $this->escape($value);
        $this->left = "startswith($left,$escapedValue)";
        return $this->compare('eq', true);
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function toLower($field = null) {
        $this->left = "tolower($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function toUpper($field = null) {
        $this->left = "toupper($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function trim($field = null) {
        $this->left = "trim($field)";
        return $this;
    }

    /**
     * @param null|string $field
     * @param int $pos
     * @param int $length
     * @return $this
     * @throws Exception
     */
    public function substring($field=null, $pos=0, $length=0) {
        if (is_null($field))
            return $this;
        if ($length<=0)
            throw new Exception('Invalid argument. Length must be greater than zero.');
        if ($pos<0)
            throw new Exception('Invalid argument. Position must be greater or equal to zero.');
        $this->left = "substring($field,$pos,$length)";
        return $this;
    }

    /**
     * @param null|string $field
     * @param null $s
     * @return $this
     */
    public function substringOf($field=null, $s=null) {
        if (is_null($field))
            return $this;
        $str = $this->escape($s);
        $this->left = "substringof($field,$str)";
        return $this;
    }

    /**
     * @param string $field
     * @param string $s
     * @return ClientDataQueryable
     */
    public function indexOf($field, $s) {
        if (is_null($field))
            return $this;
        $str = $this->escape($s);
        $this->left = "indexof($field,$str)";
        return $this;
    }

    /**
     * @param string $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function contains($value) {
        if (is_null($value))
            throw new InvalidArgumentException(self::EXCEPTION_NOT_NULL);
        //escape value
        $str = $this->escape($value);
        //get left operand
        $left = $this->left;
        //format left operand
        $this->left = "indexof($left,$str)";
        //and finally append comparison
        return $this->compare('ge', 0);
    }

    /**
     * @param null|string $field
     *  @return ClientDataQueryable
     */
    public function length($field = null) {
        if (is_null($field))
            return $this;
        $this->left = "length($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function year($field = null) {
        $this->left = "year($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function month($field = null) {
        $this->left = "month($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function day($field = null) {
        $this->left = "day($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function hour($field = null) {
        $this->left = "hour($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function minute($field = null) {
        $this->left = "minute($field)";
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function second($field = null) {
        $this->left = "second($field)";
        return $this;
    }

    /**
     * @param string $op
     * @param * $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    private function compare($op = null, $value = null) {
        if (is_null($this->left))
            throw new Exception(EXCEPTION_INVALID_RIGHT_OP);
        $this->op = $op;
        $this->right = $value;
        $this->append();
        return $this;
    }

    protected function append() {
        try {
            $expr = $this->left . ' ' . $this->op . ' ' . $this->escape($this->right);
            if (is_null($this->lop))
            $this->lop = 'and';
            if (isset($this->options->filter))
                $this->options->filter = '(' . $this->options->filter . ') and (' . $expr . ')';
            else
                $this->options->filter = $expr;
                    //clear expression parameters
            $this->left = null; $this->op = null; $this->right = null; $this->lop = null;
        }
        catch(Exception $e) {
            throw $e;
        }
    }

    /**
     * @param null $value
     * @return string
     */
    public function escape($value = null) {
        //0. null
        if (is_null($value))
            return 'null';
        //1. array
        if (is_array($value)) {
            $array = array();
             foreach ($value as $val) {
                 array_push($array,$this->escape($val));
             }
            return '['. implode(",", $array) . ']';
        }
        //2. datetime
        else if (is_a($value, 'DateTime')) {
            $str = $value->format('c');
            return "'$str'";
        }
        //3. boolean
        else if (is_bool($value)) {
            return $value==true ? 'true': 'false';
        }
        //4. numeric
        else if (is_float($value) || is_double($value) || is_int($value)) {
            return json_encode($value);
        }
        //5. string
        else if (is_string($value)) {
            return "'$value'";
        }
        else {
            $str = var_dump($value);
            return "'$str'";
        }
    }

}
