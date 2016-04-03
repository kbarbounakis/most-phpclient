# most-phpclient
[MOST Web Framework](https://github.com/kbarbounakis/most-web) PHP Client Library

![MOST Web Framework Logo](https://www.themost.io/assets/images/most_logo_sw_240.png)

### Usage

If you don't have a MOST Web Framework application clone [MOST Web Framework OMS Demo](https://github.com/kbarbounakis/most-web-oms-demo) application and follow the installation instructions.

Create a new PHP project and use MOST Web Framework PHP Client to communicate with this application.

### Authentication

Use basic authentication:

    context->authenticate("alexis.rees@example.com","user");

Use cookie based authentication:

    //get cookie from request
    context.getService().setCookie($response->getCookies());

### ClientDataContext Class

#### model(name)

Gets an instance of ClientDataModel class based on the given name.

    $result = $context->model("Order")
        ->where("orderStatus")->equal(1)
        ->getItems();
    var_dump($result);

#### getService()

Gets the instance of ClientDataService associated with this data context.

    print $context->getService()->getBase();

#### setService(service)

Associates the given ClientDataService instance with this data context.

    $context->setService(new MyDataService("http://data.example.com"));

### ClientDataModel Class

#### getName()

Gets a string which represents the name of this data model.

#### getService()

Gets the instance of ClientDataService associated with this data model.

#### remove(obj)

Removes the given item or array of items.

    $order = new stdClass();
    $order->id = 23;
    $context->model("Order")->remove($order);
    var_dump($order);

#### save(obj)

Creates or updates the given item or array of items.

    $result = $context->model("Order")
        ->where("id")
        ->equal("23")
        ->getItem();
    $result->orderStatus = 3;
    $context->model("Order")->save($result);
    var_dump($result);

#### getSchema()

Returns the JSON schema of this data model.

    $context = $this->getContext();
    $schema = $context->model("Group")->getSchema();
    var_dump($schema);

#### select(...attr)

Initializes and returns an instance of ClientDataQueryable class by selecting an attribute or a collection of attributes.

    $result = $context->model("User")
        ->where("name")
        ->equal("alexis.rees@example.com")
        ->select("id","name","description")
        ->first();
    var_dump($result) ;

#### skip(num)

Initializes and returns an instance of ClientDataQueryable class by specifying the number of records to be skipped.

    $context = $this->getContext();
    $result = $context->model("Order")
        ->skip(10)
        ->take(10)
        ->getItems();
    var_dump($result);

#### take(num)

Initializes and returns an instance of ClientDataQueryable class by specifying the number of records to be taken.

    $context = $this->getContext();
    $result = $context->model("Order")
        ->take(10)
        ->getItems();
    var_dump($result);

#### where(attr)

Initializes a comparison expression by using the given attribute as left operand
and returns an instance of ClientDataQueryable class.

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderedItem/category")->equal("Laptops")
        ->take(10)
        ->getItems();
    var_dump($result);

### ClientDataQueryable Class

ClientDataQueryable class enables developers to perform simple and extended queries against data models.
The ClienDataQueryable class follows [DataQueryable](https://docs.themost.io/most-data/DataQueryable.html)
which is introduced by [MOST Web Framework ORM server-side module](https://github.com/kbarbounakis/most-data).

#### Logical Operators

Either (Or):

    $result = $context->model("Product")
        ->where("category")->equal("Desktops")
        ->either("category")->equal("Laptops")
        ->orderBy("price")
        ->take(5)
        ->getItems();
    var_dump($result);

Also (And):

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("category")->equal("Laptops")
        ->also("price")->between(200,750)
        ->orderBy("price")
        ->take(5)
        ->getItems();
    var_dump($result);

#### Comparison Operators

Equal:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("category")
        ->equal("Laptops")
        ->orderBy("price")
        ->getItems();
    var_dump($result);

Not equal:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("category")
        ->notEqual("Laptops")
        ->also("category")
        ->notEqual("Desktops")
        ->orderBy("price")
        ->getItems();
    var_dump($result);

Greater than:

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

Greater or equal:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("price")->greaterOrEqual(1395.9)
        ->orderByDescending("price")
        ->take(10)
        ->getItems();
    var_dump($result);

Lower than:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("price")->lowerThan(263.56)
        ->orderBy("price")
        ->take(10)
        ->getItems();
    var_dump($result);

Lower or equal:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("price")->lowerOrEqual(263.56)
        ->also("price")->greaterOrEqual(224.52)
        ->orderBy("price")
        ->take(5)
        ->getItems();
    var_dump($result);

Contains:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("name")->contains("Book")
        ->also("category")->equal("Laptops")
        ->orderBy("price")
        ->take(5)
        ->getItems();
    var_dump($result);

Between:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("category")->equal("Laptops")
        ->either("category")->equal("Desktops")
        ->andAlso("price")->between(200,750)
        ->orderBy("price")
        ->take(5)
        ->getItems();
    var_dump($result);

#### Aggregate Functions

Count:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->select("category", "count(id) as total")
        ->groupBy("category")
        ->orderByDescending("count(id)")
        ->getItems();
    var_dump($result);

Min:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->select("category", "min(price) as minimumPrice")
        ->where("category")->equal("Laptops")
        ->either("category")->equal("Desktops")
        ->groupBy("category")
        ->orderByDescending("min(price)")
        ->getItems();
    var_dump($result);

Max:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->select("category", "max(price) as maximumPrice")
        ->where("category")->equal("Laptops")
        ->getItem();
    var_dump($result);

### String Functions:

Index Of:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("name")->indexOf("Intel")
        ->greaterOrEqual(0)
        ->getItems();
    var_dump($result);

Substring:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("name")->substr(6,4)
        ->equal("Core")
        ->getItems();
    var_dump($result);

Starts with:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("name")->startsWith("Intel Core")
        ->equal(true)
        ->getItems();
    var_dump($result);

Ends with:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("name")->endsWith("Edition")
        ->equal(true)
        ->getItems();
    var_dump($result);

Lower case:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("category")->toLowerCase()
        ->equal("laptops")
        ->getItems();
    var_dump($result);

Upper case:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("category")->toUpperCase()
        ->equal("LAPTOPS")
        ->getItems();
    var_dump($result);

#### Date Functions:

Date:

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderDate")->getDate()
        ->equal("2015-04-18")
        ->getItems();
    var_dump($result);

Month:

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderDate")->getMonth()
        ->equal(4)
        ->getItems();
    var_dump($result);

Day:

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderDate")->getMonth()->equal(4)
        ->also("orderDate")->getDay()->lowerThan(15)
        ->getItems();
    var_dump($result);

Year:

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderDate")->getMonth()->equal(5)
        ->also("orderDate")->getDay()->lowerOrEqual(10)
        ->also("orderDate")->getFullYear()->equal(2015)
        ->getItems();
    var_dump($result);

Hours:

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderDate")->getMonth()->equal(5)
        ->also("orderDate")->getDay()->lowerOrEqual(10)
        ->also("orderDate")->getHours()->between(10,18)
        ->getItems();
    var_dump($result);

Minutes:

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderDate")->getMonth()->equal(5)
        ->also("orderDate")->getHours()->between(9,17)
        ->also("orderDate")->getMinutes()->between(1,30)
        ->getItems();
    var_dump($result);

Seconds:

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("orderDate")->getMonth()->equal(5)
        ->also("orderDate")->getHours()->between(9,17)
        ->also("orderDate")->getMinutes()->between(1,30)
        ->also("orderDate")->getSeconds()->between(1,45)
        ->getItems();
    var_dump($result);

#### Math Functions

Round:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("price")->round()->lowerOrEqual(177)
        ->getItems();
    var_dump($result);

Floor:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("price")->floor()->lowerOrEqual(177)
        ->getItems();
    var_dump($result);

Ceiling:

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("price")->ceil()->lowerOrEqual(177)
        ->getItems();
    var_dump($result);

#### Methods

##### also(name)

Prepares a logical AND expression.

Parameters:
- name: The name of field that is going to be used in this expression

##### andAlso(name)

Prepares a logical AND expression.
If an expression is already defined, it will be wrapped with the new AND expression

Parameters:
- name: The name of field that is going to be used in this expression

        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")->equal("Laptops")
            ->either("category")->equal("Desktops")
            ->andAlso("price")->round()->lowerOrEqual(250)
            ->getItems();
        var_dump($result);

##### expand(...attr)

Parameters:
- attr: A param array of strings which represents the field or the array of fields that are going to be expanded.
If attr is missing then all the previously defined expandable fields will be removed

Defines an attribute or an array of attributes to be expanded in the final result. This operation should be used
when a non-expandable attribute is required to be expanded in the final result.

    $context = $this->getContext();
    $result = $context->model("Order")
        ->where("customer")->equal(337)
        ->orderByDescending("orderDate")
        ->expand("customer")
        ->getItem();
    var_dump($result);

##### first()

Executes the specified query and returns the first item.

    $context = $this->getContext();
    $result = $context->model("User")
        ->where("name")
        ->equal("alexis.rees@example.com")
        ->first();
    var_dump($result);

##### getItem()

Executes the specified query and returns the first item.

    $context = $this->getContext();
    $result = $context->model("User")
        ->where("name")
        ->equal("alexis.rees@example.com")
        ->getItem();
    var_dump($result);

##### getItems()

Executes the specified query and returns an array of items.

    $context = $this->getContext();
    $result = $context->model("Product")
        ->where("category")
        ->equal("Laptops")
        ->getItems();
    var_dump($result);

##### getList()

Executes the underlying query and returns a result set based on the specified paging parameters. The result set
contains the following attributes:

- total (number): The total number of records
- skip (number): The number of skipped records
- records (Array): An array of objects which represents the query results.

        $context = $this->getContext();
        $result = $context->model("Product")
            ->where("category")
            ->equal("Laptops")
            ->take(10)
            ->getList();
        var_dump($result);

##### skip(val)

Prepares a paging operation by skipping the specified number of records

Parameters:
- val: The number of records to be skipped

        $context = $this->getContext();
        $result = $context->model("Order")
            ->skip(10)
            ->take(10)
            ->getItems();
        var_dump($result);

##### take(val)

Prepares a data paging operation by taking the specified number of records

Parameters:
- val: The number of records to take

        $context = $this->getContext();
        $result = $context->model("Order")
            ->take(10)
            ->getItems();
        var_dump($result);

##### groupBy(...attr)

Prepares a group by expression

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

##### orderBy(...attr)

Prepares an ascending sorting operation

    $context = $this->getContext();
    $result = $context->model("Product")
        ->orderBy("category","name")
        ->take(5)
        ->getItems();
    var_dump($result);

##### thenBy(...attr)

 Continues a ascending sorting operation

    $context = $this->getContext();
    $result = $context->model("Product")
        ->orderBy("category")
        ->thenBy("name")
        ->take(5)
        ->getItems();
    var_dump($result);

##### orderByDescending(...attr)

 Prepares an descending sorting operation

    $context = $this->getContext();
    $result = $context->model("Product")
        ->orderByDescending("category","name")
        ->take(5)
        ->getItems();
    var_dump($result);

##### thenByDescending(...attr)

 Continues a descending sorting operation

    $context = $this->getContext();
    $result = $context->model("Product")
        ->orderByDescending("category")
        ->thenByDescending("name")
        ->take(5)
        ->getItems();
    var_dump($result);