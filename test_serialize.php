<?php
class ClassName {

public $name='its name';
public $value='its value';
public $property='its property';
    function __construct() {
        
    }
            

}
$obj=new ClassName;
var_dump($obj);
$obj_ser= serialize($obj);
var_dump($obj_ser);
$arValue = explode(':', $obj_ser);
var_dump($arValue);
$obj_unser= serialize($obj_ser);
var_dump($obj_unser);

var_dump(serialize('feeld'));