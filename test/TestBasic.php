<?php

/**
 * Created by PhpStorm.
 * User: kbarbounakis
 * Date: 3/4/2016
 * Time: 1:38 μμ
 */

require "../MostDataClient.php";
require "TestConfig.php";

class TestBasic extends PHPUnit_Framework_TestCase
{

    private $context_;

    private function getContext() {
        if ($this->context_ != null) {
            return $this->context_;
        }
        $this->context_ = new ClientDataContext(REMOTE_URL);
        $this->context_->authenticate(REMOTE_USER,REMOTE_PASSWORD);
        return $this->context_;
    }

    public function testGetData()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderStatus")->equal(1)
            ->getItems();
        var_dump($result);
    }

    public function testFirst()
    {
        $context = $this->getContext();
        $result = $context->model("User")
            ->where("name")
            ->equal("alexis.rees@example.com")
            ->first();
        var_dump($result);
    }

    public function testGetItem()
    {
        $context = $this->getContext();
        $result = $context->model("User")
            ->where("name")
            ->equal("alexis.rees@example.com")
            ->getItem();
        var_dump($result);
    }

    public function testGetItems()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")
            ->equal("Laptops")
            ->getItems();
        var_dump($result) ;
    }

    public function testGetList()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")
            ->equal("Laptops")
            ->take(10)
            ->getList();
        var_dump($result);
    }

    public function testSelect()
    {
        $context = $this->getContext();
        $result = $context->model("User")
            ->where("name")
            ->equal("alexis.rees@example.com")
            ->select("id","name","description")
            ->first();
        var_dump($result) ;
    }

    public function testEqual()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")
            ->equal("Laptops")
            ->orderBy("price")
            ->getItems();
        var_dump($result) ;
    }

    public function testNotEqual()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")
            ->notEqual("Laptops")
            ->also("category")
            ->notEqual("Desktops")
            ->orderBy("price")
            ->getItems();
        var_dump($result) ;
    }

    public function testSkip()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->skip(10)
            ->take(10)
            ->getItems();
        var_dump($result) ;
    }

    public function testTake()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->take(10)
            ->getItems();
        var_dump($result);
    }

    public function testWhere()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderedItem/category")->equal("Laptops")
            ->take(10)
            ->getItems();
        var_dump($result);
    }

    public function testOr()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")->equal("Desktops")
            ->either("category")->equal("Laptops")
            ->orderBy("price")
            ->take(5)
            ->getItems();
        var_dump($result) ;
    }

    public function testAnd()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")->equal("Laptops")
            ->also("price")->between(200,750)
            ->orderBy("price")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testGreaterThan()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderedItem/price")->greaterThan(968)
            ->also("orderedItem/category")->equal("Laptops")
            ->also("orderStatus/alternateName")->notEqual("OrderCancelled")
            ->select("id",
                "orderStatus/name as orderStatusName",
                "customer/description as customerDescription",
                "orderedItem")
            ->orderByDescending("orderDate")
            ->take(10)
            ->getItems();
        var_dump($result);
    }

    public function testGreaterOrEqual()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("price")->greaterOrEqual(1395.9)
            ->orderByDescending("price")
            ->take(10)
            ->getItems();
        var_dump($result);
    }

    public function testLowerThan()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("price")->lowerThan(263.56)
            ->orderBy("price")
            ->take(10)
            ->getItems();
        var_dump($result);
    }

    public function testLowerOrEqual()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("price")->lowerOrEqual(263.56)
            ->also("price")->greaterOrEqual(224.52)
            ->orderBy("price")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testContains()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("name")->contains("Book")
            ->also("category")->equal("Laptops")
            ->orderBy("price")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testBetween()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")->equal("Laptops")
            ->either("category")->equal("Desktops")
            ->andAlso("price")->between(200,750)
            ->orderBy("price")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testCount()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->select("category", "count(id) as total")
            ->groupBy("category")
            ->orderByDescending("count(id)")
            ->getItems();
        var_dump($result);
    }

    public function testMin()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->select("category", "min(price) as minimumPrice")
            ->where("category")->equal("Laptops")
            ->either("category")->equal("Desktops")
            ->groupBy("category")
            ->orderByDescending("min(price)")
            ->getItems();
        var_dump($result);
    }

    public function testMax()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->select("category", "max(price) as maximumPrice")
            ->where("category")->equal("Laptops")
            ->getItem();
        var_dump($result);
    }

    public function testIndexOf()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("name")->indexOf("Intel")
            ->greaterOrEqual(0)
            ->getItems();
        var_dump($result);
    }

    public function testSubstring()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("name")->substr(6,4)
            ->equal("Core")
            ->getItems();
        var_dump($result);
    }

    public function testStartsWith()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("name")->startsWith("Intel Core")
            ->equal(true)
            ->getItems();
        var_dump($result);
    }

    public function testEndsWith()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("name")->endsWith("Edition")
            ->equal(true)
            ->getItems();
        var_dump($result);
    }

    public function testLowerCase()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")->toLowerCase()
            ->equal("laptops")
            ->getItems();
        var_dump($result);
    }

    public function testUpperCase()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")->toUpperCase()
            ->equal("LAPTOPS")
            ->getItems();
        var_dump($result);
    }

    public function testGetDate()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderDate")->getDate()
            ->equal("2015-04-18")
            ->getItems();
        var_dump($result);
    }

    public function testGetMonth()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderDate")->getMonth()
            ->equal(4)
            ->getItems();
        var_dump($result);
    }

    public function testGetDay()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderDate")->getMonth()->equal(4)
            ->also("orderDate")->getDay()->lowerThan(15)
            ->getItems();
        var_dump($result);
    }

    public function testGetYear()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderDate")->getMonth()->equal(5)
            ->also("orderDate")->getDay()->lowerOrEqual(10)
            ->also("orderDate")->getFullYear()->equal(2015)
            ->getItems();
        var_dump($result);
    }

    public function testGetHours()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderDate")->getMonth()->equal(5)
            ->also("orderDate")->getDay()->lowerOrEqual(10)
            ->also("orderDate")->getHours()->between(10,18)
            ->getItems();
        var_dump($result);
    }

    public function testGetMinutes()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderDate")->getMonth()->equal(5)
            ->also("orderDate")->getHours()->between(9,17)
            ->also("orderDate")->getMinutes()->between(1,30)
            ->getItems();
        var_dump($result);
    }

    public function testGetSeconds()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("orderDate")->getMonth()->equal(5)
            ->also("orderDate")->getHours()->between(9,17)
            ->also("orderDate")->getMinutes()->between(1,30)
            ->also("orderDate")->getSeconds()->between(1,45)
            ->getItems();
        var_dump($result);
    }

    public function testRound()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("price")->round()->lowerOrEqual(177)
            ->getItems();
        var_dump($result);
    }

    public function testFloor()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("price")->floor()->lowerOrEqual(177)
            ->getItems();
        var_dump($result);
    }

    public function testCeil()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("price")->ceil()->lowerOrEqual(177)
            ->getItems();
        var_dump($result);
    }

    public function testAndAlso()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")->equal("Laptops")
            ->either("category")->equal("Desktops")
            ->andAlso("price")->round()->lowerOrEqual(250)
            ->getItems();
        var_dump($result);
    }

    public function testExpand()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("customer")->equal(337)
            ->orderByDescending("orderDate")
            ->expand("customer")
            ->getItem();
        var_dump($result);
    }

    public function testGroupBy()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->select("orderedItem/model as productModel",
                "orderedItem/name as productName",
                "count(id) as orderCount")
            ->where("orderDate")->getFullYear()->equal(2015)
             ->groupBy("orderedItem")
             ->orderByDescending("count(id)")
             ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testOrderBy()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->orderBy("category","name")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testThenBy()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->orderBy("category")
            ->thenBy("name")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testOrderByDescending()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->orderByDescending("category","name")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testThenByDescending()
    {
        $context = $this->getContext();
        $result = $context->model("Product")
            ->orderByDescending("category")
            ->thenByDescending("name")
            ->take(5)
            ->getItems();
        var_dump($result);
    }

    public function testSave()
    {
        $context = $this->getContext();
        $result = $context->model("Order")
            ->where("id")
            ->equal("23")
            ->getItem();
        $result->orderStatus = 3;
        $context->model("Order")->save($result);
        var_dump($result);
    }

    public function testRemove()
    {
        $context = $this->getContext();
        $order = new stdClass();
        $order->id = 23;
        $context->model("Order")->remove($order);
        var_dump($order);
    }

    public function testGetSchema()
    {
        $context = $this->getContext();
        $schema = $context->model("Group")->getSchema();
        var_dump($schema);
    }
}