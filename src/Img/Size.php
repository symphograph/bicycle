<?php

namespace Symphograph\Bicycle\Img;

class Size
{
    public int $width;
    public int $height;

    public function __construct($width, $height){
        $this->width = $width;
        $this->height = $height;
    }
}