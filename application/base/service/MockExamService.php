<?php
/**
 * Created by PhpStorm.
 * User: Harvey
 * E-mail:lenziye@qq.com
 * Date: 2021/6/6
 * Time: 3:39 PM
 */

namespace app\base\service;

use app\api\model\v1\TestForm;
use app\api\model\v1\TestQuestions;
use app\common\model\v1\Course;
use app\common\model\v1\TestAnswe;

/**
 * 模拟考试 服务类
 * Class MockExamService
 * @package app\base\service
 */
class MockExamService
{

    /**
     * 生成模拟试卷
     * @param $courseId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createMockExamPaper($courseId, $customerId)
    {
        $model = new  TestQuestions;
        $courseModel = new Course();
        $formModel = new TestForm();
        $course = $courseModel->find($courseId);
        if (empty($course['moni_config'])) {
            return [
                'status' => 'fail',
                'msg' => '无模拟试卷可用'
            ];
        }
        /*   {"test_duration":"100",
        "per_single_score":"01", "per_single_num":"01",
        "per_multi_score":"0", "per_multi_num":"10",
        "per_judge_score":"0","per_judge_num":"0"}*/
        $mockExamConfig = json_decode($course['moni_config'], true);
        $perSingleNum = intval($mockExamConfig['per_single_num']);
        $perMultiNum = intval($mockExamConfig['per_multi_num']);
        $perJudgeNum = intval($mockExamConfig['per_judge_num']);
        // 准备备选题库
        if ($perSingleNum) {
            $singleListAll = $model->where([
                'course_id' => $courseId,
                'problem_type' => TestQuestions::PROBLEM_TYPE_SINGLE,
                'del' => 0
            ])->select();
            if (count($singleListAll) < $perSingleNum) {
                return [
                    'status' => 'fail',
                    'msg' => '单选题题库不足'
                ];
            }
        }

        if ($perMultiNum) {
            $mutilListAll = $model->where([
                'course_id' => $courseId,
                'problem_type' => TestQuestions::PROBLEM_TYPE_MULTI,
                'del' => 0
            ])->select();
            if (count($mutilListAll) < $perMultiNum) {
                return [
                    'status' => 'fail',
                    'msg' => '多选题题库不足'
                ];
            }
        }
        if ($perJudgeNum) {
            $judgeListAll = $model->where([
                'course_id' => $courseId,
                'problem_type' => TestQuestions::PROBLEM_TYPE_JUDGE,
                'del' => 0
            ])->select();
            if (count($judgeListAll) < $perJudgeNum) {
                return [
                    'status' => 'fail',
                    'msg' => '判断题题库不足'
                ];
            }
        }

        // 随机获取指定数量的题库
        if ($perSingleNum) {
            if ($perSingleNum > 1) {
                $singleIds = array_rand($singleListAll, $perSingleNum);
                foreach ($singleIds as $index => $idx) {
                    $items[] = $singleListAll[$idx];
                }
            } else {
                $items[] = $singleListAll[0];
            }
        }

        if ($perMultiNum) {
            if ($perMultiNum == 1) {
                $items[] = $mutilListAll[0];
            } else {
                $mutilIds = array_rand($mutilListAll, $perMultiNum);
                foreach ($mutilIds as $index => $idx) {
                    $items[] = $mutilListAll[$idx];
                }
            }
        }
        if ($perJudgeNum) {
            if ($perJudgeNum == 1) {
                $items[] = $judgeListAll[0];
            } else {
                $judgeIds = array_rand($judgeListAll, $perJudgeNum);
                foreach ($judgeIds as $index => $idx) {
                    $items[] = $judgeListAll[$idx];
                }
            }
        }
        $lastProblemId = $items[0]['id'];
        $singleList = [];
        $multipleList = [];
        $judgmentList = [];
        $shortAnswerList = [];
        $questionIds = [];
        foreach ($items as $index => &$item) {
            $questionIds[] = $item['id'];
            $options = [];
            foreach ($item['options'] as $index2 => $option) {
                $tempOption = [
                    'answerKey' => $index2,
                    'text' => $option,
                    'color' => false,
                    'err' => false,
                ];
                $options[] = $tempOption;
            }
            $item['options'] = $options;
            $item['isCollect'] = false;
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
        $list = array_merge($singleList, $multipleList, $judgmentList, $shortAnswerList);
        $count = count($list);

        //插入记录
        $formId = $formModel->insertGetId([
            'type' => TestForm::TYPE_MONI_TEST,
            'userid' => $customerId,
            'course_id' => $courseId,
            'start_time' => time(),
            'create_time' => time(),
            'mock_exam_problems_id' => implode(',', $questionIds)
        ]);
        $status = 'success';
        return compact('status', 'formId', 'list', 'count', 'lastProblemId',
            'singleList', 'multipleList', 'judgmentList', 'shortAnswerList', 'mockExamConfig');
    }

    /**
     * 考试成绩
     */
    public function testScore($formId)
    {
        $model = new TestForm();
        $courseModel = new Course();
        $answerModel = new TestAnswe();
        $questionModel = new TestQuestions;
        $form = $model->find($formId);
        $courseId = $form['course_id'];
        $course = $courseModel->find($courseId);
        if (empty($course['moni_config'])) {
            return '无模拟试卷可用';
        }
        $rightNum = 0;
        $errorNum = 0;
        $notDoneNum = 0;
        $vaule = 0;
        $mockExamConfig = json_decode($course['moni_config'], true);
        $perSingleScore = intval($mockExamConfig['per_single_score']);
        $perMultiScore = intval($mockExamConfig['per_multi_score']);
        $perJudgeScore = intval($mockExamConfig['per_judge_score']);
        $items = $questionModel->where('id', 'in', explode(',', $form['mock_exam_problems_id']))->select();
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
                    'answerKey' => $index2,
                    'text' => $option,
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
        return compact('items', 'rightNum', 'errorNum', 'notDoneNum', 'vaule', 'singleList', 'multipleList', 'judgmentList');
    }
}