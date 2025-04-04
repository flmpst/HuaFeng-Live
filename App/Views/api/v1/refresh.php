<?php

use ChatRoom\Core\Helpers\User;
use ChatRoom\Core\Modules\TokenManager;

$tokenManager = new TokenManager;
$userHelpers = new User;

if (!$userHelpers->checkUserLoginStatus()) {
    $helpers->jsonResponse(401, false, ['message' => 'You are not logged in']);
}
if ($_GET['method'] === 'refresh') {
    $helpers->jsonResponse(200, true, [$tokenManager->generateToken($userHelpers->getUserInfoByEnv()['user_id'], '+1 year', null, 'api', [
        'CreateUA' => $_SERVER['HTTP_USER_AGENT'],
        'CreateIP' => $userHelpers->getIp()
    ])]);
} else {
    $helpers->jsonResponse(200, true, [$tokenManager->delet($userHelpers->getUserInfoByEnv()['user_id'], '+1 year', null, 'api')]);
}
