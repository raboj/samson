<?php
namespace Test3;

//показывать все ошибки
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class newBase
{
    static private $count = 0;
    static private $arSetName = [];
    /**
     * @param string $name
     */
    function __construct(int $name = 0)
    {
        if (empty($name)) {
            while (array_search(self::$count, self::$arSetName) != false) {
                ++self::$count;
            }
            $name = self::$count;
        }
        $this->name = $name;
        self::$arSetName[] = $this->name;
    }
    // решил изменить модификатор доступа privat to protected
    protected $name;
    /**
     * @return string
     */
    public function getName(): string
    {
        return '*' . $this->name  . '*';
    }
    protected $value;
    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
    /**
     * @return string
     */
    public function getSize()
    {
        $size = strlen(serialize($this->value));
        return strlen($size) + $size;
    }
    public function __sleep()
    {
        return ['value'];
    }
    /**
     * @return string
     */
    public function getSave(): string
    {
        // add $this->
        $value = serialize($this->value);
        return $this->name . ':' . sizeof($value) . ':' . $value;
    }
    /**
     * @return newBase
     */
    // убрал : newBase
    static public function load(string $value)
    {
        $arValue = explode(':', $value);
        return (new newBase($arValue[0]))
            ->setValue(unserialize(substr($value, strlen($arValue[0]) + 1
                + strlen($arValue[1]) + 1), $arValue[1]));
    }
}
class newView extends newBase
{
    private $type = null;
    private $size = 0;
    private $property = null;
    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        parent::setValue($value);
        $this->setType();
        $this->setSize();
    }
    public function setProperty($value)
    {
        $this->property = $value;
        return $this;
    }
    private function setType()
    {
        $this->type = gettype($this->value);
    }
    private function setSize()
    {
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
    public function __sleep()
    {
        return ['property'];
    }
    /**
     * @return string
     */
    public function getName(): string
    {
        if (empty($this->name)) {
            //Class 'Test3\Exception' not found, думал что Exception в пространстве имен Test3
            throw new \Exception('The object doesn\'t have name');
        }
        return '"' . $this->name  . '": ';
    }
    /**
     * @return string
     */
    public function getType(): string
    {
        return ' type ' . $this->type  . ';';
    }
    /**
     * @return string
     */
    public function getSize(): string
    {
        return ' size ' . $this->size . ';';
    }
    public function getInfo()
    {
        try {
            echo $this->getName()
                . $this->getType()
                . $this->getSize()
                . "\r\n";
        } catch (Exception $exc) {
            echo 'Error: ' . $exc->getMessage();
        }
    }
    /**
     * @return string
     */
    public function getSave(): string
    {
        if ($this->type == 'test') {
            $this->value = $this->value->getSave();
        }
        return parent::getSave() . serialize($this->property);
    }
    /**
     * @return newView
     */
    static public function load(string $value)
    {
        
        $arValue = explode(':', $value);
       $loadObj= new newView($arValue[0]);
       
       // вложенный объект 
       $objValStr=unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1), [$arValue[1]]);
       $arValue = explode(':', $objValStr);
       $objVal= new newBase($arValue[0]);
       $objVal->setValue(unserialize(substr($objValStr, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1), [$arValue[1]]));
       $loadObj->setValue($objVal);
       
       $arValue = explode(';', $value);
       $loadObj->setProperty(unserialize($arValue[2].';'));
        
        return $loadObj;

    }
}
function gettype($value): string
{
//    почему gettype()? может \gettype? что вообще она должна возвращать и почему сделана отдельно, а не в методе?
    if (is_object($value)) {
        $type = get_class($value);
        do {
            if (strpos($type, "Test3\newBase") === false) {
                return 'test';
            }
        } while ($type = get_parent_class($type));
    }
//    return gettype($value);
    return 'some type';
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
//которое  являлось объектом
$obj2 = newView::load($save);

var_dump($obj2->getSave() == $obj3->getSave());

