<?php

namespace req;

require_once 'DbLayer.php';

use DbLayer\DbLayer;

class UserInteraction
{
    private $allData;
    private $userId;
    private $userDbInfo;
    private $userReqData;
    private $db;
    private $commnad;

    /*JSON Data input*/
    function __construct($data)
    {
        $this->allData = $data;
        $this->userId = $data['session']['user_id'];
        $this->db = new DbLayer('alisa', 'Q7pvA7KRZaZ9vZFt', '127.0.0.1', 'alisa');
        $this->userReqData = null;
        $this->getUserData();
    }

    private function getUserData()
    {

        if (isset($this->allData['request']['payload'])) //обработка команд для кнопок
        {

            $payload = json_decode($this->allData['request']['payload'], true);

            switch (array_keys($payload)[0]) {
                case 'TableTime':
                    $this->userReqData = 'TableTime';
                    break;

                case 'TableTimeNext':
                    $this->userReqData = 'TableTimeNext';
                    break;

                default:

                    if (array_keys($payload)[0] == 'groupId')
                    {
                        $this->congratulation();
                        $this->db->update(array_keys($payload)[0], array_values($payload)[0], $this->userId);
                        die();

                    }


                    $this->db->update(array_keys($payload)[0], array_values($payload)[0], $this->userId);
                    break;
            }

        } elseif (isset($this->allData['request']['command'])) {

            $command = $this->allData['request']['command'];
            $this->commnad = $command;

            switch (mb_strtolower($command)) {

                case 'расписание на сегодня':
                case 'расписание':
                    $this->userReqData = 'TableTime';
                    break;

                case 'расписание на завтра':
                    $this->userReqData = 'TableTimeNext';
                    break;

                case 'удали мой аккаунт':
                case 'удалить':
                    $this->userReqData = 'delete';
                    break;

                case 'помощь':
                case 'информация':
                case 'что ты умеешь?':
                case 'что ты умеешь':
                    $this->userReqData = 'info';
                    break;

                case 'фото с расписанием':
                    $this->userReqData = 'showPhotoUserClick';
                    break;
            }


        }

        if ($this->allData['session']['message_id'] == 0)
            $this->userReqData = 'hello';

        $this->userInter();

    }

    private function userInter()
    {
        try {
            $userInfo = $this->db->select($this->userId);
            if (!$userInfo)
                throw new \Exception('Пустой селект');
        } catch (\Exception $e) {

            $this->db->insertNewUserId($this->userId);
            $userInfo = $this->db->select($this->userId);

        }

        $this->userDbInfo = $userInfo;

        if (count(array_filter($userInfo)) == count($userInfo) || $userInfo['groupId'] != null) { //если все не null => есть незаполненные поля реги

            switch ($this->userReqData) {
                case 'TableTime':
                    $this->showTimetable($this->userDbInfo, time());
                    break;

                case 'TableTimeNext':
                      $this->showTimetable($this->userDbInfo, time() + 86400);
                      break;


                case 'hello':
                    $this->showMessage(['Привет, рады тебя видеть, напиши помощь для того чтобы увидеть мои возможности']);
                    break;

                case 'delete':
                    $this->deleteUser();
                    break;

                case 'info':
                    $this->showInfo();
                    break;

                case 'showPhotoUserClick':
                    $this->showMessage([' ']);
                    break;

                default:
                    $this->showMessage();
                    break;
            }

        } else {

            $this->makeRegister($userInfo);//передаем сюда результат селект

        }

    }

    private function makeRegister($userInfo)
    { //здесь свитч для того чтобы определить на каком ты этапе

        if (!empty($this->commnad) && preg_match('/[0-9]/', $this->commnad))
        {
            $this->db->update("groupId", $this->commnad, $this->userId);
            $this->congratulation();
            die();
        }

        switch (null) {


            case $userInfo['edForm']:
                $this->askQuestion('edForm');
                break;
            case $userInfo['inst']:
                $this->askQuestion('inst');
                break;
            case $userInfo['fac']:
                $this->askQuestion('fac');
                break;
            case $userInfo['curs']:
                $this->askQuestion('curs');
                break;
            case $userInfo['groupId']:
                $this->askQuestion('groupId');
                break;


        }


    }

    private function showMessage($response = ['Извините, не могу вас понять. Напишите помощь', 'Я вас не пойму, вы что с ЭУИС? Напишите помощь', 'Это сообщение с болота? Давай еще раз, напишите помощь'])
    {
        echo '{
  "response": {
    "text": "' . $response[array_rand($response)] . '",
    "tts": "' . $response[array_rand($response)] . '",
    "buttons": [
        {
            "title": "Расписание на сегодня",
               "payload": "{\"TableTime\" : 1}",
            "hide": true
        },
                {
            "title": "Расписание на завтра",
               "payload": "{\"TableTimeNext\" : 1}",
            "hide": true
        }
        
    ],
    "end_session": false
  },
  "session": {
        "session_id": "' . $this->allData['session']['session_id'] . '",
    "message_id": ' . $this->allData['session']['message_id'] . ',
    "user_id": "' . $this->userId . '"
  },
  "version": "1.0"
}';

