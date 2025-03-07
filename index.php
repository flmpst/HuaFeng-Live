<?php

/***
 *        __  __            ______                 __    _          
 *       / / / /_  ______ _/ ____/__  ____  ____ _/ /   (_)   _____ 
 *      / /_/ / / / / __ `/ /_  / _ \/ __ \/ __ `/ /   / / | / / _ \
 *     / __  / /_/ / /_/ / __/ /  __/ / / / /_/ / /___/ /| |/ /  __/
 *    /_/ /_/\__,_/\__,_/_/    \___/_/ /_/\__, /_____/_/ |___/\___/ 
 *                                       /____/                     
 * ---------------------------------------------- 基于子辰聊天室魔改
 * @author 花枫工作室
 * @license Apache
 * @link https://github.com/flmpst/HuaFeng-Live
 */
require_once __DIR__ . '/vendor/autoload.php';

use ChatRoom\Core\Main;

$App = new Main;
$App->run();
