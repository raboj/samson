<?php

require_once 'Class/Db.php';
$Pdo = Db::getPdo();

echo '<pre>';

echo '<p>convertString($a, $b). Результат ее выполнение: если в строке $a содержится 2 и более подстроки $b, 
то во втором месте заменить подстроку $b на инвертированную подстроку.</p><br>';

$str = 'груша яблоко груша яблоко груша яблоко';
$subStr = 'яблоко';

//$str='pear apple pear apple pear apple';
//$subStr='apple';

function strRever(string $str) {
//был вариант с циклом и конкатенацией в обратном порядке, но громоздко...
//strrev - не работает с русскими символами
    $bArr = preg_split('//u', $str, NULL, PREG_SPLIT_NO_EMPTY);
    $bArr = array_reverse($bArr);
    return implode('', $bArr);
}

function convertString(string $a, string $b) {
    if (substr_count($a, $b) >= 2) {
//находим позицию первой подстроки, использую mb_ и указываю кодировку, но эффекта это не дало
        $subPos = mb_strpos($a, $b, 0, 'UTF-8');
// находим позицию второй подстроки, делаем смещение на позицию первой подстроки + разммер подстроки
        $subPos = mb_strpos($a, $b, $subPos + mb_strlen($b, 'UTF-8'), 'UTF-8');
        $bRev = strRever($b);
//заменяем подстроку, не смог решить проблему с кодировкой, не правильно считает позицию символов
//понятно почему, но не понятно как решить, потратил несколько часов...
//iconv - не помог...
        return substr_replace($a, $bRev, $subPos, mb_strlen($bRev, 'UTF-8'));
    }
}

// второй вариант, не мой, stackoverflow
function convertString2(string $a, string $b) {
    if (substr_count($a, $b) >= 2) {
        $bRev = strRever($b);
        $occurrence = 2;
        return preg_replace("/^((?:(?:.*?$b){" . --$occurrence . "}.*?))$b/", "$1$bRev", $a);
    }
}

echo $str . ' <b>- исходная строка</b><br>';
echo $subStr . ' <b>- подстрока</b><br>';
echo "<h4>Вариант 1 (не работает с кирилицей)</h4> " . convertString($str, $subStr);
echo "<h4>Вариант 2</h4>" . convertString2($str, $subStr);

echo "<hr>";

echo "<p>mySortForKey(\$a, \$b). \$a – двумерный массив вида [['a'=>2,'b'=>1],['a'=>1,'b'=>3]], 
\$b – ключ вложенного массива. Результат ее выполнения: двумерном массива \$a отсортированный по возрастанию значений для ключа \$b. 
В случае отсутствия ключа \$b в одном из вложенных массивов, выбросить ошибку класса Exception с индексом неправильного массива.<p><br>";

$a = [['a' => 2, 'b' => 1], ['a' => 1, 'b' => 3], ['a' => 4, 'b' => 2]];
$b = 'b';

