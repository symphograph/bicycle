<?php

namespace Symphograph\Bicycle;

use ReflectionProperty;
use Symphograph\Bicycle\Errors\AppErr;

class JsonDecoder
{
    public static function cloneFromAny(array|object $Inductor, string $className): object
    {
        $Recipient = new $className;
        $classVars = (object) get_class_vars($className);

        foreach ($Inductor as $propName => $propValue) {


            if (!property_exists($classVars, $propName)){
                continue;
            }


            if (is_object($propValue) || is_array($propValue)) {

                $typeInClass = self::getTypeInClass($className, $propName);
                if ($typeInClass === 'array' || $typeInClass === 'object') {
                    $Recipient->$propName = $propValue;
                    continue;
                }


                $Recipient->$propName = self::cloneFromAny($propValue, $typeInClass);
                continue;
            }

            $Recipient->$propName = $propValue;
        }
        return $Recipient;
    }

    private static function getTypeInClass(string $className, string $propName): string
    {
        $Reflection =  new ReflectionProperty($className, $propName)->getType();

        if($Reflection::class === 'ReflectionNamedType'){
            return $Reflection->getName();
        }

        if ($Reflection::class === 'ReflectionUnionType') {
            $types = $Reflection->getTypes();

            $type = self::chooseBetweenTypes($types);
            if(!$type){
                throw new AppErr('JsonDecoder: Type of ' . $propName . ' is invalid for '. $className);
            }

            return $type;
        }

        return 'mixed';
    }

    private static function chooseBetweenTypes($types): string|false
    {
        $arr = [];
        foreach ($types as $type){
            if (Helpers::isMyClassExist($type->getName())){
                $countVars = count(get_class_vars($type->getName()));
                $arr[$type->getName()] = $countVars;
                continue;
            }
            if(!in_array($type->getName(),['array', 'object'])){
                continue;
            }
            $arr[$type->getName()] = 0;
        }
        if(empty($arr)){
            return false;
        }
        asort($arr);
        return array_key_last($arr);
    }
}