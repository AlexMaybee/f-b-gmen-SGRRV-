<?php

class Exchangefunctions{

    const USER_1C = 38; //Вставить ID пользователя 1С

    public function test(){
        ($this->isUser1c()) ? $name = '1C' : $name = 'Solder';
        echo 'Hello, user '.$name.'!!!';
    }


    public function wokrWithDeal(&$arFields){

        $this->logData($arFields);

    }


    /*
     * @return true, if current user is for 1C exchange
     * @use it in every event
     * */
    private function isUser1c(){
        $result = false;
        global $USER;
        if(self::USER_1C == $USER->GetID()) $result = true;
        return $result;
    }

    private function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/myWokrPanelTestLog.log';
        file_put_contents($file, print_r([date('d.m.Y'),$data],true), FILE_APPEND | LOCK_EX);
    }
}