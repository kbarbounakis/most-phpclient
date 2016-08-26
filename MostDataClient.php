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
require_once 'HTTP/Request2/Adapter/Curl.php';


class Args {

    /**
     * @param * $arg
     * @param string $name
     * @throws Exception
     */
    public static function notNull($arg, $name) {
        if (is_null($arg)) {
            throw new Exception($name." may not be null");
        }
    }

    /**
     * @param * $arg
     * @param string $name
     * @throws Exception
     */
    public static function notString($arg, $name) {
        if (!is_string($arg)) {
            throw new Exception($name." must be a string");
        }
    }


    /**
     * @param * $arg
     * @param string $name
     * @throws Exception
     */
    public static function notEmpty($arg, $name) {
        Args::notNull($arg,$name);
        Args::notString($arg,$name);
        if (strlen($arg)==0) {
            throw new Exception($name." may not be empty");
        }
    }

    /**
     * @param * $arg
     * @param string $message
     * @throws Exception
     */
    public static function check($arg, $message) {
        if (!$arg) {
            throw new Exception($message);
        }
    }
}

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

    /**
     * @param null|array|stdClass|DynamicObject $args
     * @throws Exception
     */
    public function __construct($args = null) {
        if (is_null($args))
            return;
        if (is_array($args)) {
            foreach ($args as $property => $argument) {
                $this->{$property} = $argument;
            }
        }
        else if (is_a($args, 'stdClass')) {
            DynamicObject::object_to_dynamic($args, $this);
        }
        else if (is_a($args, 'DynamicObject')) {
            $vars = get_object_vars($args);
            foreach ($vars AS $property => $value) {
                $this->{$property} = $value;
            }
        }
        else {
            throw new Exception('Invalid argument');
        }
    }

    /**
     * @param stdClass|null|DynamicObject $object
     * @param DynamicObject|null $target
     * @return array|DynamicObject|null
     */
    public static function object_to_dynamic($object, $target = null) {

        if (is_null($object))
            return null;
        if (is_array($object)) {
            $arr = array();
            foreach ($object as $item) {
                array_push($arr,DynamicObject::object_to_dynamic($item));
            }
            return $arr;
        }
        if (is_null($target))
            $target = new DynamicObject();
        $vars = get_object_vars($object);
        foreach ($vars AS $key => $value) {
            if (is_array($value)) {
                $arr = array();
                foreach ($value as $item) {
                    if (is_a($item, 'stdClass'))
                        array_push($arr,DynamicObject::object_to_dynamic($item));
                    else
                        array_push($arr,$item);
                }
                $target->{$key} = $arr;
            }
            else if (is_a($value, 'stdClass')) {
                $target->{$key} = DynamicObject::object_to_dynamic($value);
            }
            else {
                $target->{$key} = $value;
            }
        }
        return $target;
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

    protected $ssl_verify_host = FALSE;

    protected $ssl_verify_peer = FALSE;

    private $cookies;
    
    private $url;
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

    public  function getBase() {
        return $this->url;
    }

    /**
     * Sets ClientDataService cookies.
     * @param $cookies
     */
    public function setCookie($cookies) {
        $this->cookies = $cookies;
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
        return $this->execute('GET',$relativeUrl, null);
    }

    /**
     * @param string $relativeUrl - A string that represents the relative URL of the target application.
     * @param array|stdClass|* $data
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
    public function execute($method, $relativeUrl, $data) {
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
                if (!is_null($data))
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
                        return new DynamicObject();
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
     * Gets an instance of ClientDataModel class based on the specified model name
     * @param string $name
     * @throws Exception
     * @return ClientDataModel
     */
    function model($name) {
        Args::notNull($name, "Model name");
        return new ClientDataModel($name,$this->service);
	}

	/**
	* Gets the instance of ClientDataService which is associated with this data context.
	* @return ClientDataService
	*/
	public function getService() {
		return $this->service;
	}
}

