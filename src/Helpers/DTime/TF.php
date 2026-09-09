<?php

namespace Symphograph\Bicycle\Helpers\DTime;

enum TF: string implements DTFormat
{
    case His = 'H:i:s';
    case is = 'i:s';
}