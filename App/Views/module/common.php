<?php

use ChatRoom\Core\Helpers\Helpers;

if (defined('FRAMEWORK_DEBUG') && FRAMEWORK_DEBUG) {
    $helpers = new Helpers;
    $helpers->debugBar();
}