class ClientDataModel {

    private $name_;
    private $url_;
    private $service_;
    /**
     * ClientDataModel class constructor.
     * @param string $name - A string which represents the name of this model.
     * @param ClientDataService $service - An instance of ClientDataService that is going to be used in data requests.
     */
    public function __construct($name, $service) {
        Args::notNull($name, "Model name");
        $this->name_ = $name;
        $this->url_ = "/$name/index.json";
        $this->service_ = $service;
    }

    /**
     * Gets the name of this data model
     * @return string
     */
    public function getName() {
        return $this->name_;
    }

    /**
     * Gets the URL which is associated with this data model
     * @return string
     */
    public function getUrl() {
        return $this->url_;
    }

    /**
     * Sets the URL for this data model
     * @param string $url
     * @return string
     */
    public function setUrl($url) {
        Args::notNull($url,"Model URL");
        Args::check(preg_match("/^https?:\\/\\//i",$url),"Request URL may not be an absolute URI");
        if (preg_match("/^\\//i", $url))
            $this->url_ = $url;
        else
        {
            $this->url_ = "/".$this->getName()."/".$url;
        }
    }

    /**
     * Gets the instance of ClientDataService which is associated with this data model.
     * @return ClientDataService
     */
    public function getService() {
        return $this->service_;
    }

    /**
     * Gets the schema of this data model
     * @return stdClass
     * @throws HttpClientException
     * @throws HttpException
     */
    public function getSchema() {
        $model = $this->getName();
        return $this->getService()->execute("GET", "/$model/schema.json", null);
    }

    /**
     * @param string $field
     * @return ClientDataQueryable
     */
    public function where($field) {
        Args::notNull($field,"Field");
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return $res->where($field);
    }

    /**
     * @param ...string $field
     * @return ClientDataQueryable
     */
    public function select($field) {
        Args::notNull($field,"Field");
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return call_user_func_array(array($res, "select"), func_get_args());
    }

    /**
     * @param ...string $field
     * @return ClientDataQueryable
     */
    public function expand($field) {
        Args::notNull($field,"Field");
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return call_user_func_array(array($res, "expand"), func_get_args());
    }

    /**
     * @param ...string $field
     * @return ClientDataQueryable
     */
    public function orderBy($field) {
        Args::notNull($field,"Field");
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return call_user_func_array(array($res, "orderBy"), func_get_args());
    }

    /**
     * @param ...string $field
     * @return ClientDataQueryable
     */
    public function orderByDescending($field) {
        Args::notNull($field,"Field");
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return call_user_func_array(array($res, "orderByDescending"), func_get_args());
    }

    /**
     * @param int $num
     * @return ClientDataQueryable
     */
    public function skip($num) {
        Args::notNull($num,"Skip argument");
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return $res->skip($num);
    }

    /**
     * @param int $num
     * @return ClientDataQueryable
     */
    public function take($num) {
        Args::notNull($num,"Skip argument");
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return $res->take($num);
    }

    /**
     * @return stdClass[]
     */
    public function getItems() {
        $res = new ClientDataQueryable($this->getName());
        $res->setService($this->getService());
        return $res->getItems();
    }

    /**
     * @param * $data
     * @return array|stdClass|DynamicObject
     * @throws Exception
     * @throws HttpClientException
     * @throws HttpException
     */
    public function save($data) {
        Args::notNull($this->getService(),"Client service");
        return $this->getService()->execute("POST", $this->getUrl(), $data);
    }

    /**
     * @param * $data
     * @return array|stdClass|DynamicObject
     * @throws Exception
     * @throws HttpClientException
     * @throws HttpException
     */
    public function remove($data) {
        Args::notNull($this->getService(),"Client service");
        return $this->getService()->execute("DELETE", $this->getUrl(), $data);
    }

}

