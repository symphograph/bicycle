<?php

namespace Symphograph\Bicycle\DTO;

/**
 * @template T of BindITF
 */
interface BindITF
{
    public int $id {
        get;
        set;
    }

    /**
     * @param array $data
     * @return T
     */
    static function byArr(array $data): object;

    function toJson(int $options = 0): string;

    function toArray(): array;

    /**
     * @param string $json
     * @return T
     */
    static function byJson(string $json): object;


}