<?php

namespace app\api\controller\v1;

use app\common\controller\Api;

/**
 *
 */
class Demo2 extends Api
{

    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['test', 'test1'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];


    protected $customer = null;
    protected $customerId = null;

    public function _initialize()
    {
        parent::_initialize();
//        $this->model = new \app\admin\model\v1\ArticleCourseCollect();
        if ($this->auth->isLogin()) {
            $this->customer = $this->auth->getUser();
            $this->customerId = $this->customer['id'];
        }
    }

}