function mySortForKey(array &$a, string $b) {
    foreach ($a as $key => $value) {
        try {
            if (!array_key_exists($b, $value)) {
                throw new Exception('неправильный индекс массива: ' . $key);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return;
        }
    }

    function sortForKey($key) {
        return function ($a, $b) use ($key) {
            return $a[$key] <=> $b[$key];
        };
    }

    usort($a, sortForKey($b));

    return $a;
}

echo "<h4>Массив до</h4>";
var_export($a);
echo "<h4>Массив после</h4>";
var_export(mySortForKey($a, $b));

echo "<hr>";

echo '<p>Реализовать функцию importXml($a). $a – путь к xml файлу (структура файла приведена ниже). 
Результат ее выполнения: прочитать файл $a и импортировать его в созданную БД.</p>';

function importXml(string $a) {
    $countProduct = 0;
    if (is_readable($a)) {
        $products = simplexml_load_file($a);
    } else {
        echo 'Ошибка чтения файла';
    }
// добавляем товар
    foreach ($products->Товар as $product) {
        //проеряем существует ли товар с таким кодом
        $sql = "SELECT * FROM `a_product` WHERE `code`=?";
        $productExist = Db::preExec($sql, [$product->attributes()->Код]);
        if ($productExist) {
            echo "<p style=\"color:red\">Товар \"{$productExist[0]['name']}\" с кодом \"{$productExist[0]['code']}\" уже существует и не был добавлен<p>";
        } else {
            Db::getPdo()->beginTransaction();
            try {
                //добавляем товар
                $producrArr = [NULL, $product->attributes()->Код, $product->attributes()->Название];
                $sql = "INSERT INTO `a_product` (`id`, `code`, `name`) VALUES (?, ?, ?)";
                Db::preExec($sql, $producrArr);
                $idProduct = Db::getPdo()->lastInsertId();
                // добавляем цены
                foreach ($product->Цена as $prices) {
                    $priceArr = [$idProduct, $prices->attributes()->Тип, $prices];
                    $sql = "INSERT INTO `a_price`(`id_product`, `price_type`, `price`) VALUES (?, ?, ?)";
                    Db::preExec($sql, $priceArr);
                }
                //добавляем свойства
                foreach ($product->Свойства as $propertys) {
                    foreach ($propertys as $property => $propertyValue) {
                        $propertyAttr = current($propertyValue->attributes());
                        if (is_array($propertyAttr)) {
                            $key = key($propertyAttr);
                            $value = current($propertyAttr);
                        } else {
                            $key = '';
                            $value = '';
                        }
                        $propertyArr = [$idProduct, $property, $propertyValue, $key, $value];
                        $sql = "INSERT INTO `a_property`(`id_product`, `property`, `value`, `atribut_property`, `atribut_value`) VALUES (?, ?, ?, ?, ?)";
                        Db::preExec($sql, $propertyArr);
                    }
                }
                //добавляем категории, лучше бы они существовали заранее... но, для данной ситуации - добавляем
                $idParentCategory = '';
                foreach ($product->Разделы->Раздел as $category) {
                    // проверяем, существует ли категория 
                    $sql = "SELECT * FROM `a_category` WHERE `name`='" . $category . "'";
                    $categoryTb = Db::preExec($sql);
                    if ($categoryTb) {
                        // берем id для связи и запоминаем ее как родителя
                        $idCategory = ($categoryTb[0]['id']);
                        $idParentCategory = $idCategory;
                    } else {
                        $sql = "INSERT INTO `a_category`(`id`, `id_parent`, `code`, `name`) "
                                . "VALUES (NULL,?,'',?)";
                        $categoryArr = [$idParentCategory, $category];
                        Db::preExec($sql, $categoryArr);
                        $idCategory = Db::getPdo()->lastInsertId();
                        // сохраняем id родителя для следующего раздела товара
                        $idParentCategory = $idCategory;
                    };
                    //делаем связь
                    $sql = "INSERT INTO `a_product_category`(`id_category`, `id_product`) VALUES (?,?)";
                    Db::preExec($sql, [$idCategory, $idProduct]);
                }
            } catch (Exception $e) {
                echo "Ошибка: " . $e->getMessage();
                Db::getPdo()->rollBack();
            }

            if (Db::getPdo()->commit() == TRUE) {
                $countProduct++;
            }
        }
    }
    echo "<p>Добавлено $countProduct товар(ов/а)</p>";
}

importXml('importToDb.xml');

echo '<hr>';

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
    $productsRoot = $dom->createElement('Товары');
    $dom->appendChild($productsRoot);
    for ($i = 0; $i < count($xmlArr); $i++) {
        $productElem = $dom->createElement("Товар");
        $productsRoot->appendChild($productElem);
        $prodAttrCode = $dom->createAttribute("Код");
        $productElem->appendChild($prodAttrCode);
        $priceValue = $dom->createTextNode($xmlArr[$i]['product']['code']);
        $prodAttrCode->appendChild($priceValue);
        $prodAttrCode = $dom->createAttribute("Название");
        $productElem->appendChild($prodAttrCode);
        $priceValue = $dom->createTextNode($xmlArr[$i]['product']['name']);
        $prodAttrCode->appendChild($priceValue);

        for ($k = 0; $k < count($xmlArr[$i]['price']); $k++) {
            $productPrice = $dom->createElement("Цена");
            $productElem->appendChild($productPrice);
            $productPriceText = $dom->createTextNode($xmlArr[$i]['price'][$k]['price']);
            $productPrice->appendChild($productPriceText);
            $prodAttrPrice = $dom->createAttribute("Тип");
            $productPrice->appendChild($prodAttrPrice);
            $priceValue = $dom->createTextNode($xmlArr[$i]['price'][$k]['price_type']);
            $prodAttrPrice->appendChild($priceValue);
        }

        $productProrertys = $dom->createElement("Свойства");
        $productElem->appendChild($productProrertys);

        for ($k = 0; $k < count($xmlArr[$i]['property']); $k++) {
            $productProrerty = $dom->createElement($xmlArr[$i]['property'][$k]['property']);
            $productProrertys->appendChild($productProrerty);
            $productProrertyText = $dom->createTextNode($xmlArr[$i]['property'][$k]['value']);
            $productProrerty->appendChild($productProrertyText);

            if ($xmlArr[$i]['property'][$k]['atribut_property']) {
                $productProrertyAttr = $dom->createAttribute($xmlArr[$i]['property'][$k]['atribut_property']);
                $productProrerty->appendChild($productProrertyAttr);
                $productProrertyAttrVal = $dom->createTextNode($xmlArr[$i]['property'][$k]['atribut_value']);
                $productProrertyAttr->appendChild($productProrertyAttrVal);
            }
        }

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

    if (!$dom->save($a)) {
        echo "<p style=\"color:red\">Не удалось сохранить XML в файл \"$a\"</p>";
    } else {
        echo "<p>Данные сохранены в файл \"$a\"</p>";
    };
}

exportXml('exportFromDb.xml', '210');



