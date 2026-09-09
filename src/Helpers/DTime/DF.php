<?php

namespace Symphograph\Bicycle\Helpers\DTime;

enum DF: string implements DTFormat
{
    case Y = 'Y';
    case y = 'y';

    case sql = 'Y-m-d';           // SQL DATE

    case mYDots = 'd.m.Y';
    case mYDash = 'd-m-Y';
    case YmdSlash = 'Y/m/d';
    case mYEuroSlash = 'd/m/Y';
    case YmdDots = 'Y.m.d';
    case Ymd = 'Ymd';
}