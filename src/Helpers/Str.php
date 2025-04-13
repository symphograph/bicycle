<?php

namespace Symphograph\Bicycle\Helpers;

class Str
{
    public static function camel(string $string, bool $upFirstChar = false): string
    {

        $str = str_replace(['_', ' '], '-',$string);
        $str = ucwords($str, '-');
        $str = str_replace('-', '', $str);

        return $upFirstChar ? $str : lcfirst($str);
    }

    public static function mb_ucfirst(string $str, string $encoding = 'UTF-8'): string
    {
        $firstChar = mb_substr($str, 0, 1, $encoding);
        $restOfString = mb_substr($str, 1, null, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $restOfString;
    }

    public static function isContainsNumber(string $str): bool
    {
        return !!preg_match('/\d/', $str);
    }

    public static function isContainsRuLetters(string $string): bool {
        return preg_match('/[А-Яа-яЁё]/u', $string) === 1;
    }

    /**
     * @param string $str
     * @return bool - Проверка на наличие акцентированных символов
     */
    public static function isAccent(string $str): bool
    {
        return !!preg_match('/[^\x20-\x7E]/', $str);
    }

    public static function isCamelCase(string $str): bool
    {
        return !!preg_match('/\b([A-Z][a-z]*[A-Z][a-z]*)\b/', $str);
    }

    public static function isTitleCase(string $str): bool
    {
        return !!preg_match('/\b([A-Z][a-z]+(?: [A-Z][a-z]+)*)\b/', $str);
    }

    public static function decodeDoubleHtml(string $str): string
    {
        $regExp = '/&amp;(?=#\d+;)/';
        $str = preg_replace($regExp, '&', $str);

        return html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }


    public static function sanitize(string $str): string
    {
        if(empty($str)) return '';
        $str = trim($str);
        $str = preg_replace('/[^a-zA-ZА-Яа-яёЁ\-\s]/ui','',$str);
        $str = self::decodeDoubleHtml($str);
        //$str = str_replace('&amp;', '&', $str);
        $str = str_replace('&nbsp;', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return $str ?? '';
    }

    public static function onlyLetters(string $str): string
    {
        return preg_replace('/[^a-zA-ZА-Яа-яёЁ\-\s]/ui','',$str);
    }

    public static function onlyLettersAndNums(string $str): string
    {
        return preg_replace('/[^0-9a-zA-ZА-Яа-яёЁ\-\s]/ui','',$str);
    }

    public static function onlyNums(string $str): string
    {
        return preg_replace('/[^0-9\-\s]/ui','',$str);
    }

    public static function unDoubleSpace(string $str): string
    {
        $str = str_replace('&nbsp;', ' ', $str);
        return preg_replace('/\s+/', ' ', $str);
    }

    public static function explode(array|string $separators, string $str, int $limit = PHP_INT_MAX): array
    {
        if(is_array($separators)){
            $sep = reset($separators);
            $str = str_replace($separators, $sep, $str);
        }else{
            $sep = $separators;
        }

        return explode($sep, $str, $limit);
    }

    /**
     * Разбивает строку на массив, учитывая скобки.
     * @return string[]
     */
    public static function explodeWithBrackets(array|string $separators, string $str, int $limit = PHP_INT_MAX): array
    {
        // Шаг 1: Разделить строку, используя несколько разделителей.
        $parts = Str::explode($separators, $str, $limit);

        // Шаг 2: Объединять части, если есть незакрытая скобка.
        $result = [];
        $buffer = '';
        $openBrackets = 0;

        foreach ($parts as $part) {
            // Подсчет открытых и закрытых скобок.
            $openBrackets += substr_count($part, '(');
            $openBrackets -= substr_count($part, ')');

            // Добавляем часть к буферу.
            $buffer .= ($buffer ? ' ' : '') . trim($part);

            // Если все скобки закрыты, добавляем элемент в массив и очищаем буфер.
            if ($openBrackets === 0) {
                $result[] = $buffer;
                $buffer = '';
            }
        }

        // В случае, если остался элемент без закрытия скобок.
        if (!empty($buffer)) {
            $result[] = $buffer;
        }

        return $result;
    }

    public static function toLowerWithoutAbbr($str): string
    {
        $regExp = '/\b([A-ZА-ЯЁ]?[a-zа-яё]+)\b/u';
        return preg_replace_callback(
            $regExp,
            function ($matches) {
                return mb_strtolower($matches[0]);
            },
            $str
        );
    }

    public static function isValidMD5($md5): bool
    {
        return preg_match('/^[a-f0-9]{32}$/', $md5) === 1;
    }

}