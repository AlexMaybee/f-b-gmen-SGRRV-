<?php
/*
 * @file for income data functions
 * */

namespace Crmgenesis\Exchange1c;

class incomeb24{

    public function workWithIncomeContact($data){
        $result = [
            'time' => date('d.m.Y H:i:s', strtotime('now')),
            'result' => false,
            'error' => false,
        ];

        $bitrixfunctionsObj = new bitrixfunctions();

        // !!! если авторизированный пользователь == 1С, то принимаем (в событиях наоборот)
        if($bitrixfunctionsObj->is_1cUser() === true){

        }
        $result['result'] = $data;


        return $result;
    }

}