class DataQueryableOptions
{
    /**
     * Gets or set a string that contains an open data formatted filter statement, if any.
     * @var string
     */
    public $filter;
    /**
     * Gets or sets a comma delimited string that contains the fields to be retrieved.
     * @var string
     */
    public $select;
    /**
     * Gets or sets a comma delimited string that contains the fields to be used for ordering the result set.
     * @var string
     */
    public $order;
    /**
     * Gets or sets a number that indicates the number of records to retrieve.
     * @var int
     */
    public $top;
    /**
     * Gets or sets a number that indicates the number of records to be skipped.
     * @var int
     */
    public $skip;
    /**
     * Gets or sets a comma delimited string that contains the fields to be used for grouping the result set.
     * @var string
     */
    public $group;
    /**
     * Gets or sets a comma delimited string that contains the models to be expanded.
     * @var string
     */
    public $expand;
    /**
     * Gets or sets a boolean that indicates whether paging parameters will be included in the result set.
     * @var boolean
     */
    public $inlinecount;
    /**
     * Gets or sets a boolean which indicates whether the result will contain only the first item of the result set.
     * @var boolean
     */
    public $first;
    /**
     *  Gets or set a string that contains an open data formatted filter statement that is going to be joined with the underlying filter statement, if any.
     * @var string
     */
    public $prepared;
}

/**
 * A FilterExpression instance that is going to be used in open data filter statements
 * Class FilterExpression
 */
class FilterExpression {

    public $expr;
    /**
     * @param string $expr
     */
    public function __construct($expr) {
        $this->expr = $expr;
    }
    public function __toString(){
        return $this->expr;
    }
}

/**
 * Represents a common HTTP exception
 * Class HttpClientException
 */
class MeExpression extends FilterExpression {
    public function __construct() {
        parent::__construct('me()');
    }
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
    private $left;
    /**
     * Gets or sets in-process operator
     * @var null|string
     */
    private $op;
    /**
     * Gets or sets in-process operator
     * @var null|string
     */
    private $lop;
    /**
     * Gets or sets in-process prepared operator
     * @var null|string
     */
    private $prepared_lop;
    /**
     * Gets or sets in-process operator
     * @var *
     */
    private $right;
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

    /**
     * Gets the name of the associated data model.
     * @return ClientDataService
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Gets the instance of ClientDataService which is associated with this data queryable.
     * @return ClientDataService
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Sets the instance of ClientDataService which is associated with this data queryable.
     * @param ClientDataService $service
     * @return ClientDataQueryable
     */
    public function setService($service) {
        $this->service = $service;
        return $this;
    }

    const EXCEPTION_INVALID_RIGHT_OP = 'Invalid right operand assignment. Left operand cannot be empty at this context.';
    const EXCEPTION_NOT_NULL = 'Value cannot be null at this context.';

    /**
     * @param int $num
     * @return ClientDataQueryable
     * @throws Exception
     * @throws HttpException
     */
    public function take($num) {
        $this->options->top = $num;
        $this->options->first = false;
        $this->options->inlinecount=false;
        return $this;
    }

    /**
     * @return stdClass
     * @throws Exception
     * @throws HttpException
     */
    public function first() {
        $this->options->skip = 0;
        $this->options->top = 0;
        $this->options->inlinecount = false;
        $this->options->first = true;
        return $this->service->execute("GET", $this->get_url.$this->build_options_query(), null);
    }

    /**
     * @return stdClass
     * @throws Exception
     * @throws HttpException
     */
    public function getItem() {
        return $this->first();
    }

    /**
     * @return stdClass
     * @deprecated Use ClientDataQueryable.getItem() instead
     * @throws Exception
     * @throws HttpException
     */
    public function item() {
        return $this->getItem();
    }

    /**
     * @return stdClass[]
     * @throws Exception
     * @throws HttpException
     */
    public function getItems() {
        $this->options->inlinecount = false;
        return $this->service->execute("GET", $this->get_url.$this->build_options_query(), null);
    }

