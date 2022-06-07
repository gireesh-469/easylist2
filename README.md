# PHP EasyList - Version 1.0
#### by 
## BTL Team DigitalMesh



### Introduction

EasyList is a PHP plugin which lets the developer to implement listing,pagination and filtering with less code and effort.With the help of composer the plugin can be added to projects which are developed using any frameworks in PHP which makes EasyList versatile.

### Requirements
- PHP version supported from 5.6 to 7.4(Testing is in progress for PHP 8)
- JQuery and Bootstrap

### Supporting Databases
EasyList Supports the following databases
- MySQL
- POSTGRESQL
- MSSQL

### Features
- Listing can be easily done.
- Add pagination according to the listing 
- Provides the auto generated query for listing which in turn helps for debugging.

### Installation
EasyList is supported in PHP versions 5.6 to 7.4.The plugin requires Bootstrap and JQuery preinstalled.
##### Using Composer
If the respective project includes composer EasyList can be added to the project by the following command.
```sh 
composer require easylist/easylist2
```
##### Git
EasyList can be downloaded or cloned from 
```sh
https://github.com/pknairr/easylist2.git
```
##### Configuring 
If the plugin was included to the project through composer. The file 
```sh
vendor/easylist2/config/EasyListConfig.php
```
needs to be configured.

Copy this file to any desired location in the project 
The line number 19 of this file contains the following code
```sh
19 $configPath = 'app/config/EasyListConfig.php';
```
Change the path mentioned there to the path of the configuration file of the project. For Laravel projects this would be the path of the .env file.

### Usage
There are three function for listing,pagination and rendering filtered result to the DOM.
- Page
- List
- Pager

Page function have to be added in the controller. List and Pager function should be added to the view.

Add the file EasyList.php
```sh
require 'vendor/easylist2/EasyList.php'
```
to the  desired code block.

##### Page Function
This function is called from the controller part.

```sh
DynaList::Page(array());
```
There are some parameters that needs to be given in the array. The params are mentioned below
| Param | Description |
| ------ | ------ |
| select |  Comma separated column list |
| from   |  From table with alias       |
| joins  |  Join statements             |
| conditions |  Array which consist of the conditions that has to be applied in the <Where> clause. Its to mention extra conditions other than filters |
| group | Comma separated group names  |
| having| Array which consist of the conditions and values for <having> clause|
| having_columns| Comma separated list of columns used in HAVING cluase.|
|filters|Array which consist of conditions for the filters from front end|
|order|Comma separated order coluns with ASC/DESC key |
|return_data|Format of the data that has to be returned|
|view|view location if return_data is HTML|
|view_variables|Array variables that has to be passed to the view|
|page|page number|
|pagination|Used to mention whether pagination is present|
|page_size|Number of records that has to be shown in each page|

###### Examples
 **select**
 ```sh
 DynaList::Page(array(
        "select" => "id,name" 
 ));
 ```
 
 **from**
 ```sh
  DynaList::Page(array(
        "select" => "id,name",
        "from"   => "user AS usr"
 ));
 ```
 
 **joins**
 ```sh
  DynaList::Page(array(
        "select" => "id,name",
        "from"   => "user AS usr",
        "joins"  => " INNER JOIN login AS lgn  ON usr.id = lgn.user_id"
 ));
 ```
 **conditions**
  ```sh
  DynaList::Page(array(
        "select"     => "id,name",
        "from"       => "user AS usr",
        "conditions" => array(
            array("condition" => "id > ?","value" => 10, "operation" => "AND"),
            array("condition" => "code <> ?","value" => "AU", "operation" => "AND")
        )
 ));
 ```
 | Option | Description |Possible values
