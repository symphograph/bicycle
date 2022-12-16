<?php

namespace Symphograph\Bicycle;

use ReflectionProperty;

class JsonDecoder
{
    public static function cloneFromAny(array|object $Inductor, string $className): object
    {
        $Recipient = new $className;
        $classVars = (object)get_class_vars($className);

        foreach ($Inductor as $propName => $propValue) {

            if (!property_exists($classVars, $propName))
                continue;

            if (is_object($propValue) || is_array($propValue)) {

                $typeInClass = self::getTypeInClass($className, $propName);

                if ($typeInClass === 'array') {
                    $Recipient->$propName = $propValue;
                    continue;
                }

                $Recipient->$propName = self::cloneFromAny($propValue, $propName);
                continue;
            }

            $Recipient->$propName = $propValue;
        }
        return $Recipient;
    }

    private static function getTypeInClass(string $className, string $propName): string
    {
        $Reflection =  (new ReflectionProperty($className, $propName))->getType();

        if($Reflection::class === 'ReflectionNamedType'){
            return $Reflection->getName();
        }

        if ($Reflection::class === 'ReflectionUnionType') {
            $types = $Reflection->getTypes();
            $arr = [];
            foreach ($types as $type){
                $countVars = count(get_class_vars($type->getName()));
                $arr[$countVars] = $type->getName();
            }
            ksort($arr);
            return array_pop($arr);
        }
        return 'mixed';
    }
}