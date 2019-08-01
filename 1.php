<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo 'Реализовать функцию findSimple ($a, $b). $a и $b – целые положительные числа. 
Результат ее выполнение: массив простых чисел от $a до $b.';

function findSimple(int $a, int $b) {
    if ($a <= 0 || $b <= 0) {
        return "не положительное число";
    } else {
        for ($i = $a; $i <= $b; $i++) {
            $cnt = 0;
            for ($j = 1; $j <= $b; $j++) {
                if ($i % $j == 0) {
                    $cnt++;
                }
            }
            if ($cnt == 2) {
                $arr[] = $i;
            }
        }
        return $arr;
    }
}

var_dump(findSimple(7, 20));

echo '<hr>
Реализовать функцию createTrapeze($a). $a – массив положительных чисел, 
количество элементов кратно 3. Результат ее выполнение: двумерный массив 
(массив состоящий из ассоциативных массива с ключами a, b, c). 
Пример для входных массива [1, 2, 3, 4, 5, 6] результат [[‘a’=>1,’b’=>2,’с’=>3],[‘a’=>4,’b’=>5 ,’c’=>6]].';

function createTrapeze(array $a) {
    foreach ($a as $value) {
        if ($value <= 0) {
            return "в массиве должны быть только положительные числа";
        }
    }
    if (count($a) % 3 != 0) {
        return "количество элементов не кратно 3";
    } else {
        for ($i = 0; $i < count($a); $i += 3) {
            $arr[] = ['a' => $a[$i], 'b' => $a[$i + 1], 'c' => $a[$i + 2]];
        }
        return $arr;
    }
}

var_dump(createTrapeze([1, 2, 3, 4, 5, 6, 7, 8, 9]));

echo '<hr>
Реализовать функцию squareTrapeze($a). $a – массив результата выполнения функции createTrapeze(). 
Результат ее выполнение: в исходный массив для каждой тройки чисел добавляется дополнительный ключ s, 
содержащий результат расчета площади трапеции со сторонами a и b, и высотой c.';

function squareTrapeze(array $a) {
    foreach ($a as &$trapeze) {
        $trapeze['s'] = ($trapeze['a'] + $trapeze['b']) / 2 * $trapeze['c'];
    }
    return $a;
}

$a = createTrapeze([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
var_dump(squareTrapeze($a));

echo '<hr>
Реализовать функцию getSizeForLimit($a, $b). $a – массив результата выполнения функции squareTrapeze(), 
$b – максимальная площадь. Результат ее выполнение: массив размеров трапеции с максимальной площадью, но меньше или равной $b.';

function getSizeForLimit(array $a, float $b) {
//    $maxSquare = max(array_column($a, 's'));

    $maxSquareArr = current($a);
    $maxSquare = $maxSquareArr['s'];
    foreach ($a as $trapeze) {
        if ($maxSquare <= $b && $trapeze['s'] <= $b) {
            $maxSquare = $trapeze['s'];
            $maxSquareArr = $trapeze;
        }
    }
    unset($maxSquareArr['s']);
    return $maxSquareArr;
}

$a = squareTrapeze($a);

var_dump(getSizeForLimit($a, 30));

echo '<hr>
Реализовать функцию getMin($a). $a – массив чисел. 
Результат ее выполнения: минимальное числа в массиве (не используя функцию min, ключи массив может быть ассоциативный).';

function getMin(array $a) {
    $min = current($a);
    foreach ($a as $value) {
        if ($min > $value) {
            $min = $value;
        }
    }
    return $min;
}

var_dump(getMin([1, 2, -3, 4, 5, 6, 7, -8, -9]));

echo '<hr>
Реализовать функцию printTrapeze($a). $a – массив результата выполнения функции squareTrapeze(). 
Результат ее выполнение: вывод таблицы с размерами трапеций, строки с нечетной площадью трапеции отметить любым способом.';

function printTrapeze(array $a) {
    echo '
    <table border="1">
        <tr>
            <th>сторона а</th>
            <th>сторона b</th>
            <th>высота c</th>
            <th>площадь s</th>
        </tr>
        ';
    foreach ($a as $trapeze) {
        if (($trapeze['s'] - intval($trapeze['s'])) == 0 && ($trapeze['s'] % 2 == 0)) {
            $style = 'style="background: gray;"';
        } else {
            $style = '';
        }
        ?>
        <tr <?= $style ?>>
            <td><?= $trapeze['a'] ?></td>
            <td><?= $trapeze['b'] ?></td>
            <td><?= $trapeze['c'] ?></td>
            <td><?= $trapeze['s'] ?></td>
        </tr>
        <?php
    }
    echo '</table>';
}

printTrapeze($a);

echo '<hr>
Реализовать абстрактный класс BaseMath содержащий 3 метода: exp1($a, $b, $c) и exp2($a, $b, $c),getValue(). 
Метода exp1 реализует расчет по формуле a*(b^c). Метода exp2 реализует расчет по формуле (a/b)^c. 
Метод getValue() возвращает результат расчета класса наследника.';

abstract class BaseMath {

    protected function exp1($a, $b, $c) {
        return $a * ($b ** $c);
    }

    protected function exp2($a, $b, $c) {
        return ($a / $b) ** $c;
    }

    abstract public function getValue();
}

echo '<hr>
Реализовать класс F1 наследующий методы BaseMath, содержащий конструктор с параметрами ($a, $b, $c) и метод getValue(). 
Класс реализует расчет по формуле f=(a*(b^c)+(((a/c)^b)%3)^min(a,b,c)).<br>
<mark>Думаю, что не совсем понял задание.<b>Нужен Ваш комментарий</b> <br>
опечатка в формуле? f=(a*(b^c)+(((a/c)^b)%3)^min(a,b,c))  > (a/c)^b) ,  в предыдущем задании (a/b)^c, и со скобками беда
</mark>';

class F1 extends BaseMath {

    public function __construct($a, $b, $c) {
        $this->f = $this->exp1($a, $b, $c) + ((($this->exp2($a, $b, $c)) % 3) ** min($a, $b, $c));
    }

    public function getValue() {
        return $this->f;
    }

}

$tempObj = new F1(1, 2, 3);

var_dump($tempObj->getValue());
?>