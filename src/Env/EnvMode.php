<?php

namespace Symphograph\Bicycle\Env;

enum EnvMode: string
{
    case prod = 'prod';
    case dev = 'dev';
    case test = 'test';
}
