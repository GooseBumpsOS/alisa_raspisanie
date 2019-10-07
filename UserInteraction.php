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

    /*JSON Data input*/
    function __construct($data)
    {
        $this->allData = $data;
        $this->userId = $data['session']['user_id'];
        $this->db = new DbLayer('root', '9e4ed01e02', '127.0.0.1', 'alisa');
    }

    public function start()
    {

        if (isset($this->allData['request']['payload'])) //добавить свой обработчик payload, тк этот сугубо для регистрации
        {

            $payload = json_decode($this->allData['request']['payload'], true);

            switch (array_keys($payload)[0]) {
                case 'TableTime':
                    $this->userReqData = 'TableTime';
                    break;

                default:
                    $this->db->update(array_keys($payload)[0], array_values($payload)[0], $this->userId);
                    break;
            }

        }


        $this->isUserExist();

    }

    private function isUserExist()
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

        if (count(array_filter($userInfo)) == count($userInfo)) { //если все не null => есть незаполненные поля реги

            switch ($this->userReqData) {
                case 'TableTime':
                    $this->showTimetable($this->userDbInfo);
                    break;

                default:
                    $this->showErrorMessage();
                    break;
            }

        } else {

            $this->makeRegister($userInfo);//передаем сюда результат селект

        }

    }

    private function makeRegister($userInfo)
    { //здесь свитч для того чтобы определить на каком ты этапе


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

    private function showErrorMessage()
    {

        $resRes = ['Извините, не могу вас понять.', 'Я вас не пойму, вы что с ЭУИС?', 'Это сообщение с болота? Давай еще раз'];

        echo '{
  "response": {
    "text": "'. $resRes[array_rand($resRes)] .'",
    "tts": "'. $resRes[array_rand($resRes)] .'",
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

    private function showTimetable($userInfo)
    {

        echo '{
  "response": {
    "text": "Здравствуйте! Это мы, хороводоведы.",
    "tts": "Здравствуйте! Это мы, хоров+одо в+еды.",
    "buttons": [
        {
            "title": "Надпись на кнопке",
               "payload": "{\"' . 'TableTime' . '\" : ' . '1' . '}",
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
                $this->makeRegButton($apiData, 'Выберите форму обучения: ', 'edForm');


                break;
            case 'inst':

                $apiData = json_decode(file_get_contents('https://bot-srv.mgsu.ru/api/get/institute?gradeId=' . $this->userDbInfo['edForm']), true);
                $this->makeRegButton($apiData, 'Выберите институт: ', 'inst');

                break;
            case 'fac':

                $apiData = json_decode(file_get_contents('https://bot-srv.mgsu.ru/api/get/faculty?instituteId=' . $this->userDbInfo['inst']), true);
                $this->makeRegButton($apiData, 'Выберите факультут: ', 'fac');

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

}