| ------ | ------ |------|
|condition|The condition which has to be included in the where clause|-|
|value|The values which has to be added to the respective where clause|-|
|operation|This option specifies which operation binds the condition to other consitions|AND/OR|
 **group**
  ```sh
  DynaList::Page(array(
        "select" => "id,name",
        "from"   => "user AS usr",
        "group"  => "code"
 ));
 ```
 
 **having**
  ```sh
  DynaList::Page(array(
        "select" => "id,name",
        "from"   => "customer AS cust",
        "group"  => "code",
        "having" => array(
            array("condition" => "count(*) > ?", "value" => 5, "operation" => "OR")
        )
 ));
 ```
 
 **having_columns**
   ```sh
  DynaList::Page(array(
        "select" => "id,name",
        "from"   => "customer AS cust",
        "group"  => "code",
        "having" => array(
            array("condition" => "age = ?", "value" => 25, "operation" => "AND")
            array("condition" => "count(*) < ?", "value" => 10, "operation" => "AND")
        ),
        "having_columns"        => "age" 
 ));
 ```
 
 **filters**
 ```sh
  DynaList::Page(array(
        "select"  => "id,name",
        "from"    => "customer AS cust",
        "filters" => array(
            array("condition" => "cust.name = ?", "form-field" => "txt_name", 
                 "operation" => "AND", "type"=>"STRING",
                 "consider_empty" => "YES"),
            array("condition" => "cust.created_date = ?", "form-field" => "c_date", 
            "operation" => "OR", "type"=>"DATE", 
            "datetime_format_from"=>"d/m/Y", "datetime_format_to"=>"Y-m-d", 
            "consider_empty" => "NO")
        )
 ));
 ```
  | Option | Description |Possible values
| ------ | ------ |------|
|condition|The condition which has to be included in the where clause|-|
|form-field|The field from which the value is taken for the particular condition|-|
|operation|This option specifies which operation binds the condition to other consitions|AND/OR|
|type|The type of the form-field|BOOLEAN/DATE/DATETIME/TIME/INTEGER/STRING.|
|datetime_format_from|Date time format of th field|-|
|datetime_format_to|The date time format to which the field value has to be converted|-|
|consider_empty|If the option consider_empty is YES then the field's condition will be considered for the query created automatically otherwise it will be ruled out. By default this option's value is NO.|YES?NO|

 ``If both conditions and filters options are present filters will have more preference. ``

**order**
```sh
DynaList::Page(array(
    "select" => "id,name",
    "from"   => "customer AS cust",
    "order" => "name ASC"
));
```

**return_data**
```sh
DynaList::Page(array(
    "select" => "id,name",
    "from"   => "customer AS cust",
    "return_data" => "JSON"
));
```
``return_data can have  HTML / JSON / OBJECT / QUERY``

**view**
```sh
DynaList::Page(array(
    "select"      => "id,name",
    "from"        => "customer AS cust",
    "return_data" => "HTML"
    "view"        => "views/list.php"
));
```
**view_variables**
```sh
DynaList::Page(array(
    "select"         => "id,name",
    "from"           => "customer AS cust",
    "return_data"    => "HTML",
    "view"           => "views/list.php",
    "view_variables" => array("ids"=>$ids,"names"=>$names)
));
```

**page**
```sh
DynaList::Page(array(
    "select"         => "id,name",
    "from"           => "customer AS cust",
    "return_data"    => "HTML",
    "view"           => "views/list.php",
    "view_variables" => array("ids"=>$ids,"names"=>$names),
    "page"           => 1
));
```
**pagination**
```sh
DynaList::Page(array(
    "select"         => "id,name",
    "from"           => "customer AS cust",
    "return_data"    => "JSON",
    "page"           => 1,
    "pagination"     => "YES"
));
```
``Default value for pagination is YES``

**page_size**
```sh
DynaList::Page(array(
    "select"         => "id,name",
    "from"           => "customer AS cust",
    "return_data"    => "JSON",
    "page"           => 1,
    "pagination"     => "YES"
    "page_size"      => 25
));
```

##### List Function
List function is used in the view page. This function directs the control to the URL from where the data should be formatted and shown. It places the resulting list to the desired area of the DOM.

```sh
DynaList::List(array());
```

There are some parameters that needs to be given in the array. The params are mentioned below
| Param | Description |
| ------ | ------ |
|url|Target location of controller/action function|
|form_id|Id of the filter form|
|target_div_id|Div id where we want to show the output|
|button_id|ID of the filtering button|
|autolist|Can have true / false : If true will show the output first time without pressing button|
|column|Specify the columns of the list|
|pager|Location where we want to show the page controller|
|action|To point out the action columns where edit,delete and show links should be shown|
