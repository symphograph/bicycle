<?php

namespace Symphograph\Bicycle\SQL;

enum SQLOperators:string
{
    case LESS = '<';
    case GREATER = '>';
    case EQUAL = '=';
    case NOT_EQUAL = '!=';
    case GREATER_OR_EQUAL = '>=';
    case LESS_OR_EQUAL = '<=';
    case IS_NULL = 'IS NULL';
    case IS_NOT_NULL = 'IS NOT NULL';
    case IN = 'IN';
    case NOT_IN = 'NOT IN';

}
