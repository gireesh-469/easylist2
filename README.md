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
| having| Array which consist of the conditions and values for <having > clause|
| having_columns| Comma separated list of columns used in HAVING cluase. This is to prevent count query error when HAVING is used |
|filters|Array which consist of conditions for the filters from front end|
|order|Comma separated order coluns with ASC/DESC key |
|return_data|Format of the data that has to be returned|
|view|view location if return_data is HTML|
|view_variables|Array variables that has to be passed to the view|
|page|page number|
|pagination|Used to mention whether pagination is present|
|page_size|Number of records that has to be shown in each page|

