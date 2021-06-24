<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuizAssignment;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\Registration;
use App\Models\Account;
use App\Models\AuthHistory;

class AssessmentController extends Controller
{
    private $_quiz;
    private $_question;
    private $_option;
    private $_result;
    private $_quizAssignment;
    private $_registration;
    private $_account;
    private $_authHistory;

    public function __construct(AuthHistory $authHistory, Quiz $quiz, Question $question, Option $option, Result $result, QuizAssignment $assignment, Registration $registration, Account $account)
    {
        $this->_quizAssignment = $assignment;
        $this->_quiz = $quiz;
        $this->_question = $question;
        $this->_option = $option;
        $this->_result = $result;
        $this->_registration = $registration;
        $this->_account = $account;
        $this->_authHistory = $authHistory;
    }

    public function quizList(Request $request)
    {
        $auth = $request->header('Authorization');
        if (empty($auth)) {
            $response = [
                'success' => false,
                'description' => 'Authorization header required'
            ];
            return response()->json($response, 401);
        }

        $authorization = explode(':', $auth);
        $token = trim($authorization[1]);
        $user = $this->_authHistory->_getAuthUserId($token);
        if (!$user) {
            if (empty($auth)) {
                $response = [
                    'success' => false,
                    'description' => 'Wrong token'
                ];
                return response()->json($response, 401);
            }
        }
        $userId = $user->id_number;
        $isAuthExpired = $this->_authHistory->_isAuthExpired($userId, $token);
        if ($isAuthExpired) {
            $payload = [
                'is_expired' => 1
            ];
            $this->_authHistory->_expireAuth($userId, $token, $payload);
            $response = [
                'success' => false,
                'description' => 'Token is expired'
            ];
            return response()->json($response, 401);
        }

        $indexNumber = trim($request->get('index_number'));
        $status = trim($request->get('status'));

        $quiz = $this->_quiz->_getQuizList();

        if ($quiz->isEmpty()) {
            $response = [
                'success' => true,
                'description' => [
                    'quiz' => []
                ]
            ];
            return response()->json($response, 401);
        }

        $quizList = [];
        switch ($status) {
            case '0':
                foreach ($quiz as $q) {
                    if ($this->_quizAssignment->_checkQuizAvailability($q->quid, $indexNumber)) {
                        $quizList[] = [
                            'quiz_id' => $q->quid,
                            'quiz_name' => $q->quiz_name,
                            'duration_in_minutes' => $q->duration,
                            'description' => strip_tags($q->description),
                            'start_date' => $q->start_date,
                            'end_date' => $q->end_date,
                            'number_of_questions' => $q->noq,
                            'maximum_attempts' => $q->maximum_attempts,
                            'pass_percentage' => $q->pass_percentage
                        ];
                    }
                }
                $response = [
                    'success' => true,
                    'description' => [
                        'quiz' => (count($quizList) > 0) ? $quizList : []
                    ]
                ];
                return response()->json($response, 200);
                break;
            case '1':
                $lists = $this->_quizAssignment->_fetchWrittenQuiz($indexNumber);

                if ($lists->isEmpty()) {
                    $response = [
                        'success' => true,
                        'description' => [
                            'quiz' => []
                        ]
                    ];
                    return response()->json($response, 401);
                }
                foreach ($lists as $list) {
                    $singleQuiz = $this->_quiz->_getQuiz($list->quiz_id);
                    $quizList[] = [
                        'quiz_id' => $singleQuiz->quid,
                        'quiz_name' => $singleQuiz->quiz_name,
                        'duration_in_minutes' => $singleQuiz->duration,
                        'description' => strip_tags($singleQuiz->description),
                        'start_date' => $singleQuiz->start_date,
                        'end_date' => $singleQuiz->end_date,
                        'number_of_questions' => $singleQuiz->noq,
                        'maximum_attempts' => $singleQuiz->maximum_attempts,
                        'pass_percentage' => $singleQuiz->pass_percentage
                    ];
                }
                $response = [
                    'success' => true,
                    'description' => [
                        'quiz' => (count($quizList) > 0) ? $quizList : []
                    ]
                ];
                return response()->json($response, 200);
                break;
        }
    }

