<?php

namespace Symphograph\Bicycle\Repo\Redis;

enum RedisTTL: int
{
    case _5sec = 5;
    case _10sec = 10;
    case _30sec = 30;
    case _1min = 60;
    case _5min = 300;
    case _15min = 900;
    case _1hour = 3600;
    case _6hours = 21600;
    case _1day = 86400;
    case _1week = 604800;
    case persist = -1;
}