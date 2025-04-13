<?php

namespace Symphograph\Bicycle\HTTP;

use JetBrains\PhpStorm\ExpectedValues;
use Throwable;

class Cookie
{
    public function __construct(
        public int     $expires = 0,
        public string  $path = '/',
        #[ExpectedValues(values: ['None', 'Lax', 'Strict'])]
        public string  $samesite = 'Strict', // None || Lax  || Strict
        public bool    $secure = true,
        public bool    $httponly = true,
        public ?string $domain = null,
        public bool $partitioned = false
    )
    {
        if($expires){
            $this->expires = time() + $expires;
        }
    }

    public static function opts(
        int     $expires = 0,
        string  $path = '/',
        #[ExpectedValues(values: ['None', 'Lax', 'Strict'])]
        string  $samesite = 'Strict',
        bool    $secure = true,
        bool    $httponly = true,
        ?string $domain = null,
        bool $partitioned = false
    ): self
    {
       return new self($expires, $path, $samesite, $secure, $httponly, $domain, $partitioned);

    }

    public static function set(string $name, string $value, ?self $opts = null): void
    {
        $opts = (array) ($opts ?? new self());
        unset($opts['partitioned']);
        try {
            setcookie($name,$value, $opts);
        } catch (Throwable $err) {
            //printr($opts);
            throw $err;
        }
    }

}