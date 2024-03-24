<?php

namespace Symphograph\Bicycle\Helpers;

class TextHelper
{
    public static function transliterate($string): string
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g',
            'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ь' => '',
            'ы' => 'y', 'ъ' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya', 'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E',
            'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y',
            'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S',
            'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H',
            'Ц' => 'C', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '', 'Ы' => 'Y', 'Ъ' => '', 'Э' => 'E',
            'Ю' => 'Yu', 'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }

    /**
     * Определение правильной формы слова на основе числа.
     *
     * @param int $number Число, для которого нужно определить форму слова.
     * @param array $wordForms [яблоко, яблока, яблок]
     * @return string Правильная форма слова в зависимости от числа.
     */
    public static function numDeclension(int $number, array $wordForms = ['год', 'года', 'лет']): string
    {
        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        // Правила склонения в русском языке для различных чисел.
        $cases = array(2, 0, 1, 1, 1, 2);

        // Определение формы слова на основе числа и контекста.
        $formIndex = ($lastTwoDigits > 4 && $lastTwoDigits < 20)
            ? 2
            : $cases[min($lastDigit, 5)];

        // Возвращение правильной формы слова из массива.
        return $wordForms[$formIndex];
    }
}