    /**
     * @throws Exception
     * @throws HttpException
     */
    public function getList() {
        $this->options->first = false;
        $this->options->inlinecount = true;
        return $this->service->execute("GET", $this->get_url.$this->build_options_query(), null);
    }

    /**
     * @return stdClass[]
     * @deprecated Use ClientDataQueryable.getItems() instead
     * @throws Exception
     * @throws HttpException
     */
    public function items() {
        return $this->getItems();
    }


    private function join_filters($filter1=null, $filter2=null)
    {
        if (is_string($filter1)) {
            if (is_null($this->prepared_lop))
                $this->prepared_lop='and';
            if (is_string($filter2)) {
                return '('.$filter1.') '.$this->prepared_lop.' ('.$filter2.')';
            }
            else {
                return $filter1;
            }
        }
        else {
            return $filter2;
        }
    }

    private function build_options_query() {
        if (is_null($this->options))
            return '';
        //enumerate options
        $vars = get_object_vars($this->options);
        if (is_string($vars['prepared'])) {
            $vars['filter'] = $this->join_filters($vars['prepared'], $vars['filter']);
            $vars['prepared']=null;
        }
        $query = array();
        while (list($key, $val) = each($vars)) {
            if (!is_null($val)) {
                if (is_bool($val))
                    array_push($query, '$'.$key.'='.($val ? 'true' : 'false'));
                else
                    array_push($query, '$'.$key.'='.$val);
            }

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
        if ($num<0)
            return $this;
        $this->options->top = $num;
        return $this;
    }

    /**
     * @return ClientDataQueryable
     */
    public function prepare() {
        if (is_null($this->options->filter))
          return $this;
        //append filter statement
        $this->options->prepared = $this->join_filters($this->options->prepared, $this->options->filter);
        //destroy filter statement
        $this->options->filter=null;
        return $this;
    }

    /**
     * Prepares a logical OR query expression.
     * Note: The common ClientDataQueryable.or() method cannot be used because and is a reserved word for PHP.
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function either($field = null) {
        Args::notNull($field,"Field");
        $this->lop = 'or';
        $this->left = $field;
        $this->lop = 'or';
        return $this;
    }

    /**
     * Prepares a logical AND query expression.
     * Note: The common ClientDataQueryable.and() method cannot be used because and is a reserved word for PHP.
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function also($field) {
        Args::notNull($field,"Field");
        $this->lop = 'and';
        $this->left = $field;
        return $this;
    }

    /**
     * @param string $field
     * @return ClientDataQueryable
     */
    public function andAlso($field) {
        if (is_null($field))
            return $this;
        $this->prepare();
        $this->prepared_lop = 'and';
        $this->left = $field;
        return $this;
    }

    /**
     * @param string $field
     * @return ClientDataQueryable
     */
    public function orElse($field) {
        if (is_null($field))
            return $this;
        $this->prepare();
        $this->prepared_lop = 'or';
        $this->left = $field;
        return $this;
    }

    /**
     * @param string $field
     *  @return ClientDataQueryable
     */
    public function where($field) {
        Args::notNull($field,"Field");
        //set in-process field
        $this->left = $field;
        return $this;
    }

    /**
     * @param ...string $field
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function select($field) {
        $arg_list = func_get_args();
        if (count($arg_list)>0) {
            $this->options->select = implode(',', $arg_list);
        }
        else {
            throw new Exception('Invalid argument. Expected string.');
        }
        return $this;
    }

    /**
     * @param ...string $field
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function groupBy($field) {
        $arg_list = func_get_args();
        if (count($arg_list)>0) {
            $this->options->group = implode(',', $arg_list);
        }
        else {
            throw new Exception('Invalid argument. Expected string.');
        }
        return $this;
    }

    /**
     * @param ...string $field
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function expand($field) {
        $arg_list = func_get_args();
        if (count($arg_list)>0) {
            $this->options->expand = implode(',', $arg_list);
        }
        else {
            throw new Exception('Invalid argument. Expected string.');
        }
        return $this;
    }

    /**
     * @param string $field
     * @return ClientDataQueryable
     */
    public function orderBy($field) {
        Args::notNull($field,"Order expression");
        $this->options->order = $field;
        return $this;
    }
    /**
     * @param string $field
     * @return ClientDataQueryable
     */
    public function orderByDescending($field) {
        Args::notNull($field,"Order expression");
        $this->options->order = "$field desc";
        return $this;
    }

    /**
     * @param null|string $field
     * @return ClientDataQueryable
     */
    public function thenBy($field) {
        Args::notNull($field,"Order expression");
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
        Args::notNull($field,"Order expression");
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
     * @param * $value1
     * @param * $value2
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function between($value1, $value2) {
        Args::notNull($this->left,"Left operand");
        $s = (new ClientDataQueryable ($this->getModel ()))
            ->where ($this->left)->greaterOrEqual ($value1)
            ->also ($this->left)->lowerOrEqual ($value2)->options->filter;
        $lop = $this->lop;
        if (is_null($lop)) {
            $lop = "and";
        }
        $filter = $this->options->filter;
        if (is_string($filter)) {
            $this->options->filter = "($filter) $lop ($s)";
        }
        else {
            $this->options->filter =  "($s)";
        }
        $this->left = null; $this->op = null; $this->right = null; $this->lop = null;
        return $this;
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
        return $this;
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
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function toLowerCase() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "tolower($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function toUpperCase() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "toupper($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function trim() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "toupper($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function round() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "round($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function floor() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "floor($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function ceil() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "ceiling($field)";
        return $this;
    }

    /**
     * @param int $pos
     * @param int $length
     * @return $this
     * @throws Exception
     */
    public function substring($pos=0, $length=0) {
        if ($length<=0)
            throw new Exception('Invalid argument. Length must be greater than zero.');
        if ($pos<0)
            throw new Exception('Invalid argument. Position must be greater or equal to zero.');
        $field = $this->left;
        $this->left = "substring($field,$pos,$length)";
        return $this;
    }

    /**
     * @param int $pos
     * @param int $length
     * @return $this
     * @throws Exception
     */
    public function substr($pos=0, $length=0) {
        return $this->substring($pos,$length);
    }

    /**
     * @param string $s
     * @return ClientDataQueryable
     */
    public function indexOf($s) {
        Args::notNull($this->left,"Left operand");
        Args::notNull($s,"Value");
        $str = $this->escape($s);
        $field = $this->left;
        $this->left = "indexof($field,$str)";
        return $this;
    }

    /**
     * @param string $value
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function contains($value) {
        Args::notNull($value,"Value");
        //escape value
        $str = $this->escape($value);
        //get left operand
        $left = $this->left;
        //format left operand
        $this->left = "contains($left,$str)";
        //and finally append comparison
        return $this->compare('ge', 0);
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function length() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "length($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getDate() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "date($field)";
        return $this;
    }
    
    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getYear() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "year($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getFullYear() {
        return $this->getYear();
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getMonth() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "month($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getDay() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "day($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getHours() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "hour($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getMinutes() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
        $this->left = "minute($field)";
        return $this;
    }

    /**
     * @return ClientDataQueryable
     * @throws Exception
     */
    public function getSeconds() {
        Args::notNull($this->left,"Left operand");
        $field = $this->left;
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
            if (is_null($this->lop)) {
                $this->lop = 'and';
            }
            if (isset($this->options->filter))
                $this->options->filter = '(' . $this->options->filter . ') '. $this->lop .' (' . $expr . ')';
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
        //6. filter expression
        else if (is_a($value, 'FilterExpression')) {
            return (string)$value;
        }
        //7. other
        else {
            $str = (string)$value;
            return "'$str'";
        }
    }

}
