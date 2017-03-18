<?php

require '../vendor/autoload.php';

$connects = \ONS\Utils\Env::get('CONNECTS');

(new \ONS\Monitor\Console(explode(',', $connects)))->liveView();