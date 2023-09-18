<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/6/26
 * Time: 5:38 PM
 */

namespace app\service;


class SearchService
{
    /**
     * Instance.
     *
     */
    private static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //分词函数
    public function fenci($string)
    {
        // 严格开发模式
        ini_set('display_errors', 'On');
        ini_set('memory_limit', '64M');
        error_reporting(E_ALL);
        header('Content-Type: text/html; charset=utf-8');
//        include ROOT_PATH. 'extend/phpanalysis/phpanalysis.class.php';
        include ROOT_PATH.  'application/base/lib/phpanalysis/phpanalysis.class.php';
        $str = !empty($string) ? $string : '';
        if ($str != '') {
            //岐义处理
            $do_fork = empty($_POST['do_fork']) ? false : true;
            //新词识别
            $do_unit = empty($_POST['do_unit']) ? false : true;
            //多元切分
            $do_multi = empty($_POST['do_multi']) ? false : true;
            //词性标注
            $do_prop = empty($_POST['do_prop']) ? false : true;
            //是否预载全部词条
            $pri_dict = empty($_POST['pri_dict']) ? false : true;

            $tall = microtime(true);

            //初始化类
            \PhpAnalysis::$loadInit = false;
            $pa = new \PhpAnalysis('utf-8', 'utf-8', $pri_dict);   //tp3.2   new \PhpAnalysis

            //载入词典
            $pa->LoadDict();

            //执行分词
            $pa->SetSource($str);
            $pa->differMax = $do_multi;
            $pa->unitWord = $do_unit;

            $pa->StartAnalysis($do_fork);
            $okresult = $pa->GetFinallyResult(',', $do_prop);
            $pa_foundWordStr = $pa->foundWordStr;
            $pa = '';
            return $okresult;
        }
    }

}