        die();


    }


    private function makeRegButton($data, $text = ' ', $columnName)
    {
        $buttons = '';


        for ($i = 0; $i < count($data['data']); $i++) {

            $buttons = $buttons . '        {
            "title": "' . $data['data'][$i]['name'] . '",
            "payload": "{\"' . $columnName . '\" : ' . $data['data'][$i]['id'] . '}",
            "hide": true
        },';

        }

        $buttons = substr($buttons, 0, strlen($buttons) - 1);

        echo $response = '{
  "response": {
    "text": "' . $text . '",
    "tts": "' . $text . '",
    "buttons": [
              ' . $buttons . '
    ],
    "end_session": false
  },
  "session": {
    "session_id": "' . $this->allData['session']['session_id'] . '",
    "message_id": ' . $this->allData['session']['message_id'] . ',
    "user_id": "' . $this->userId . '"
  },
  "version": "1.0"
}';


    }

    private function showTimetable($userInfo, $time)
    {
        $imgUrl = "https://bot-srv.mgsu.ru/api/get/schedule?partyId={$userInfo['groupId']}&timestamp={$time}&image=true";

        $rawTimetable = file_get_contents("https://bot-srv.mgsu.ru/api/get/schedule?partyId={$userInfo['groupId']}&timestamp={$time}");


        $rawTimetable = json_decode($rawTimetable, true);
        $lessonCount = count($rawTimetable["data"]["Lessons"]);

        $textTimetable = $rawTimetable['data']['Lessons'][0]['date'] . '\n\n   Время начало занятий - ' . $rawTimetable['data']['Lessons'][0]['startTime'] . '\n\n\n';

        for ($i = 0; $i < $lessonCount; $i++) {

            $textTimetable .= $rawTimetable['data']['Lessons'][$i]['podgrs'][0]['aud'] . ' : ' . $rawTimetable['data']['Lessons'][$i]['podgrs'][0]['textLesson'] . '\n\n';


        }


        echo '{
  "response": {
    "text": "' . $textTimetable . '",
    "tts": "' . $textTimetable . '",
    "buttons": [
        {
            "title": "Фото с расписанием",
            "url" : "' . $imgUrl . '",
           
            "hide": false
        }, 
         {
            "title": "Расписание на сегодня",
               "payload": "{\"TableTime\" : 1}",
            "hide": true
        }, 
                 {
            "title": "Расписание на завтра",
               "payload": "{\"TableTimeNext\" : 1}",
            "hide": true
        }
    ],
    "end_session": false
  },
  "session": {
    "session_id": "' . $this->allData['session']['session_id'] . '",
    "message_id": ' . $this->allData['session']['message_id'] . ',
    "user_id": "' . $this->userId . '"
  },
  "version": "1.0"
}';

    }

    private function askQuestion($question)
    {
        switch ($question) {


            case 'edForm':

                $apiData = json_decode(file_get_contents('https://bot-srv.mgsu.ru/api/get/grade'), true);
                $this->makeRegButton($apiData, 'Привет!\n \n Для того чтобы начать пользоваться нашим новым ботом, тебе нужно указать данные о себе. Выберите форму обучения или назови уникальный номер группы: ', 'edForm');


                break;
            case 'inst':

                $apiData = json_decode(file_get_contents('https://bot-srv.mgsu.ru/api/get/institute?gradeId=' . $this->userDbInfo['edForm']), true);
                $this->makeRegButton($apiData, 'Выберите институт: ', 'inst');

                break;
            case 'fac':

                $apiData = json_decode(file_get_contents('https://bot-srv.mgsu.ru/api/get/faculty?instituteId=' . $this->userDbInfo['inst']), true);
                $this->makeRegButton($apiData, 'Выберите факультет: ', 'fac');

                break;
            case 'curs':

                $apiData = json_decode(file_get_contents('http://bot-srv.mgsu.ru/api/get/year?facultyId=' . $this->userDbInfo['fac']), true);
                $this->makeRegButton($apiData, 'Выберите курс: ', 'curs');

                break;
            case 'groupId':

                $apiData = json_decode(file_get_contents('http://bot-srv.mgsu.ru/api/get/party?yearId=' . $this->userDbInfo['curs']), true);
                $this->makeRegButton($apiData, 'Выберите вашу группу: ', 'groupId');

                break;


        }

    }

    private function deleteUser()
    {
        $this->db->deleteUserById($this->userId);

        echo '{
  "response": {
    "text": "Удалено.",
    "tts": "Удалено",
    "end_session": false
  },
  "session": {
    "session_id": "' . $this->allData['session']['session_id'] . '",
    "message_id": ' . $this->allData['session']['message_id'] . ',
    "user_id": "' . $this->userId . '"
  },
  "version": "1.0"
}';

        die();

    }

    private function congratulation()
    {

        echo '{
  "response": {
    "text": "Поздравляю, вы зарегестрировались. Напиши помощь для подсказки",
    "tts": "Поздравляю, вы зарегестрировались. Напиши помощь для подсказки",
        "buttons": [
         {
            "title": "Расписание на сегодня",
               "payload": "{\"TableTime\" : 1}",
            "hide": true
        }
    ],
    "end_session": false
  },
  "session": {
    "session_id": "' . $this->allData['session']['session_id'] . '",
    "message_id": ' . $this->allData['session']['message_id'] . ',
    "user_id": "' . $this->userId . '"
  },
  "version": "1.0"
}';

    }

    private function showInfo()
    {

        echo '{
  "response": {
    "text": "Помощь - для показа подсказок. Удалить - удалит ваш аккаунт. Расписание - показать расписание",
    "tts": "Помощь - для показа подсказок. Удалить - удалит ваш аккаунт. Расписание - показать расписание",
        "buttons": [
         {
            "title": "Расписание на сегодня",
               "payload": "{\"TableTime\" : 1}",
            "hide": true
        }
    ],
    "end_session": false
  },
  "session": {
    "session_id": "' . $this->allData['session']['session_id'] . '",
    "message_id": ' . $this->allData['session']['message_id'] . ',
    "user_id": "' . $this->userId . '"
  },
  "version": "1.0"
}';

    }

}
