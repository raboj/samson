DELETE FROM `a_price`;
DELETE FROM `a_product`;
DELETE FROM `a_product_category`;
DELETE FROM `a_property`;
DELETE FROM `a_category`;

<pre>
    <?php
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    require_once 'Class/Db.php';
    $Pdo = Db::getPdo();

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
            if (isset($product->attributes()->Код) && $product->attributes()->Код != '') {
                $sql = "SELECT * FROM `a_product` WHERE `code`=?";
                $productExist = Db::preExec($sql, [$product->attributes()->Код]);
            } else {
                echo "<p style=\"color:red\">Товар не имеет атрибута \"Код\" и не был добавлен<p>";
                continue;
            }
            if ($productExist == 5) {
                echo "<p style=\"color:red\">Товар \"{$productExist[0]['name']}\" с кодом \"{$productExist[0]['code']}\" уже существует и не был добавлен<p>";
            } else {
                if (Db::getPdo()->beginTransaction()) {
//            Db::getPdo()->beginTransaction();
                    try {
                        //добавляем товар
                        if (isset($product->attributes()->Название) && $product->attributes()->Название != '') {
                            $producrArr = [NULL, $product->attributes()->Код, $product->attributes()->Название];
                            $sql = "INSERT INTO `a_product` (`id`, `code`, `name`) VALUES (?, ?, ?)";
                            Db::preExec($sql, $producrArr);
                            $idProduct = Db::getPdo()->lastInsertId();
                        } else {
                            throw new Exception('<p style=\"color:red\">Товар не имеет атрибута "Название" и не был добавлен<p>');
                        }
                        // добавляем цены
                        if (isset($product->Цена)) {
                            foreach ($product->Цена as $prices) {
                                if (isset($prices->attributes()->Тип) && $prices->attributes()->Тип != '' && isset($prices) && $prices != '') {
                                    $priceArr = [$idProduct, $prices->attributes()->Тип, $prices];
                                    $sql = "INSERT INTO `a_price`(`id_product`, `price_type`, `price`) VALUES (?, ?, ?)";
                                    Db::preExec($sql, $priceArr);
                                } else {
                                    throw new Exception("<p style=\"color:red\">Товар не имеет атрибута или значения \"Цена\" и не был добавлен<p>");
                                }
                            }
                        } else {
                            throw new Exception("<p style=\"color:red\">Товар не имеет \"Цены\" и не был добавлен<p>");
                        }
                        //добавляем свойства
                        if (isset($product->Свойства) && key($product->Свойства) !== 0) {
                            foreach ($product->Свойства as $propertys) {
                                foreach ($propertys as $property => $propertyValue) {
//                                var_dump($property);
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
                        } else {
                            throw new Exception("<p style=\"color:red\">Товар не имеет \"Свойства\" и не был добавлен<p>");
                        }
                        //добавляем категории, лучше бы они существовали заранее... но, для данной ситуации - добавляем
                        $idParentCategory = '';
                        if (isset($product->Разделы->Раздел)) {
                            var_dump($product->Разделы->Раздел);
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
                        } else {
                            throw new Exception("<p style=\"color:red\">Товар не имеет \"Разделы\" и не был добавлен<p>");
                        }
                        if (Db::getPdo()->commit() == TRUE) {
                            $countProduct++;
                        }
                    } catch (Exception $e) {
                        echo "Ошибка: " . $e->getMessage();
                        if (Db::getPdo()->inTransaction()) {
                            Db::getPdo()->rollBack();
                        }
                    }
                } //end if beginTransaction
            }
        }
        echo "<p>Добавлено $countProduct товар(ов/а)</p>";
    }

    importXml('importToDb.xml');
    ?>
</pre>