    public function attemptQuiz(Request $request)
    {
        $auth = $request->header('Authorization');
        if (empty($auth)) {
            $response = [
                'success' => false,
                'description' => 'Authorization header required'
            ];
            return response()->json($response, 401);
        }

        $authorization = explode(':', $auth);
        $token = trim($authorization[1]);
        $user = $this->_authHistory->_getAuthUserId($token);
        if (!$user) {
            if (empty($auth)) {
                $response = [
                    'success' => false,
                    'description' => 'Wrong token'
                ];
                return response()->json($response, 401);
            }
        }
        $userId = $user->id_number;
        $isAuthExpired = $this->_authHistory->_isAuthExpired($userId, $token);
        if ($isAuthExpired) {
            $payload = [
                'is_expired' => 1
            ];
            $this->_authHistory->_expireAuth($userId, $token, $payload);
            $response = [
                'success' => false,
                'description' => 'Token is expired'
            ];
            return response()->json($response, 401);
        }

        $quizId = $request->get('quiz_id');
        $quiz = $this->_quiz->_getQuiz($quizId);
        if (!$quiz) {
            $response = [
                'success' => false,
                'description' => 'No quiz found with provided id'
            ];

            return response()->json($response, 404);
        }

        $questionIds = explode(',', $quiz->qids);
        $questions = [];
        for ($i = 0; $i < count($questionIds); $i++) {
            $question = $this->_question->_getQuestion($questionIds[$i]);
            $options = $this->_option->_getOptions($questionIds[$i]);
            $opts = [];
            foreach ($options as $option) {
                $opts[] = [
                    'option_id' => $option->oid,
                    'option' => strip_tags(str_replace('&nbsp;', '', $option->q_option))
                ];
            }
            $correct = explode(',', $quiz->correct_score);
            $incorrect = explode(',', $quiz->incorrect_score);

            $questions[] = [
                'question_id' => $question->qid,
                'question_type' => $question->question_type,
                'quiz' => [
                    'question' => strip_tags($question->question),
                    'options' => $opts
                ],
                'correct_score' => $correct[$i],
                'incorrect_score' => $incorrect[$i],
                'course_code' => $question->course_code,
                'course_name' => $question->course_name,
                'credit_hours' => $question->credit_hrs,

            ];
        }

        $qtns = $this->_shuffle_assoc($questions);

        $response = [
            'success' => true,
            'description' => $qtns
        ];
        return response()->json($response, 200);
    }

    private function _shuffle_assoc($list) { 
        if (!is_array($list)) return $list; 

        $keys = array_keys($list); 
        shuffle($keys); 
        $random = array(); 
        foreach ($keys as $key) { 
          $random[] = $list[$key]; 
        }
        return $random;
    }


    public function saveAnswers(Request $request)
    {
        $auth = $request->header('Authorization');
        if (empty($auth)) {
            $response = [
                'success' => false,
                'description' => 'Authorization header required'
            ];
            return response()->json($response, 401);
        }

        $authorization = explode(':', $auth);
        $token = trim($authorization[1]);
        $user = $this->_authHistory->_getAuthUserId($token);
        if (!$user) {
            if (empty($auth)) {
                $response = [
                    'success' => false,
                    'description' => 'Wrong token'
                ];
                return response()->json($response, 401);
            }
        }
        $userId = $user->id_number;
        $isAuthExpired = $this->_authHistory->_isAuthExpired($userId, $token);
        if ($isAuthExpired) {
            $payload = [
                'is_expired' => 1
            ];
            $this->_authHistory->_expireAuth($userId, $token, $payload);
            $response = [
                'success' => false,
                'description' => 'Token is expired'
            ];
            return response()->json($response, 401);
        }

        $indexNumber = $request->input('index_number');
        $quizId = $request->input('quiz_id');
        $ipAddress = $request->input('ip_address');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $answers = $request->input('answers');  // qid, oid
        $category = $request->input('category');

        if (empty($indexNumber) || empty($quizId) || empty($answers) || empty($startTime) || empty($endTime)) {
            $response = [
                'success' => false,
                'description' => 'All fields are required'
            ];

            return response()->json($response, 404);
        }

        $quiz = $this->_quiz->_getQuiz($quizId);

        if (!$quiz) {
            $response = [
                'success' => false,
                'description' => 'No quiz found with provided id'
            ];

            return response()->json($response, 404);
        }

//        $answers = json_encode($answers, true);

        if (count($answers) == 0) {
            $response = [
                'success' => false,
                'description' => 'Please provide the answers'
            ];

            return response()->json($response, 404);
        }

        $correct = [];
        $incorrect = [];
        $time = [];
        $questionIds = [];
        $individualScore = [];

//        $test = [];
//        $check = [];

        foreach ($answers as $answer) {
            $questionId = $answer['question_id'];
            $optionId = $answer['option_id'];
            $correctScore = $answer['correct_score'];
            $incorrectScore = $answer['incorrect_score'];
            $individualTime = $answer['time'];


            // compare answers
            $compare = $this->_option->_getScore($questionId, $optionId);

//            $check[] = [$compare];

            if ($compare) {
                if ($compare->score == 1) {   /// correct score
                    $correct[] = $correctScore;
                    $individualScore[] = $correctScore;
//                    $test[] = [
//                        'qid' => $questionId,
//                        'oid' => $optionId,
//                        'cs' => $correctScore,
//                        'is' => $incorrectScore
//                    ];
                } else {
                    $incorrect[] = $incorrectScore;
                    $individualScore[] = $incorrectScore;
//                    $test[] = [
//                        'qid' => $questionId,
//                        'oid' => $optionId,
//                        'cs' => $correctScore,
//                        'is' => $incorrectScore
//                    ];
                }
                $questionIds[] = $questionId;
                $time[] = $individualTime;
            }
        }

//        return response()->json($incorrect);

        $totalCorrectScore = array_sum($correct);
        $defaultPercentage = $quiz->pass_percentage;
        $totalTime = array_sum($time);

        $passPercentage = number_format((float)($totalCorrectScore / $quiz->noq) * 100, 2, '.', '');

        $status = '';
        if ($passPercentage >= $defaultPercentage) {
            $status = 'Pass';
        } elseif ($passPercentage < $defaultPercentage) {
            $status = 'Fail';
        }

        $resultData = [
            'quid' => $quizId,
            'uid' => $indexNumber,
            'result_status' => $status,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'categories' => $category,
            'category_range' => 0,
            'r_qids' => implode(',', $questionIds),
            'individual_time' => implode(',', $time),
            'total_time' => $totalTime,
            'score_obtained' => $totalCorrectScore,
            'percentage_obtained' => $passPercentage,
            'attempted_ip' => $ipAddress,
            'score_individual' => implode(',', $individualScore),
            'manual_valuation' => 0,
            'photo' => null
        ];



        $resultId = $this->_result->_saveResults($resultData);

        // update assessing student table
        $payload = [
            'status' => 1
        ];

        $this->_quizAssignment->_update($indexNumber, $quizId, $payload);

        $response = [
            'success' => true,
            'description' => [
                'quiz_id' => $quizId,
                'result_id' => $resultId
            ]
        ];

        return response()->json($response, 201);
    }


