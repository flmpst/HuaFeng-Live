<?php

/***
 *       _  __   ____     ____     ______
 *      | |/ /  / __ \   / __ \   / ____/
 *      |   /  / / / /  / /_/ /  / /_    
 *     /   |  / /_/ /  / ____/  / __/    
 *    /_/|_|  \___\_\ /_/      /_/       
 * ------------------- 轻量级PHP快速启动框架
 * 快速启动一个PHP项目，提供基础的MVC架构和常用功能。
 * @package   XQPF
 * @author    小枫_QWQ
 * @link http://url.com
 * @copyright 2025 小枫_QWQ
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.global.php';

use XQPF\Core\Main;

$App = new Main;
$App->run();
