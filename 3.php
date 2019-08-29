<?php

namespace Test3;

// в классах свойства поднял вверх, тяжело их искать пежду методами
class newBase {

    static private $count = 0;
    static private $arSetName = [];
    // $name privat to protected
    protected $name;
    protected $value;

    /**
     * @param string $name
     */
    function __construct(int $name = 0) {
        if (empty($name)) {
            while (array_search(self::$count, self::$arSetName) != false) {
                ++self::$count;
            }
            $name = self::$count;
        }
        // должно возвращать строку
        $this->name = strval($name);
        self::$arSetName[] = $this->name;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return '*' . $this->name . '*';
    }

    /**
     * @param mixed $value
     */
    public function setValue($value) {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getSize() {
        $size = strlen(serialize($this->value));
        return strlen($size) + $size;
    }

    public function __sleep() {
        // добавил свойство 'name'
        return ['name', 'value'];
    }

    /**
     * @return string
     */
    public function getSave($TempObj = null): string {
        // если нужно сохранить объект не типа 'test'
        if ($TempObj === null) {
            $TempObj = $this;
        }
        $value = serialize($TempObj);
        //sizeof to strlen
        return $TempObj->name . ':' . strlen($value) . ':' . $value;
    }

    /**
     * @return newBase
     */
    // убрал : newBase, т.к. в доченем нужно возвращать newView
    // не совсем понятно, нужен ли второй параметр unserialize, 
    // оставил, но лучше бы удалить
    static public function load(string $value) {
        $arValue = explode(':', $value);
        return unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1), [$arValue[4]]);
    }

}

class newView extends newBase {

    private $type = null;
    private $size = 0;
    private $property = null;

    /**
     * @param mixed $value
     */
    public function setValue($value) {
        parent::setValue($value);
        $this->setType();
        $this->setSize();
    }

    public function setProperty($value) {
        $this->property = $value;
        return $this;
    }

    private function setType() {
        $this->type = gettype($this->value);
    }

    private function setSize() {
        if (is_subclass_of($this->value, "Test3\newView")) {
            $this->size = parent::getSize() + 1 + strlen($this->property);
        } elseif ($this->type == 'test') {
            $this->size = parent::getSize();
        } else {
            $this->size = strlen($this->value);
        }
    }

    /**
     * @return string
     */
    public function __sleep() {
        // добавил 'type', 'size', 'name', 'value',
        return ['type', 'size', 'property', 'name', 'value',];
    }

    /**
     * @return string
     */
    public function getName(): string {
        if (empty($this->name)) {
            //Exception не в пространстве имен Test3
            throw new \Exception('The object doesn\'t have name');
        }
        return '"' . $this->name . '": ';
    }

    /**
     * @return string
     */
    public function getType(): string {
        return ' type ' . $this->type . ';';
    }

    /**
     * @return string
     */
    public function getSize(): string {
        return ' size ' . $this->size . ';';
    }

    public function getInfo() {
        try {
            echo $this->getName()
            . $this->getType()
            . $this->getSize()
            . "\r\n";
            // Exception to \Exception
        } catch (\Exception $exc) {
            echo 'Error: ' . $exc->getMessage();
        }
    }

    /**
     * @return string
     */
    public function getSave(): string {
        // решил добавить промежуточный объект $TempObj, чтобы не изменять оригинал
        if ($this->type == 'test') {
            $TempObj = clone $this;
            $TempObj->value = $TempObj->value->getSave($TempObj->value);
            return parent::getSave($TempObj) . serialize($this->property);
            ;
        } else {
            return parent::getSave() . serialize($this->property);
        }
    }

    /**
     * @return newView
     */
    static public function load(string $value): newView {
        // все нужное уже есть в родительском классе, решил сделать так
        // unserialize propety нет смысла, оно уже восстановлено 
        $tempObj = parent::load($value);
        // при условии, что только обект тапа 'test' может содержать вложенный объект
        if ($tempObj->type == 'test') {
            $tempObj->value = parent::load($tempObj->value);
        }
        return $tempObj;
    }

}

function gettype($value): string {
    if (is_object($value)) {
        $type = get_class($value);
        do {
            // исправил кавычки
            if (strpos($type, 'Test3\newBase') !== false) {
                return 'test';
            }
        } while ($type = get_parent_class($type));
    }
    return gettype($value);
}

$obj = new newBase('12345');
$obj->setValue('text');

// что-то со значением O9876 - первый символ не ноль 0, а О
$obj2 = new \Test3\newView('09876');
$obj2->setValue($obj);
$obj2->setProperty('field');
$obj2->getInfo();

$save = $obj2->getSave();
var_dump($save);

$obj3 = newView::load($save);

var_dump($obj2->getSave() == $obj3->getSave());

var_dump($obj);
var_dump($obj2);
var_dump($obj3);
