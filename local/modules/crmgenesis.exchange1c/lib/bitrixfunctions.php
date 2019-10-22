<?php

/*
 * @file for functions, which can use all other classes
 * */
namespace Crmgenesis\Exchange1c;

use Bitrix\Main\Web\HttpClient;
use \Bitrix\Crm\EntityRequisite;

class bitrixfunctions{

    const USER_1C = 40; //Вставить ID пользователя 1С

    public function logData($data){
        $file = $_SERVER["DOCUMENT_ROOT"].'/zzz.log';
        file_put_contents($file, print_r([date('d.m.Y H:i:s'),$data],true), FILE_APPEND | LOCK_EX);
    }

    /*
    * @return true, if current user is for 1C exchange
    * @use it in every event
    * */
    public function is_1cUser(){
        $result = false;
        global $USER;
        if(self::USER_1C == $USER->GetID()) $result = true;
        return $result;
    }


    public function getContactData($filter,$select){
        $result = [];
        $arr = \CCrmContact::GetListEx(['ID' => 'DESC'],$filter,false,false,$select,[]);
        while($ob = $arr->getNext()) $result[] = $ob;
        return $result;
    }

    public function updateContact($id,$fields){
        $result = [
            'result' => false,
            'error' => false,
        ];
        $entity = new \CCrmContact(false);
        $upd_contact = $entity->update($id,$fields);
        if($upd_contact) $result['result'] = $upd_contact;
        else $result['error'] = $entity->LAST_ERROR;
        return $result;
    }

    public function getCompanyData($filter,$select){
        $result = [];
        $arr = \CCrmCompany::GetListEx(['ID' => 'DESC'],$filter,false,false,$select,[]);
        while($ob = $arr->getNext()) $result[] = $ob;
        return $result;
    }

    public function updateCompany($id,$fields){
        $result = [
            'result' => false,
            'error' => false,
        ];
        $entity = new \CCrmCompany(false);
        $upd_company = $entity->update($id,$fields);
        if($upd_company) $result['result'] = $upd_company;
        else $result['error'] = $entity->LAST_ERROR;
        return $result;
    }

    public function getDealData($filter,$select){
        $result = [];
        $arr = \CCrmDeal::GetListEx(['ID' => 'DESC'],$filter,false,false,$select,[]);
        while($ob = $arr->getNext()) $result[] = $ob;
        return $result;
    }

    public function updateDeal($id,$fields){
        $result = [
            'result' => false,
            'error' => false,
        ];
        $entity = new \CCrmDeal(false);
        $upd_deal = $entity->update($id,$fields);
        if($upd_deal) $result['result'] = $upd_deal;
        else $result['error'] = $entity->LAST_ERROR;
        return $result;
    }

    public function getInvoiceData($filter,$select){
        $massive = \CCrmInvoice::GetList($arOrder = ["DATE_STATUS"=>"DESC"], $filter,false,false,$select,[]);
        while($ar_result = $massive->GetNext()){
            $invoices[] = $ar_result;
        }
        return $invoices;
    }

    public function updatInvoice($id,$fields){
        $result = [
            'result' => false,
            'error' => false,
        ];
        $entity = new \CCrmInvoice(false);
        $upd_invoice = $entity->update($id,$fields);
        if($upd_invoice) $result['result'] = $upd_invoice;
        else $result['error'] = $entity->LAST_ERROR;
        return $result;
    }

    public function getQuoteData($filter,$select){
        $massive = \CCrmQuote::GetList($arOrder = ["DATE_STATUS"=>"DESC"], $filter,false,false,$select,[]);
        while($ar_result = $massive->GetNext()){
            $invoices[] = $ar_result;
        }
        return $invoices;
    }

    public function getIMcontact($filter,$select){
        $dbRes = \CCrmFieldMulti::GetListEx(['ID' => 'ASC'],$filter,false,false,$select);
        $allIm = [];
        while ($multiFields = $dbRes->getNext()) {
            $allIm[] = $multiFields;
        }
        return $allIm;
    }

    public function makePostRequest($url,$queryData){
        $httpClient = new HttpClient();

        $user = 'am@itlogic.biz';
        $pass = '12345678';
        $httpClient->setAuthorization($user, $pass);

        $httpClient->setHeader('Content-Type', 'application/json', true);
        $result = $httpClient->post($url, json_encode($queryData));
        return json_decode($result);
    }

    public function getRequisiteByFilter($filter){
        //Пример фильтра ["filter"=>["ENTITY_ID"=>6,"ENTITY_TYPE_ID"=>CCrmOwnerType::Company/*,'PRESET_ID'=>1*/]]
        $result = [];
        $req = new EntityRequisite;
        $dbRes = $req->getList($filter);
        while($mass = $dbRes->fetch())
            $result[] = $mass;
        return $result;
    }

    //товары по ID сделки
    public function getDealProducts($dealId){
        return $dealProducts = \CCrmDeal::LoadProductRows($dealId);
    }

    public function getInvoiceProducts($invoiceId){
        return $invoiceProducts = \CCrmInvoice::GetProductRows($invoiceId);//CCrmInvoice::GetProductRows($ID)
    }

    //данные товара
    public function getListElementsByFilter($arFilter,$arSelect){
        $result = [];
        $resultList = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        while ($list = $resultList->Fetch()) $result[] = $list;
        return $result;
    }

    //D7 - получение всех контактов из сделки
    public function getAllDealContacts($dealID){
        return \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($dealID);
    }

    public function getUserData($id){
       $result = [];
       $arr = \CUser::GetByID($id);
       return $result = $arr->Fetch();
    }

}