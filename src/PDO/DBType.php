<?php

namespace Symphograph\Bicycle\PDO;

enum DBType: string
{
    case mysql = 'mysql';
    case pgsql = 'pgsql';
    case sqlite = 'sqlite';
}
