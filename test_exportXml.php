<?php
//header("Content-Type: text/plain");
echo '<pre>';

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'Class/Db.php';
$Pdo = Db::getPdo();


echo '<p>Реализовать функцию exportXml($a, $b). $a – путь к xml файлу вида (структура файла приведена ниже), 
//$b – код рубрики. Результат ее выполнения: выбрать из БД товары (и их характеристики, необходимые для формирования файла) 
//выходящие в рубрику $b или в любую из всех вложенных в нее рубрик, сохранить результат в файл $a.<p>';

function exportXml(string $a, int $keyCat) {
//выбираем id категорий с потомками
    $sql = "SELECT * FROM `a_category`";
    $categoryTb = Db::preExec($sql);

//строим типа "дерево"  
    function keyToId(array $arr) {
        $cat = array();
        foreach ($arr as $value) {
            $cat[$value['id']] = $value;
        }
        return $cat;
    }

    function getTree(array $arr) {
        $tree = array();
        foreach ($arr as $id => &$node) {
            //Если нет вложений
            if (!$node['id_parent']) {
                $tree[$id] = &$node;
            } else {
                //Если есть потомки то перебераем массив
                $arr[$node['id_parent']]['childs'][$id] = &$node;
            }
        }
        return $tree;
    }

//выбираем нужную категорию с потомками
    function getCategory(array $arr, string $idCat) {
        $result = array();
        foreach ($arr as $key => $value) {
            if (@$value['id'] == $idCat)
                $result = $value;
            else if (is_array($arr[$key])) {
                $ret = getCategory($value, $idCat);
                if (count($ret))
                    $result = $ret;
            }
        }
        return $result;
    }

    //выбираем нужную категорию с потомками
    function getCategoryId(array $arr) {
        $result = '';
        foreach ($arr as $key => $value) {
            if ($key == 'id') {
                $result .= $value . ',';
            } else if (is_array($arr[$key])) {
                $ret = getCategoryId($value);
                if (count($ret))
                    $result .= $ret;
            }
        }
        return $result--;
    }

    $arrCategoryTree = keyToId($categoryTb);
    $arrCategoryTree = (getTree($arrCategoryTree));
    $arrCategory = (getCategory($arrCategoryTree, $keyCat));
    
    if (empty($arrCategory)) {
        echo "<p style=\"color:red\">Нет категории с таки ID, экпортируем все товары</p>";
            $sql = "SELECT * FROM `a_product` ";
    } else {
    $strCategoryId = rtrim(getCategoryId($arrCategory), ',');
    $sql = "SELECT * FROM `a_product` "
            . "LEFT JOIN  `a_product_category` ON `a_product`.`id` = `a_product_category`.`id_product` "
            . "WHERE `a_product_category`.`id_category` IN ($strCategoryId)";    
    }

    $arrProduct = Db::preExec($sql);

    for ($i = 0; $i < count($arrProduct); $i++) {
        $xmlArr[$i]['product'] = $arrProduct[$i];
        // выбираем цены
        $sql = "SELECT * FROM `a_price` WHERE `a_price`.`id_product`=?";
        $xmlArr[$i]['price'] = Db::preExec($sql, [$arrProduct[$i]['id']]);
        // выбираем свойства
        $sql = "SELECT * FROM `a_property` WHERE `id_product`=?";
        $xmlArr[$i]['property'] = Db::preExec($sql, [$arrProduct[$i]['id']]);
        //выбираем категории
        $sql = "SELECT `a_category`.`name`, `a_product_category`.`id_category` AS `id_c`, `a_product_category`.`id_product` AS `id_p`"
                . "FROM a_product_category "
                . "LEFT JOIN  a_product ON a_product.id = a_product_category.id_product "
                . "LEFT JOIN  a_category ON a_category.id = a_product_category.id_category "
                . "WHERE a_product_category.id_product={$arrProduct[$i]['id']}";
        $xmlArr[$i]['category'] = (Db::preExec($sql));
    }
    
    $dom = new DomDocument("1.0", "windows-1251");
    //root
    $productsRoot=$dom->createElement('Товары');
    $dom->appendChild($productsRoot);
    for ($i = 0; $i < count($xmlArr); $i++) {
        // create child element
        $productElem = $dom->createElement("Товар");
        $productsRoot->appendChild($productElem);
        
        // create attribute node
        $prodAttrCode=  $dom->createAttribute("Код");
        $productElem->appendChild($prodAttrCode);
        // create attribute value node
        $priceValue = $dom->createTextNode($xmlArr[$i]['product']['code']);
        $prodAttrCode->appendChild($priceValue);
        
        // create attribute node
        $prodAttrCode=  $dom->createAttribute("Название");
        $productElem->appendChild($prodAttrCode);
        // create attribute value node
        $priceValue = $dom->createTextNode($xmlArr[$i]['product']['name']);
        $prodAttrCode->appendChild($priceValue);
        
        //цена
        for ($k = 0; $k < count($xmlArr[$i]['price']); $k++) {
            // create child element
            $productPrice = $dom->createElement("Цена");
            $productElem->appendChild($productPrice);
            // create text node
            $productPriceText = $dom->createTextNode($xmlArr[$i]['price'][$k]['price']);
            $productPrice->appendChild($productPriceText);
            // create attribute node
            $prodAttrPrice=  $dom->createAttribute("Тип");
            $productPrice->appendChild($prodAttrPrice);
            // create attribute value node
            $priceValue = $dom->createTextNode($xmlArr[$i]['price'][$k]['price_type']);
            $prodAttrPrice->appendChild($priceValue);
        }
        
        //свойства
        // create child element
        $productProrertys = $dom->createElement("Свойства");
        $productElem->appendChild($productProrertys);
        
        for ($k = 0; $k < count($xmlArr[$i]['property']); $k++) {
            // create child element
            $productProrerty = $dom->createElement($xmlArr[$i]['property'][$k]['property']);
            $productProrertys->appendChild($productProrerty);
            // create text node
            $productProrertyText = $dom->createTextNode($xmlArr[$i]['property'][$k]['value']);
            $productProrerty->appendChild($productProrertyText);
            
            if ($xmlArr[$i]['property'][$k]['atribut_property']) {
            // create attribute node
            $productProrertyAttr = $dom->createAttribute($xmlArr[$i]['property'][$k]['atribut_property']);
            $productProrerty->appendChild($productProrertyAttr);
            // create attribute value node
            $productProrertyAttrVal = $dom->createTextNode($xmlArr[$i]['property'][$k]['atribut_value']);
            $productProrertyAttr->appendChild($productProrertyAttrVal);
            }
        }
        
        //разделы
        // create child element
        $productCategorys = $dom->createElement("Разделы");
        $productElem->appendChild($productCategorys);
        
        for ($k = 0; $k < count($xmlArr[$i]['category']); $k++) {
            // create child element
            $productCategory = $dom->createElement('Раздел');
            $productCategorys->appendChild($productCategory);
            // create text node
            $productCategoryText = $dom->createTextNode($xmlArr[$i]['category'][$k]['name']);
            $productCategory->appendChild($productCategoryText);
        }
    }
       

//    $dom->saveXML();
    $dom->save('domXml.xml');
    
//    $xml_header = '<?xml version="1.0" encoding="UTF-8"? ><Товары></Товары>';
//    $xml = new SimpleXMLElement($xml_header, null, false);
//    for ($i = 0; $i < count($xmlArr); $i++) {
//        $xml->addChild("Товар");
//        $xml->Товар[$i]->addAttribute('Код', $xmlArr[$i]['product']['code']);
//        $xml->Товар[$i]->addAttribute('Название', $xmlArr[$i]['product']['name']);
//        for ($k = 0; $k < count($xmlArr[$i]['price']); $k++) {
//            $xml->Товар[$i]->addChild("Цена", $xmlArr[$i]['price'][$k]['price']);
//            $xml->Товар[$i]->Цена[$k]->addAttribute('Тип', $xmlArr[$i]['price'][$k]['price_type']);
//        }
//        $xml->Товар[$i]->addChild("Свойства");
//        for ($k = 0; $k < count($xmlArr[$i]['property']); $k++) {
//            $xml->Товар[$i]->Свойства->addChild($xmlArr[$i]['property'][$k]['property'], $xmlArr[$i]['property'][$k]['value']);
//            $prpTmp = $xmlArr[$i]['property'][$k]['property'];
//            if ($xmlArr[$i]['property'][$k]['atribut_property']) {
//                $xml->Товар[$i]->Свойства->$prpTmp->addAttribute($xmlArr[$i]['property'][$k]['atribut_property'], $xmlArr[$i]['property'][$k]['atribut_value']);
//            }
//        }
//        //добавляем разделы
//        $xml->Товар[$i]->addChild("Разделы");
//        for ($k = 0; $k < count($xmlArr[$i]['category']); $k++) {
//            $xml->Товар[$i]->Разделы->addChild('Раздел', $xmlArr[$i]['category'][$k]['name']);
//        }
//    }
//    //сохраняем
//    if (!$xml->saveXML($a)) {
//        echo "<p style=\"color:red\">Не удалось сохранить XML в файл \"$a\"</p>";
//    } else {
//        echo "<p>Данные сохранены в файл \"$a\"</p>";
//    };
    
    
}

exportXml('exportFromDb.xml', '20');

//проверить существование категории
echo "</pre>";