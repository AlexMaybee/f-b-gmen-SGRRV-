<?php
/*
 * @file for income data functions
 * */

namespace Crmgenesis\Exchange1c;

use  \Crmgenesis\Exchange1c\Bitrixfunctions;

class Incomeb24{

    public function workWithIncomeContact($data){
        $result = [
            'time' => date('d.m.Y H:i:s', strtotime('now')),
            'result' => false,
            'error' => false,
        ];

        // !!! если авторизированный пользователь == 1С, то принимаем (в событиях наоборот)
        if(Bitrixfunctions::is_1cUser() === true){

        }
        $result['result'] = $data;


        return $result;
    }

}