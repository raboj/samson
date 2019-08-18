<?php

namespace Test3;

// в классах свойства поднял вверх, тяжело их искать пежду методами
// убрал из 
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
        $this->name = $name;
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
        return ['value'];
    }

    /**
     * @return string
     */
    public function getSave(): string {
        // $value to $this->value
        $value = serialize($this->value);
        return $this->name . ':' . sizeof($value) . ':' . $value;
    }

    /**
     * @return newBase
     */
    // убрал : newBase, т.к. в доченем нужно возвращать newView
    static public function load(string $value) {
        $arValue = explode(':', $value);
        return (new newBase($arValue[0]))
                        ->setValue(unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1), $arValue[1]));
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
        // gettype() переименовал  в getTypeVal() и сделал методом
        $this->type = self::getTypeVal($this->value);
    }

    private function setSize() {
        if (is_subclass_of($this->value, "Test3\newView")) {
            $this->size = parent::getSize() + 1 + strlen($this->property);
        } elseif ($this->type == 'test') {
            $this->size = parent::getSize();
        } else {
            $this->size = parent::getSize();
        }
    }

    /**
     * @return string
     */
    public function __sleep() {
        return ['property'];
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
        if ($this->type == 'test') {
            $this->value = $this->value->getSave();
        }
        return parent::getSave() . serialize($this->property);
    }

    /**
     * @return newView
     */
    static public function load(string $value): newView {
        // решил переписать метод по другому
        $arValue = explode(':', $value);
        $loadObj = new newView($arValue[0]);
        $objValStr = unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1), [$arValue[1]]);

        $arValue = explode(':', $objValStr);
        $objVal = new newBase($arValue[0]);

        $objVal->setValue(unserialize(substr($objValStr, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1), [$arValue[1]]));
        $loadObj->setValue($objVal);

        $arValue = explode(';', $value);
        $loadObj->setProperty(unserialize($arValue[2] . ';'));

        return $loadObj;
    }

    // gettype() to getTypeVal(), gettype - функция PHP, в этом namespace работает
    // но, лучше переименовать и сделать методом newViwe
    static public function getTypeVal($value): string {
        if (is_object($value)) {
            $type = get_class($value);
            do {
                // пол логике понял, что должно быть так !== to ===
                if (strpos($type, "Test3\newBase") === false) {
                    return 'test';
                }
            } while ($type = get_parent_class($type));
        }
        // пол логике понял, что должно быть так gettype() to \gettype()
        return \gettype($value);
    }

}

$obj = new newBase('12345');
$obj->setValue('text');

// что-то со значением O9876 - первый символ не ноль 0, а О
$obj2 = new \Test3\newView('09876');
$obj2->setValue($obj);
$obj2->setProperty('field');
$obj2->getInfo();

$save = $obj2->getSave();

$obj3 = newView::load($save);
//необходимо восстановить $obj2, т.к. при сохранении был сериализовано его value, 
//которое  являлось объектом, не совсем понято для чего 
$obj2 = newView::load($save);

var_dump($obj2->getSave() == $obj3->getSave());

