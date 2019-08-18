<pre>
    <?php
    echo "<p>mySortForKey(\$a, \$b). \$a – двумерный массив вида [['a'=>2,'b'=>1],['a'=>1,'b'=>3]], 
\$b – ключ вложенного массива. Результат ее выполнения: двумерном массива \$a отсортированный по возрастанию значений для ключа \$b. 
В случае отсутствия ключа \$b в одном из вложенных массивов, выбросить ошибку класса Exception с индексом неправильного массива.<p><br>";

    $a = [
        [
            'a' => 2,
            'b' => 1
        ],
        [
            'a' => 1,
            'b' => 3
        ],
        [
            'a' => 4,
            'b' => 2
        ]
    ];
    $b = 'c';

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
    ?>

</pre>