<?php

namespace symphograph\bicycle;

use ReflectionProperty;

class JsonDecoder
{
    public static function cloneFromAny(array|object $Inductor, string $className): object
    {
        //test i am updated
        $Recipient = new $className;
        $classVars = (object)get_class_vars($className);

        foreach ($Inductor as $k => $v) {

            //Убираем поля, которых класс не ожидает
            if (!property_exists($classVars, $k))
                continue;
            if (is_object($v) || is_array($v)) {

                //Если предлагаемое значение итерабельное, то решаем что с ним делать
                $Reflection =  (new ReflectionProperty($className, $k))->getType();
                if($Reflection::class === 'ReflectionNamedType'){
                    $typeInClass = $Reflection->getName();
                }elseif ($Reflection::class === 'ReflectionUnionType') {
                    $types = $Reflection->getTypes();
                    $arr = [];
                    foreach ($types as $type){
                        $countVars = count(get_class_vars($type->getName()));
                        $arr[$countVars] = $type->getName();
                    }
                    ksort($arr);
                    $typeInClass = array_pop($arr);
                }

                //Массив просто инициализируем
                if ($typeInClass === 'array') {
                    $Recipient->$k = $v;
                    continue;
                }

                //Если это объект ожидаемого класса, инициализируем рекурсивно
                $Recipient->$k = self::cloneFromAny($v, new $typeInClass());
                continue;
            }

            //В простом случае инициализируем.
            // Если тип не приводится, выполнение прекратится с Fatal Error.
            $Recipient->$k = $v;
        }
        return $Recipient;
    }
}