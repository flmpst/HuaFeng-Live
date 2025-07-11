<?php

namespace HuaFengLive\Controller;

use HuaFengLive\Controller\LiveController;
use HuaFengLive\Helpers\UserHelpers;

class HomeController extends BaseController
{

    private $userHelpers;

    public function __construct()
    {
        $this->userHelpers = new UserHelpers;
    }

    /**
     * 首页方法
     *
     * @return void
     */
    public function index(): void
    {
        // 加载首页视图
        $this->loadView('/index', [
            'userHelpers' => $this->userHelpers,
            'appConfig' => $this->getAppConfig(),
        ]);
    }

    /**
     * 直播方法
     * 
     * @return void
     */
    public function live(): void
    {
        $liveId = $this->getCurrentUrlArray()['segments'][0];
        $live = new LiveController;
        $liveData = $live->get($liveId);
        $this->loadView('/live', [
            'liveData' => $liveData,
            'liveId' => $liveId,
            'userHelpers' => $this->userHelpers
        ]);
    }

    /**
     * 搜索方法
     * 
     * @return void
     */
    public function module()
    {
        $this->loadView('/module/search');
    }
}
