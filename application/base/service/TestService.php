<?php

namespace app\base\service;

use app\api\model\v1\TestProblemCollect;
use app\common\model\v1\Course;
use app\common\model\v1\TestAnswe;
use app\common\model\v1\TestForm;
use app\common\model\v1\TestPaper;
use app\api\model\v1\TestQuestions;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use addons\epay\library\Service;

class TestService
{

    /**
     * 提交答案
     * @param $formId
     * @param $problemId
     * @param $answe
     * @param string $rightStatus
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function submitAnswer($formId, $problemId, $answe, $rightStatus = '')
    {
        $model = new TestAnswe();
        $form = \app\api\model\v1\TestForm::get($formId);
        if ($model->where(['form_id' => $formId,
            'problem_id' => $problemId])->find()) {
            return '';
        }
        if (is_string($rightStatus)) {
            $problemModel = new TestQuestions();
            $problem = $problemModel->find($problemId);
            if ($problem['problem_type'] == TestQuestions::PROBLEM_TYPE_SINGLE || $problem['problem_type'] == TestQuestions::PROBLEM_TYPE_JUDGE) {
                if ($problem['answer'] == $answe) {
                    $rightStatus = 1;
                } else {
                    $rightStatus = 0;
                }
            } elseif ($problem['problem_type'] == TestQuestions::PROBLEM_TYPE_MULTI) {
                $right = explode(',', $problem['answer']);
                $answer = explode(',', rtrim($answe, ','));
                sort($answer);
                $answe = implode(',', $answer);
                $check = array_diff($right, $answer);
                $rightStatus = empty($check) ? true : false;
            }
        }
        TestProblemCollect::errorCollect($problemId, $rightStatus, $form['userid']);
        $model->insert([
            'answe' => $answe,
            'form_id' => $formId,
            'problem_id' => $problemId,
            'right_status' => $rightStatus,
            'create_time' => time()
        ]);
        return true;
    }


    /**
     * 完成考试
     * @param $formId
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function completeTest($formId)
    {
        $model = new TestForm();
        $form = $model->find($formId);
        if (!$form) {
            return '数据不存在';
        }
        if ($form->type == TestForm::TYPE_TRUE_PAPER) {
            $testPaperId = $form->chapter_id;
            TestPaper::where(['id' => $testPaperId])->setInc('use_test_num');
        } elseif ($form->type == TestForm::TYPE_MONI_TEST) {

        }

        if ($form->end_time)
            return '不能重复提交';
        $form->end_time = time();
        $form->save();

        return true;
    }

    /**
     * 历年真题 -- 题目列表
     * @param $testPaperId
     * @param $customerId
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function testPaperQuestions($testPaperId, $customerId)
    {
        $model = new  TestQuestions;
        $testPaperModel = new TestPaper();
        $testPaperInfo = $testPaperModel->find($testPaperId);
        if (!$testPaperInfo) return '试卷不存在';
        $courseId = $testPaperInfo['course_id'];
        $singleList = [];
        $multipleList = [];
        $judgmentList = [];
        $shortAnswerList = [];
        $formModel = new TestForm();
        //是否有正在进行的考试
        $doExam = $formModel->where(['type' => TestForm::TYPE_TRUE_PAPER,
            'chapter_id' => $testPaperId,
            'userid' => $customerId,
            'end_time' => 0
        ])->find();
        if (empty($doExam)) {
            $formId = $formModel->insertGetId([
                'type' => TestForm::TYPE_TRUE_PAPER,
                'chapter_id' => $testPaperId,
                'userid' => $customerId,
                'course_id' => $courseId,
                'start_time' => time(),
                'create_time' => time(),
            ]);
        } else {
            $formId = $doExam['id'];
        }

        $map = [
            'tid' => $testPaperId,
            'parent_type' => 2,
            'del' => 0
        ];
        $items = $model->where($map)->order('problem_type asc,weigh desc')
            ->select();
        foreach ($items as $index => &$item) {
            $options = [];
            foreach ($item['options'] as $index2 => $option) {
                $tempOption = [
                    'answerKey' => $option['option'],
                    'text' => $option['text'],
                    'image' => out_net_img($option['image']),
                    'color' => false,
                    'err' => false,
                ];
                $options[] = $tempOption;
            }
            $item['desc_img'] = out_net_img($item['desc_img']);
            $item['options'] = $options;
            $isCollect = Db::name('test_problem_collect')->where([
                'problem_id' => $item['id'],
                'type' => 1,
                'userid' => $customerId
            ])->find();
            $item['isCollect'] = $isCollect ? true : false;
            $problemType = $item['problem_type'];
            if ($problemType == 1) {
                array_push($singleList, $item);
            } elseif ($problemType == 2) {
                array_push($multipleList, $item);
            } elseif ($problemType == 3) {
                array_push($judgmentList, $item);
            } elseif ($problemType == 4) {
                array_push($shortAnswerList, $item);
            }
        }
        $list = $items;
        $count = count($items);
        $duration = $testPaperInfo['test_duration'];

        // 正规考试模式，计算时间
//        if ($testPaperInfo['end_time'] < time()) {
//            $duration = 0;
//        } else {
//            $duration = $testPaperInfo['end_time'] - time();
//        }
        return compact('list', 'count', 'formId', 'duration',
            'singleList', 'multipleList', 'judgmentList', 'shortAnswerList',
            'testPaperInfo');
    }

    /**
     * 章节练习 -- 题目列表
     * @param $courseId
     * @param $chapterId
     * @param $customerId
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function chapterTestQuestions($courseId, $chapterId, $customerId)
    {
        $model = new  TestQuestions;
        $lastProblemId = 3;
        $singleList = [];
        $multipleLis = [];
        $judgmentList = [];
        $shortAnswerList = [];
        $formModel = new TestForm();
//        $courseId = $param['courseId'];
        $formId = $formModel->insertGetId([
            'type' => TestForm::TYPE_CHAPTER_TEST,
            'userid' => $customerId,
            'course_id' => $courseId,
            'chapter_id' => $chapterId,
            'start_time' => time(),
            'create_time' => time(),
        ]);
        $map = [
            'tid' => $chapterId,
            'parent_type' => 1,
            'del' => 0
        ];
        $items = $model->where($map)->order('problem_type asc,weigh desc')
            ->select();
        foreach ($items as $index => &$item) {
            $options = [];
            foreach ($item['options'] as $index2 => $option) {
                $tempOption = [
                    'answerKey' => $option['option'],
                    'text' => $option['text'],
                    'image' => out_net_img($option['image']),
                    'color' => false,
                    'err' => false,
                ];
                $options[] = $tempOption;
            }
            $item['options'] = $options;
            $item['desc_img'] = out_net_img($item['desc_img']);
            $isCollect = Db::name('test_problem_collect')->where([
                'problem_id' => $item['id'],
                'type' => 1,
                'userid' => $customerId
            ])->find();
            $item['isCollect'] = $isCollect ? true : false;
            $problemType = $item['problem_type'];
            if ($problemType == 1) {
                array_push($singleList, $item);
            } elseif ($problemType == 2) {
                array_push($multipleLis, $item);
            } elseif ($problemType == 3) {
                array_push($judgmentList, $item);
            } elseif ($problemType == 4) {
                array_push($shortAnswerList, $item);
            }
        }
        $list = array_merge($singleList, $multipleLis, $judgmentList, $shortAnswerList);
        $count = count($list);
        return compact('list', 'lastProblemId', 'count',
            'singleList', 'multipleLis', 'judgmentList', 'formId', 'shortAnswerList');
    }

    /**
     *  考试成绩  历年真题
     */
    public function testScore($formId)
    {
        $model = new TestForm();
        $courseModel = new Course();
        $answerModel = new TestAnswe();
        $questionModel = new TestQuestions;
        $testPaperModel = new TestPaper();
        $form = $model->find($formId);
        $courseId = $form['course_id'];
        $course = $courseModel->find($courseId);
        $testPaperId = $form['chapter_id'];
        $testPaperInfo = $testPaperModel->find($testPaperId);
        $rightNum = 0;
        $errorNum = 0;
        $notDoneNum = 0;
        $vaule = 0;
        $perSingleScore = intval($testPaperInfo['per_single_score']);
        $perMultiScore = intval($testPaperInfo['per_multi_score']);
        $perJudgeScore = intval($testPaperInfo['per_judge_score']);
        $map = [
            'tid' => $testPaperId,
            'parent_type' => 2,
            'del' => 0
        ];
        $items = $questionModel->where($map)->order('problem_type asc,weigh desc')
            ->select();
        $singleList = [];
        $multipleList = [];
        $judgmentList = [];
        $questionIds = [];
        foreach ($items as $index => &$item) {
            $problemType = $item['problem_type'];
            $answers = $answerModel->where([
                'form_id' => $formId,
                'problem_id' => $item['id']
            ])->find();
            if (!empty($answers)) {
                $item['rightStatus'] = $answers['right_status'];
            } else {
                $item['rightStatus'] = '';
            }
            $item['problemId'] = $item['id'];
            //记分
            if (empty($form['has_score'])) {
                if (empty($answers)) {
                    $notDoneNum++;
                } elseif ($answers['right_status'] == 1) {
                    $rightNum++;
                    if ($problemType == 1) {
                        $vaule += $perSingleScore;
                    } elseif ($problemType == 2) {
                        $vaule += $perMultiScore;
                    } elseif ($problemType == 3) {
                        $vaule += $perJudgeScore;
                    }
                } else {
                    $errorNum++;
                }
            }

            $questionIds[] = $item['id'];
            $options = [];
            foreach ($item['options'] as $index2 => $option) {
                $tempOption = [
                    'answerKey' => $option['option'],
                    'text' => $option['text'],
                    'image' => out_net_img($option['image']),
                    'color' => false,
                    'err' => false,
                ];
                $options[] = $tempOption;
            }
            $item['options'] = $options;
            $item['isCollect'] = false;
            if ($problemType == 1) {
                array_push($singleList, $item);
            } elseif ($problemType == 2) {
                array_push($multipleList, $item);
            } elseif ($problemType == 3) {
                array_push($judgmentList, $item);
            }
        }
        if (!empty($form['has_score'])) {
            $rightNum = $form->right_num;
            $errorNum = $form->error_num;
            $notDoneNum = $form->not_done_num;
            $vaule = $form->score;
        } else {
            $form->right_num = $rightNum;
            $form->error_num = $errorNum;
            $form->not_done_num = $notDoneNum;
            $form->score = $vaule;
            $form->has_score = 1;
            $form->save();
        }
        $totalScore = $vaule;
        return compact('totalScore', 'items', 'rightNum', 'errorNum', 'notDoneNum', 'vaule', 'singleList', 'multipleList', 'judgmentList');
    }
}
