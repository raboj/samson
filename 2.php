<?php

// convertString($a, $b). Результат ее выполнение: если в строке $a содержится 2 и более подстроки $b, 
// то во втором месте заменить подстроку $b на инвертированную подстроку.


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

//mySortForKey($a, $b). $a – двумерный массив вида [['a'=>2,'b'=>1],['a'=>1,'b'=>3]], 
//$b – ключ вложенного массива. Результат ее выполнения: двумерном массива $a отсортированный по возрастанию значений для ключа $b. 
//В случае отсутствия ключа $b в одном из вложенных массивов, выбросить ошибку класса Exception с индексом неправильного массива.

$a = [['a' => 2, 'b' => 1], ['a' => 1, 'b' => 3], ['a' => 4, 'b' => 2]];
$b = 'b';
echo "<h4>Массив до</h4>";
var_dump($a);

function mySortForKey(array &$a, string $b) {
    
    foreach ($a as $key=>$value) {
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
echo "<h4>Массив после</h4>";

var_dump(mySortForKey($a, $b));

echo "<hr>";