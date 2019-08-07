<?php

//echo '<pre>';

echo '<p>convertString($a, $b). Результат ее выполнение: если в строке $a содержится 2 и более подстроки $b, 
то во втором месте заменить подстроку $b на инвертированную подстроку.</p><br>';

$str = 'груша яблоко груша яблоко груша яблоко';
$subStr = 'яблоко';

//$str='pear apple pear apple pear apple';
//$subStr='apple';

function strRever($str) {
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
            if (!array_key_exists($GLOBALS['b'], $value)) {
                throw new Exception('неправильный индекс массива: ' . $key);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return;
        }
    }
    usort($a, function($a, $b) {
        return $a[$GLOBALS['b']] <=> $b[$GLOBALS['b']];
    });
    return $a;
}

echo "<h4>Массив до</h4>";
var_export($a);
echo "<h4>Массив после</h4>";
var_export(mySortForKey($a, $b));

echo "<hr>";

echo '<p>Реализовать функцию importXml($a). $a – путь к xml файлу (структура файла приведена ниже). 
Результат ее выполнения: прочитать файл $a и импортировать его в созданную БД.</p><br>';

function db() {
    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    try {
        return new PDO("mysql:host=localhost;dbname=test_samson; charset=UTF8", 'test_samson', '123456', $opt);
    } catch (PDOException $e) {
        die('Ошибка подключения к БД: ' . $e->getMessage());
    }
}

$pdo = db();

function preExec(string $sql, array $arr = []) {
    $stmt = $GLOBALS['pdo']->prepare($sql);
    $stmt->execute($arr);
    return $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function importXml($a) {
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
        $productExist = preExec($sql, (array) $product['Код']);
        if ($productExist) {
            echo "<p style=\"color:red\">Товар \"{$productExist[0]['name']}\" с кодом \"{$productExist[0]['code']}\" уже существует и не был добавлен<p>";
        } else {
            $GLOBALS['pdo']->beginTransaction();
            try {
                //добавляем товар
                $producrArr = [NULL, $product['Код'], $product['Название']];
                $sql = "INSERT INTO `a_product` (`id`, `code`, `name`) VALUES (?, ?, ?)";
                preExec($sql, $producrArr);
                $idProduct = $GLOBALS['pdo']->lastInsertId();
                // добавляем цены
                foreach ($product->Цена as $prices) {
                    $priceArr = [$idProduct, $prices[0]['Тип'], $prices];
                    $sql = "INSERT INTO `a_price`(`id_product`, `price_type`, `price`) VALUES (?, ?, ?)";
                    preExec($sql, $priceArr);
                }
                //добавляем свойства
                foreach ($product->Свойства as $propertys) {
                    foreach ($propertys as $property => $propertyValue) {
                        $propertyAttr = current((array) $propertyValue->attributes());
                        if (is_array($propertyAttr)) {
                            $key = key($propertyAttr);
                            $value = current($propertyAttr);
                        } else {
                            $key = '';
                            $value = '';
                        }
                        $propertyArr = [$idProduct, $property, (string) $propertyValue, $key, $value];
                        $sql = "INSERT INTO `a_property`(`id_product`, `property`, `value`, `atribut_property`, `atribut_value`) VALUES (?, ?, ?, ?, ?)";
                        preExec($sql, $propertyArr);
                    }
                }
                //добавляем категории, лучше бы они существовали заранее... но, для данной ситуации - добавляем
                $idParentCategory = '';
                foreach ($product->Разделы->Раздел as $category) {
                    // проверяем, существует ли категория 
                    $sql = "SELECT * FROM `a_category` WHERE `name`='" . (string) $category . "'";
                    $categoryTb = preExec($sql);
                    if ($categoryTb) {
                        // берем id для связи и запоминаем ее как родителя
                        $idCategory = ($categoryTb[0]['id']);
                        $idParentCategory = $idCategory;
                    } else {
                        $sql = "INSERT INTO `a_category`(`id`, `id_parent`, `code`, `name`) "
                                . "VALUES (NULL,?,'',?)";
                        $categoryArr = [$idParentCategory, (string) $category];
                        preExec($sql, $categoryArr);
                        $idCategory = $GLOBALS['pdo']->lastInsertId();
                        // сохраняем id родителя для следующего раздела товара
                        $idParentCategory = $idCategory;
                    };
                    //делаем связь
                    $sql = "INSERT INTO `a_product_category`(`id_category`, `id_product`) VALUES (?,?)";
                    preExec($sql, [$idCategory, $idProduct]);
                }
            } catch (Exception $e) {
                echo "Ошибка: " . $e->getMessage();
                $GLOBALS['pdo']->rollBack();
            }

            if ($GLOBALS['pdo']->commit() == TRUE) {
                $countProduct++;
            }
        }
    }
    echo "<p>Добавлено $countProduct товар(ов/а)</p>";
}

importXml('importToDb.xml');

