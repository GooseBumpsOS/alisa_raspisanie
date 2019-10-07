<?php

use req\UserInteraction;

require_once 'UserInteraction.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Получаем запрос пользователя
 */
$dataRow = file_get_contents('php://input');
header('Content-Type: application/json');


/**
 * Здесь будет ответ
 */
$response = '';

/**
 * Впиши сюда своё активационное имя
 */
$mySkillName = 'gooseBotAlisa';

try{
    if (!empty($dataRow)) {
        /**
         * Простейший лог, чтобы проверять запросы.
         */
//        file_put_contents('alisalog.txt', date('Y-m-d H:i:s') . PHP_EOL . $dataRow . PHP_EOL, FILE_APPEND);

        /**
         * Преобразуем запрос пользователя в массив
         */
        //echo $dataRow;
        $data = json_decode($dataRow, true);

        /**
         * Проверяем наличие всех необходимых полей
         */
        if (!isset($data['request'], $data['session'], $data['session']['session_id'], $data['session']['message_id'], $data['session']['user_id'])) {
            /**
             * Нет всех необходимых полей. Не понятно, что вернуть, поэтому возвращаем ничего.
             */
            $result = json_encode([]);
        } else {



            $userInter = new UserInteraction($data);
            $userInter->start();
            $userInter = null;

        }
    } else {
        $response = json_encode([
            'version' => '1.0',
            'session' => 'Error',
            'response' => [
                'text' => 'Отсутствуют данные',
                'tts' =>  'Отсутствуют данные'
            ]
        ]);
    }

    echo $response;

} catch(\Exception $e){
    echo $e->getMessage();
}
