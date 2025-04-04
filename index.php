<?php

/***
 *        __  __            ______                 __    _          
 *       / / / /_  ______ _/ ____/__  ____  ____ _/ /   (_)   _____ 
 *      / /_/ / / / / __ `/ /_  / _ \/ __ \/ __ `/ /   / / | / / _ \
 *     / __  / /_/ / /_/ / __/ /  __/ / / / /_/ / /___/ /| |/ /  __/
 *    /_/ /_/\__,_/\__,_/_/    \___/_/ /_/\__, /_____/_/ |___/\___/ 
 *                                       /____/                     
 * --------------------------------------------------- 自托管式直播列表
 * @author 花枫工作室
 * @license Apache
 * @link https://github.com/flmpst/HuaFeng-Live
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.global.php';

use ChatRoom\Core\Main;

$App = new Main;
$App->run();