    public function getQuizResult(Request $request)
    {
        $auth = $request->header('Authorization');
        if (empty($auth)) {
            $response = [
                'success' => false,
                'description' => 'Authorization header required'
            ];
            return response()->json($response, 401);
        }

        $authorization = explode(':', $auth);
        $token = trim($authorization[1]);
        $user = $this->_authHistory->_getAuthUserId($token);
        if (!$user) {
            if (empty($auth)) {
                $response = [
                    'success' => false,
                    'description' => 'Wrong token'
                ];
                return response()->json($response, 401);
            }
        }
        $userId = $user->id_number;
        $isAuthExpired = $this->_authHistory->_isAuthExpired($userId, $token);
        if ($isAuthExpired) {
            $payload = [
                'is_expired' => 1
            ];
            $this->_authHistory->_expireAuth($userId, $token, $payload);
            $response = [
                'success' => false,
                'description' => 'Token is expired'
            ];
            return response()->json($response, 401);
        }

        $indexNumber = $request->get('index_number');
        $quizId = $request->get('quiz_id');
        $resultId = $request->get('result_id');

        if (!empty($resultId)) {
            $result = $this->_result->_getResult($resultId, $quizId, $indexNumber);
        } else {
            $result = $this->_result->_getResultWithoutId($quizId, $indexNumber);
        }

        if (!$result) {
            $response = [
                'success' => false,
                'description' => 'No result found'
            ];
            return response()->json($response, 404);
        }

        $quiz = $this->_quiz->_getQuiz($result->quid);
        $questionIds = explode(',', $quiz->qids);
        $questions = [];


        for ($i = 0; $i < count($questionIds); $i++) {
            $question = $this->_question->_getQuestion($questionIds[$i]);
            $options = $this->_option->_getOptions($questionIds[$i]);
            $opts = [];
            foreach ($options as $option) {
                if ($option->score == 1) {      // check for only correct answers
                    $opts[] = [
                        'option_id' => $option->oid,
                        'option' => strip_tags(str_replace('&nbsp;', '', $option->q_option))
                    ];
                }
            }
            $correct = explode(',', $quiz->correct_score);
            $incorrect = explode(',', $quiz->incorrect_score);

            $questions[] = [
                'question_id' => $question->qid,
                'question_type' => $question->question_type,
                'quiz' => [
                    'question' => strip_tags($question->question),
                    'description' => strip_tags($question->description),
                    'answer' => $opts
                ],
                'course_id' => $this->_account->_getCourse($question->cid)->course_code,
                'course' => $this->_account->_getCourse($question->cid)->course_name,
                'semester' => $question->lid,
                'correct_score' => $correct[$i],
                'incorrect_score' => $incorrect[$i]

            ];
        }

        $defaultPercentage = $quiz->pass_percentage;
        $totalCorrectScore = $result->score_obtained;
        $passPercentage = number_format((float)($totalCorrectScore / $quiz->noq) * 100, 2, '.', '');

        $status = '';

        if ($passPercentage >= $defaultPercentage) {
            $status = 'Pass';
        } elseif ($passPercentage < $defaultPercentage) {
            $status = 'Fail';
        }

        $results = [
            'result_id' => $result->rid,
            'quiz_id' => $result->quid,
            'total_time' => $result->total_time,
            'total_score' => $result->score_obtained,
            'percentage_score' => $result->percentage_obtained,
            'status' => $status,
            'breakdown' => $questions
        ];

        $response = [
            'success' => true,
            'description' => [
                'results' => $results
            ]
        ];

        return response()->json($response, 200);
    }
}
