<?php
/**
 * Abstract base for transfer
 * User: moyo
 * Date: 10/03/2017
 * Time: 3:21 PM
 */

namespace ONS\Transfer;

use ONS\Contract\Transfer\Queue;
use ONS\Transfer\Components\NetClient;
use ONS\Transfer\Components\PropertySet;

abstract class Base extends PropertySet implements Queue
{
    use NetClient;
}