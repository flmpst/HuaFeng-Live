<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\TokenManager;

$tokenManager = new TokenManager;
$userHelpers = new User;

$helpers->jsonResponse(200, true, [$tokenManager->generateToken($userHelpers->getUserInfoByEnv()['user_id'])]);