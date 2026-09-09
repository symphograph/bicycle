<?php

namespace Symphograph\Bicycle\Auth\User;

use Symphograph\Bicycle\Auth\User\NickGenITF;
use Symphograph\Bicycle\Logs\ErrorLog;

abstract class NickGenerator implements NickGenITF
{
    /** @var int */
    protected const int prefixesCnt = 2000;
    /** @var int */
    protected const int maxAttempts = 1000;

    private array $prefixes;
    protected array $fNames;
    protected bool  $initialized = false;

    /** @var array<string, static> */
    private static array $instances = [];

    protected function __construct()
    {
    }

    final public static function inst(): static
    {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }
        return self::$instances[$class];
    }

    /** Запрет клонирования */
    private function __clone() {}

    /** Запрет десериализации */
    final public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    public function newNick(): string
    {
        $max = static::maxAttempts;
        $attempts = 0;

        do {
            $attempts++;

            if ($attempts <= $max) {
                // Первые {max} попыток - обычные ники
                $nick = $this->gen();
            } else {
                // После {max} попыток - ники с суффиксом
                $nick = $this->genWithSuffix();
            }

            try {
                if (!$this->isExists($nick)) {
                    return $nick;
                }
            } catch (\Exception $e) {
                // При ошибке БД генерируем гарантированно уникальный ник
                ErrorLog::writeToLog($e);
                return $this->generateGuaranteedUniqueNick();
            }

        } while ($attempts < $max * 2); // Всего до 2000 попыток

        // Если все попытки исчерпаны
        return $this->generateGuaranteedUniqueNick();
    }

    private function generateGuaranteedUniqueNick(): string
    {
        // Ник с timestamp гарантирует уникальность даже без проверки в БД
        $str = $this->gen();
        return "$str " . time() . mt_rand(1000, 9999);
    }

    /** @return string[] */
    protected function prefixes(): array
    {
        if(isset($this->prefixes)) return $this->prefixes;

        $consonants = ['b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
                       'n', 'p', 'r', 's', 't', 'v', 'w', 'z'];
        $vowels = ['a', 'e', 'i', 'o', 'u', 'y'];

        $prefixes = [];

        while (count($prefixes) < static::prefixesCnt) {
            $prefix = ucfirst(
                $consonants[array_rand($consonants)] .
                $vowels[array_rand($vowels)] .
                $consonants[array_rand($consonants)] .
                $vowels[array_rand($vowels)]
            );
            $prefixes[$prefix] = true; // Уникальность
        }

        return $this->prefixes = array_keys($prefixes);
    }

    abstract protected function gen(): string;

    abstract protected function genWithSuffix(): string;

    abstract protected function isExists(string $nick): bool;

    /** @return string[] */
    abstract protected function fNames(): array;
}