<?php

/*
 * @file for outgoing data functions
 * */

namespace Crmgenesis\Exchange1c;

use \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Localization\Loc,
    \Crmgenesis\Exchange1c\Bitrixfunctions;


class Customevent{

    const REQUEST_URL = 'https://cp.crmgenesis.com/z_alex_requests_test.php';
    const PRODUCT_CATALOG_ID = 26;
    const IBLOCK_31 = 31; //Договора
    const IBLOCK_32 = 32; //Торговые точки

    /*
     * @get Fields on Event
     * @get needed fields from Contact
     *
     * */

    public function workWithContact(&$arFields){
        $onSentArr = [];

        if(Bitrixfunctions::is_1cUser() === false){

            $contactSelect = [
                'UF_CRM_1571224508', //ID в 1С
//                'UF_CRM_1565362223845', //Направление
                'UF_CRM_1572857630', //Главный контрагент
                'UF_CRM_1572076993739', //Адрес доставки
                'UF_CRM_1573040726', //Деятельность
                'ID','NAME','LAST_NAME',
                'TYPE_ID','COMPANY_ID','COMMENTS',
                'SOURCE_ID','BIRTHDATE','ASSIGNED_BY_ID',
            ];
            $contactData = Bitrixfunctions::getContactData(['ID' => $arFields['ID']],$contactSelect);

            if($contactData){
                $assignedEmail = '';
                if($contactData[0]['ASSIGNED_BY_ID']){
                    $assignedByData = Bitrixfunctions::getUserData($contactData[0]['ASSIGNED_BY_ID']);
                    ($assignedByData)
//                    ? $assignedEmail = $assignedByData['LAST_NAME'].' '.$assignedByData['NAME']
                        ? $assignedEmail = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedEmail = '';
                }


                //Тип контакта/клиента (справочник)
                ($contactData[0]['TYPE_ID'])
                    ? $contactType = self::getRefferenceValArrByTypeAndId('CONTACT_TYPE',$contactData[0]['TYPE_ID'])
                    : $contactType = [];

                //Источник
                ($contactData[0]['SOURCE_ID'])
                    ? $source = self::getRefferenceValArrByTypeAndId('SOURCE',$contactData[0]['SOURCE_ID'])
                    : $source = [];

                //Деятельность (справочник - тип компании)
                ($contactData[0]['UF_CRM_1573040726'])
                    ? $activity = self::getRefferenceValArrByTypeAndId('COMPANY_TYPE',$contactData[0]['UF_CRM_1573040726'])
                    : $activity = [];

                //главный контрагент - компания либо контакт
                $mainContragent = self::returnCompanyOrContact1CiD($contactData[0]['UF_CRM_1572857630']);

                //тел, почта и мессенджеры
                $communications = self::getCommunications('CONTACT',$contactData[0]['ID']);

                //торговые точки
                $outlets = self::getOutlets(['IBLOCK_ID' => self::IBLOCK_32,'PROPERTY_92' => 'C_'.$contactData[0]['ID']]);

                //договора
                $contracts = self::getContracts(['IBLOCK_ID' => self::IBLOCK_31,'PROPERTY_89' => 'C_'.$contactData[0]['ID']]);

                //реквизиты
                $requisites = self::getRequisites(["ENTITY_ID" => $contactData[0]['ID'],"ENTITY_TYPE_ID" => \CCrmOwnerType::Contact]);

                $onSentArr = [
                    '1C_CONTACT_ID' => $contactData[0]['UF_CRM_1571224508'], //ID 1C
                    'NAME' => HTMLToTxt($contactData[0]['NAME']),
                    'LAST_NAME' => HTMLToTxt($contactData[0]['LAST_NAME']),
                    'TYPE' => $contactType,
                    '1C_COMPANY_ID' => '',
                    '1C_MAIN_CONTRAGENT_ID' => $mainContragent,
                    'SOURCE' => $source,
                    'ASSIGNED_BY' => $assignedEmail, //Договорились сопоставлять пользователей по емейлу
                    'COMMENTS' => $contactData[0]['COMMENTS'],
                    'SHIPPING_ADDRESS' => $contactData[0]['UF_CRM_1572076993739'], //Адрес Доставки
                    'ACTIVITY' => $activity, //Деятельность (размер)
                    'OUTLETS' => $outlets,
                    'CONTRACTS' => $contracts,
                    'COMMUNICATION' => $communications,
                    'REQUISITES' => $requisites,
                ];

                //если есть ID компании, то получаем и ее ID в 1с
                if($contactData[0]['COMPANY_ID'] > 0){
                    $companyResult = Bitrixfunctions::getCompanyData(
                        ['ID' => $contactData[0]['COMPANY_ID']],
                        ['ID','UF_CRM_1571234538']
                    );
                    if($companyResult)
                        $onSentArr['1C_COMPANY_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                }


//                $sentDataRes = Bitrixfunctions::makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_CONTACT',
//                        'DATA' => $onSentArr,
//                    ]);

                //здесь!
                //Если контакт новый, то из 1С должна вернуться ID
                if(!$contactData[0]['UF_CRM_1571224508']/* && $sentDataRes['1C_ID']*/){ //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571224508'] = str_shuffle('contact_').rand(1,5000);
                    $updContactRes = Bitrixfunctions::updateContact($contactData[0]['ID'],$updFields);
                }

            }

        }
//        else $arFields['1C_USER'] = 'Current user IS A 1C user!';

        Bitrixfunctions::logData($onSentArr);
    }


    /*
      * @get Fields on Event
      * @get needed fields from Company
      *
      * */
    public function workWithCompany(&$arFields){
        $onSentArr = [];

        if(Bitrixfunctions::is_1cUser() === false){

            $companySelect = [
                'UF_CRM_1571234538', //ID в 1С
                'UF_CRM_1572858286', //Тип клиента
                'UF_CRM_1572857700', //Главный контрагент
                'UF_CRM_1572858629', //Источник первичного интереса
                'UF_CRM_1572861522', //Адрес доставки
                'ID','TITLE','IS_MY_COMPANY',
                'CONTACT_ID','ASSIGNED_BY_ID',
                'COMMENTS','COMPANY_TYPE'
            ];
            $companyData = Bitrixfunctions::getCompanyData(['ID' => $arFields['ID']],$companySelect);

            if($companyData){

                $assignedEmail = '';
                if($companyData[0]['ASSIGNED_BY_ID']){
                    $assignedByData = Bitrixfunctions::getUserData($companyData[0]['ASSIGNED_BY_ID']);
                    ($assignedByData)
//                    ? $assignedEmail = $assignedByData['LAST_NAME'].' '.$assignedByData['NAME']
                        ? $assignedEmail = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedEmail = '';
                }

                //главный контрагент - компания либо контакт
                $mainContragent = self::returnCompanyOrContact1CiD($companyData[0]['UF_CRM_1572857700']);

                //Тип контакта/клиента (справочник)
                ($companyData[0]['UF_CRM_1572858286'])
                    ? $companyType = self::getRefferenceValArrByTypeAndId('CONTACT_TYPE',$companyData[0]['UF_CRM_1572858286'])
                    : $companyType = [];

                //Источник
                ($companyData[0]['UF_CRM_1572858629'])
                    ? $source = self::getRefferenceValArrByTypeAndId('SOURCE',$companyData[0]['UF_CRM_1572858629'])
                    : $source = [];

                //Деятельность (справочник - тип компании)
                ($companyData[0]['COMPANY_TYPE'])
                    ? $activity = self::getRefferenceValArrByTypeAndId('COMPANY_TYPE',$companyData[0]['COMPANY_TYPE'])
                    : $activity = [];

                //тел, почта и мессенджеры
                $communications = self::getCommunications('COMPANY',$companyData[0]['ID']);

                //торговые точки
                $outlets = self::getOutlets(['IBLOCK_ID' => self::IBLOCK_32,'PROPERTY_92' => 'CO_'.$companyData[0]['ID']]);

                //договора
                $contracts = self::getContracts(['IBLOCK_ID' => self::IBLOCK_31,'PROPERTY_89' => 'CO_'.$companyData[0]['ID']]);

                //реквизиты
                $requisites = self::getRequisites(["ENTITY_ID" => $companyData[0]['ID'],"ENTITY_TYPE_ID"=>\CCrmOwnerType::Company]);

                $onSentArr = [
                    '1C_COMPANY_ID' => $companyData[0]['UF_CRM_1571234538'], //ID 1C
                    'TITLE' => HTMLToTxt($companyData[0]['TITLE']),
                    'IS_MY_COMPANY' => $companyData[0]['IS_MY_COMPANY'],
                    'TYPE' => $companyType,
                    'MAIN_CONTRAGENT' => $mainContragent,
                    'SOURCE' => $source,
                    'SHIPPING_ADDRESS' => $companyData[0]['UF_CRM_1572861522'], //Адрес Доставки
                    'COMMENTS' => $companyData[0]['COMMENTS'],
                    'ASSIGNED_BY' => $assignedEmail,
                    'ACTIVITY' => $activity, //Деятельность (размер)
                    'CONTACTS' => [],
                    'OUTLETS' => $outlets,
                    'CONTRACTS' => $contracts,
                    'COMMUNICATION' => $communications,
                    'REQUISITES' => $requisites,
                ];


                //получение всех контактов к сделке + их 1с_ID
                $contacts = Bitrixfunctions::getContactData(
                    ['COMPANY_ID' => $companyData[0]['ID']],['ID','UF_CRM_1571224508']);
                if($contacts)
                    foreach ($contacts as $contact)
                        $onSentArr['CONTACTS'][]['1C_CONTACT_ID'] = $contact['UF_CRM_1571224508'];


//                $sentDataRes = Bitrixfunctions::makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_COMPANY',
//                        'DATA' => $onSentArr,
//                    ]);

                //здесь!
                //Если контакт новый, то из 1С должна вернуться ID
                if(!$companyData[0]['UF_CRM_1571234538']/* && $sentDataRes['1C_ID']*/){ //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571234538'] = str_shuffle('company_').rand(1,5000);
                    $updCompanyRes = Bitrixfunctions::updateCompany($companyData[0]['ID'],$updFields);
                }
            }
        }
        Bitrixfunctions::logData($onSentArr);
    }


    /*
    * @get Fields on Event
    * @get needed fields from Deals
    *
    * */
    public function workWithDeal(&$arFields){
        $onSentArr = [];

        if(Bitrixfunctions::is_1cUser() === false){
            $dealSelect = [
                'UF_CRM_1571137429', //ID в 1С
                'UF_CRM_1572869181', //Торговая точка
                'UF_CRM_1572869538', //Договор
                'CLOSEDATE', //Дата отгрузки
                'UF_CRM_1572870720', //Способ доставки
                'UF_CRM_1573033748', //Зона доставки
                'UF_CRM_1573043562', //Склад
                'OPPORTUNITY',
                'CURRENCY_ID',
                'COMMENTS',
                'ASSIGNED_BY_ID',
                'UF_CRM_1573032260', //Соглашение
                'UF_CRM_1573032335', //Группа контрагента
                'UF_CRM_1573034084', //Подразделение
                'TYPE_ID', //Операция

                'UF_CRM_1573047946', //Манагер

                'ID','TITLE','CATEGORY_ID',
                'COMPANY_ID','STAGE_ID',
            ];
            $dealData = Bitrixfunctions::getDealData(['ID' => $arFields['ID']],$dealSelect);
            if($dealData) {

                $assignedEmail = '';
                if($dealData[0]['ASSIGNED_BY_ID']){
                    $assignedByData = Bitrixfunctions::getUserData($dealData[0]['ASSIGNED_BY_ID']);
                    ($assignedByData)
                        ? $assignedEmail = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedEmail = '';
                }


                $managerEmail = '';
                if($dealData[0]['UF_CRM_1573047946']){
                    $managerData = Bitrixfunctions::getUserData($dealData[0]['UF_CRM_1573047946']);
                    ($managerData)
                        ? $managerEmail = $managerData['EMAIL'] //Временно!!!
                        : $managerEmail = '';
                }

                //ЗНАЧЕНИЯ ПОЛЬЗОВАТЕЛЬСКИХ ПОЛЕЙ

                //способ доставки
                ($dealData[0]['UF_CRM_1572870720'])
                    ? $shipmentMethod = self::getFieldValMassiveByValId(['ID' => $dealData[0]['UF_CRM_1572870720']])
                    : $shipmentMethod = [];

                //группа клиента
                ($dealData[0]['UF_CRM_1573032335'])
                    ? $clientGroup = self::getFieldValMassiveByValId(['ID' => $dealData[0]['UF_CRM_1573032335']])
                    : $clientGroup = [];

                //подразделение
                ($dealData[0]['UF_CRM_1573034084'])
                    ? $subDivision = self::getFieldValMassiveByValId(['ID' => $dealData[0]['UF_CRM_1573034084']])
                    : $subDivision = [];

                //ЗНАЧЕНИЯ ПОЛЬЗОВАТЕЛЬСКИХ ПОЛЕЙ

                //название категории
                $category = ['ID' => $dealData[0]['CATEGORY_ID'],
                    'NAME' => Bitrixfunctions::getCategoryNameById($dealData[0]['CATEGORY_ID'])];

                //Стадия в зависимости от направления)
                $stage = [];
                $stages = Bitrixfunctions::getCategoryStages($dealData[0]['CATEGORY_ID']);
                if($stages){
                    foreach ($stages as $stKey => $stVal)
                        if($stKey == $dealData[0]['STAGE_ID'])
                            $stage = ['ID' => $stKey,'NAME' => $stVal];
                }

                //Стадия в зависимости от направления)


                //Операция (справочник - тип сделки)
                ($dealData[0]['TYPE_ID'])
                    ? $operation = self::getRefferenceValArrByTypeAndId('DEAL_TYPE',$dealData[0]['TYPE_ID'])
                    : $operation = [];

                //торговые точки
                $outlets = self::getOutlets(['IBLOCK_ID' => self::IBLOCK_32,'ID' => $dealData[0]['UF_CRM_1572869181']]);

                //договора
                $contractsArr = self::getContracts(['IBLOCK_ID' => self::IBLOCK_31,'ID' => $dealData[0]['UF_CRM_1572869538']]);
                ($contractsArr) ? $contract = $contractsArr[0] : $contract = [];

                $onSentArr = [
                    'TITLE' => HTMLToTxt($dealData[0]['TITLE']),
                    '1C_DEAL_ID' => $dealData[0]['UF_CRM_1571137429'], //ID 1C

                    'CATEGORY_ID' => $category,
                    'STAGE' => $stage,

                    'SHIPMENT_DATE' => $dealData[0]['CLOSEDATE'],
                    'SHIPMENT_METHOD' => $shipmentMethod,
                    'SHIPMENT_ZONE' => $dealData[0]['UF_CRM_1573033748'],
                    'STORE' => $dealData[0]['UF_CRM_1573043562'],
                    'OPPORTUNITY' => $dealData[0]['OPPORTUNITY'], //DEAL_SUM
                    'CURRENCY_ID' => $dealData[0]['CURRENCY_ID'], //CURRENCY
                    'COMMENTS' => $dealData[0]['COMMENTS'],

                    'ASSIGNED_BY' => $assignedEmail, //ДОговорились сопоставлять по email
                    'MANAGER' => $managerEmail,

                    'AGREEMENT' => $dealData[0]['UF_CRM_1573032260'],
                    'CLIENT_CROUP' => $clientGroup,
                    'SUB_DIVISION' => $subDivision,
                    'OPERATION' => $operation,

                    '1C_COMPANY_ID' => '',
                    'CONTACTS' => [],
                    'PRODUCTS' => [],
                    'OUTLETS' => $outlets,
                    'CONTRACT' => $contract,
                ];


                //Получаем товарі сделки + их код из 1С
                $dealProducts = Bitrixfunctions::getDealProducts($dealData[0]['ID']);
                if($dealProducts){

//                    $onSentArr['PRODUCTS_TEST'] = $dealProducts;

                    foreach ($dealProducts as $product){

                        //PROPERTY_88 - ID товара в 1С
                        $productProp = Bitrixfunctions::getListElementsByFilter(
                            ['ID' => $product['PRODUCT_ID'],'IBLOCK_ID' => self::PRODUCT_CATALOG_ID],
                            ['ID','NAME','PROPERTY_88']
                        );

                        if($productProp){
                            $product['1C_PRODUCT_ID'] = $productProp[0]['PROPERTY_88_VALUE'];
                            $onSentArr['PRODUCTS'][] = $product;
                        }

                    }
                }

                //Все контакты и их ID в 1С
                $contactsIds = Bitrixfunctions::getAllDealContacts($dealData[0]['ID']);
                if($contactsIds){
//                    $dealData[0]['CONTACTS'] = [];
                    $contacts = Bitrixfunctions::getContactData(
                        ['ID' => $contactsIds],['ID','UF_CRM_1571224508']);
                    if($contacts){
                        foreach ($contacts as $contact)
                            $onSentArr['CONTACTS'][]['1C_CONTACT_ID'] = $contact['UF_CRM_1571224508'];
                    }
                }

                //Компания и ее ID в 1С
                if($dealData[0]['COMPANY_ID'] > 0){
                    $companyResult = Bitrixfunctions::getCompanyData(
                        ['ID' => $dealData[0]['COMPANY_ID']],
                        ['ID','UF_CRM_1571234538']
                    );
                    if($companyResult) $onSentArr['1C_COMPANY_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                }


                //                $sentDataRes = Bitrixfunctions::makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_DEAL',
//                        'DATA' => $onSentArr,
//                    ]);

                //здесь!
                //Если сделка новая то из 1С должна вернуться ID
                if(!$dealData[0]['UF_CRM_1571137429']/* && $sentDataRes['1C_ID']*/){ //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571137429'] = str_shuffle('deal_').rand(1,5000);
                    $updDealRes = Bitrixfunctions::updateDeal($dealData[0]['ID'],$updFields);
                }
                
            }
        }

        Bitrixfunctions::logData($onSentArr);
    }


    /*
      * @get Fields on Event
      * @get needed fields from Invoice
      *
      * */
    public function workWithInvoice(&$arFields){
        $onSentArr = [];

        if (Bitrixfunctions::is_1cUser() === false) {

            $invoiceSelect = [
                'ID','ORDER_TOPIC','ACCOUNT_NUMBER','STATUS_ID','PAY_VOUCHER_DATE',
                'DATE_BILL','DATE_PAY_BEFORE','RESPONSIBLE_ID',
                'CURRENCY','PRICE',
                'UF_COMPANY_ID','UF_CONTACT_ID','UF_DEAL_ID'

                ,'UF_MYCOMPANY_ID',

//                'UF_QUOTE_ID',
                'USER_DESCRIPTION',
                'COMMENTS',
                'PRODUCT_ROWS',
                'INVOICE_PROPERTIES', //ID манагера по работе с клиентами
                'UF_CRM_1571761946', //ID в 1С
                'UF_CRM_1573053794', //Договор
                'UF_CRM_1573053996', //Торговые точки
                'UF_CRM_5DC2E5E14F9A0', //Подразделение
                'UF_CRM_5DC2E5E11C29C', //Способ доставки
                'UF_CRM_5DC2E5E12E942', //Соглашение
                'UF_CRM_5DC2E5E15B6C3', //Склад
                'UF_CRM_5DC2E5E1453D9', //Зона доставки
                'UF_CRM_5DC2E5E165CCF', //Манагер
            ];
            $invoiceData = Bitrixfunctions::getInvoiceData(['ID' => $arFields['ID']],$invoiceSelect);
            if($invoiceData) {

                $assignedEmail = '';
                if($invoiceData[0]['RESPONSIBLE_ID']){
                    $assignedByData = Bitrixfunctions::getUserData($invoiceData[0]['RESPONSIBLE_ID']);
                    ($assignedByData)
//                    ? $assignedEmail = $assignedByData['LAST_NAME'].' '.$assignedByData['NAME']
                        ? $assignedEmail = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedEmail = '';
                }

                $managerEmail = '';
                if($invoiceData[0]['UF_CRM_5DC2E5E165CCF']){
                    $managerData = Bitrixfunctions::getUserData($invoiceData[0]['UF_CRM_5DC2E5E165CCF']);
                    ($managerData)
//                    ? $managerEmail = $managerData['LAST_NAME'].' '.$managerData['NAME']
                        ? $managerEmail = $managerData['EMAIL'] //Временно!!!
                        : $managerEmail = '';
                }

                //торговые точки
                $outlets = self::getOutlets(['IBLOCK_ID' => self::IBLOCK_32,'ID' => $invoiceData[0]['UF_CRM_1573053996']]);

                //договор
                $contractsArr = self::getContracts(['IBLOCK_ID' => self::IBLOCK_31,'ID' => $invoiceData[0]['UF_CRM_1573053794']]);
                ($contractsArr) ? $contract = $contractsArr[0] : $contract = [];

                $onSentArr = [
                    '1C_INVOICE_ID' => $invoiceData[0]['UF_CRM_1571761946'],
                    '1C_DEAL_ID' => '',
                    'TITLE' => $invoiceData[0]['ORDER_TOPIC'],
                    'NUMBER' => $invoiceData[0]['ACCOUNT_NUMBER'],
                    'SUM' => $invoiceData[0]['PRICE'],
                    'CURRENCY' => $invoiceData[0]['CURRENCY'],
                    'ASSIGNED_BY' => $assignedEmail,
                    'COMMENTS' => $invoiceData[0]['COMMENTS'],

                    'SUB_DIVISION' => $invoiceData[0]['UF_CRM_5DC2E5E14F9A0'],
                    'SHIPMENT_METHOD' => $invoiceData[0]['UF_CRM_5DC2E5E11C29C'],
                    'AGREEMENT' => $invoiceData[0]['UF_CRM_5DC2E5E12E942'],
                    'STORE' => $invoiceData[0]['UF_CRM_5DC2E5E15B6C3'],
                    'SHIPMENT_ZONE' => $invoiceData[0]['UF_CRM_5DC2E5E1453D9'],
                    'MANAGER' => $managerEmail,

//                    '1C_QUOTE_ID' => '',

                    'OUTLETS' => $outlets,
                    'CONTRACT' => $contract,
                    'PRODUCTS' => [],

                    'MY_COMPANY' => [
                        '1C_MY_COMPANY_ID' => '',
                        'REQUISITES' => [
                            'MAIN' => [],
                            'BANK' => [],
                        ],
                    ],
                    'CLIENT' => [
                        '1C_CONTACT_ID' => '',
                        '1C_COMPANY_ID' => '',
                        'REQUISITES' => [
                            'MAIN' => [],
                            'BANK' => [],
                        ],
                    ],

                    /*
                    'SHIPMENT_DATE' => $dealData[0]['CLOSEDATE'],
                    'CLIENT_CROUP' => $dealData[0]['UF_CRM_1573032335'],
                    'OPERATION' => $dealData[0]['TYPE_ID'],
                     */


                ];

                //получение товаров


                //реквизиты нашей компании - возвр. список ID главного реквизита + ID банк. реквизита
                $requisitesSelected = Bitrixfunctions::getSelectedRequisitesInInvoice(
                    ['ENTITY_ID' => $invoiceData[0]['ID'],'ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice]);

                if($requisitesSelected){

                    //Получение наших реквизитов, отдельно Общего реквизита и отдельно Банка (может быть несколько
                    if($requisitesSelected[0]['MC_REQUISITE_ID']){
                        $reqFilter = [
                            "filter" => ["ID" => $requisitesSelected[0]['MC_REQUISITE_ID']],
                            'select' => ['*','UF_*'],
                            'order' => ['ID' => 'DESC'],
                        ];
                        $myMainRequisites = Bitrixfunctions::getRequisiteByFilter($reqFilter);
                        if($myMainRequisites) $onSentArr['MY_COMPANY']['REQUISITES']['MAIN'] = $myMainRequisites[0];
                    }

                    //банковские реквизиты
                    if($requisitesSelected[0]['MC_BANK_DETAIL_ID']){
                        $myBankRequisites = Bitrixfunctions::getBankRequisitesByFilter(
                            ['ID' => $requisitesSelected[0]['MC_BANK_DETAIL_ID']]
                        );
                        if($myBankRequisites) $onSentArr['MY_COMPANY']['REQUISITES']['BANK'] = $myBankRequisites[0];
                    }


                    //Получение клиентских реквизитов, отдельно Общего реквизита и отдельно Банка (может быть несколько
                    if($requisitesSelected[0]['REQUISITE_ID']){
                        $reqFilter = [
                            "filter" => ["ID" => $requisitesSelected[0]['REQUISITE_ID']],
                            'select' => ['*','UF_*'],
                            'order' => ['ID' => 'DESC'],
                        ];
                        $clientMainRequisites = Bitrixfunctions::getRequisiteByFilter($reqFilter);
                        if($clientMainRequisites) $onSentArr['CLIENT']['REQUISITES']['MAIN'] = $clientMainRequisites[0];
                    }

                    //клиент, банковские реквизиты
                    if($requisitesSelected[0]['BANK_DETAIL_ID']){
                        $clientBankRequisites = Bitrixfunctions::getBankRequisitesByFilter(
                            ['ID' => $requisitesSelected[0]['BANK_DETAIL_ID']]
                        );
                        if($clientBankRequisites) $onSentArr['CLIENT']['REQUISITES']['BANK'] = $clientBankRequisites[0];
                    }
                }


                //My_COMPANY_ID
                if($invoiceData[0]['UF_MYCOMPANY_ID'] > 0){
                    $companyResult = Bitrixfunctions::getCompanyData(
                        ['ID' => $invoiceData[0]['UF_MYCOMPANY_ID']],
                        ['ID','UF_CRM_1571234538']
                    );
                    if($companyResult) $onSentArr['MY_COMPANY']['1C_MY_COMPANY_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                }


                //получение 1C_ID сделки, компании и контакта, ну и предложения - пока без!

                if($invoiceData[0]['UF_CONTACT_ID'] > 0){
                    $contactData = Bitrixfunctions::getContactData(
                        ['ID' => $invoiceData[0]['UF_CONTACT_ID']],['ID','UF_CRM_1571224508']);
                    if($contactData)
                        $onSentArr['CLIENT']['1C_CONTACT_ID'] = $contactData[0]['UF_CRM_1571224508'];
                }


                if($invoiceData[0]['UF_COMPANY_ID'] > 0){
                    $companyResult = Bitrixfunctions::getCompanyData(
                        ['ID' => $invoiceData[0]['UF_COMPANY_ID']],
                        ['ID','UF_CRM_1571234538']
                    );
                    if($companyResult) $onSentArr['CLIENT']['1C_COMPANY_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                }

                if($invoiceData[0]['UF_DEAL_ID'] > 0){
                    $dealResult = Bitrixfunctions::getDealData(
                        ['ID' => $invoiceData[0]['UF_DEAL_ID']],
                        ['ID','UF_CRM_1571137429']
                    );
                    if($dealResult) $onSentArr['1C_DEAL_ID'] = $dealResult[0]['UF_CRM_1571137429'];
                }

                //qoute SAME!!!
//                if($invoiceData[0]['UF_QUOTE_ID'] > 0){
//                    $quoteResult = Bitrixfunctions::getQuoteData(
//                        ['ID' => $invoiceData[0]['UF_QUOTE_ID']],
//                        ['ID','UF_CRM_1571762030']
//                    );
//                    if($quoteResult) $onSentArr['1C_QUOTE_ID'] = $quoteResult[0]['UF_CRM_1571762030'];
//                }


                //торговые точки, массив
//                $outlets = Bitrixfunctions::getListElementsByFilter(
//                    ['IBLOCK_ID' => self::IBLOCK_32,'ID' => $invoiceData[0]['UF_CRM_1573053996']],
//                    ['ID','NAME','PROPERTY_92','PROPERTY_93','PROPERTY_96','PROPERTY_97','PROPERTY_98','PROPERTY_99','PROPERTY_100']
//                );
//
//                if($outlets)
//                    foreach ($outlets as $outlet){
//
//                        //получаем 1C ID контактов на точках, если они выбраны в точке
//                        $serviceContact = '';
//                        $lawyerContact = '';
//                        $accountantContact = '';
//                        $decisionMakingContact = '';
//                        $marketingContact = '';
//
//                        if($outlet['PROPERTY_96_VALUE']){
//                            $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_96_VALUE']],['ID','UF_CRM_1571224508']);
//                            ($contactResult) ? $serviceContact = $contactResult[0]['UF_CRM_1571224508'] : $serviceContact = '';
//                        }
//                        if($outlet['PROPERTY_97_VALUE']){
//                            $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_97_VALUE']],['ID','UF_CRM_1571224508']);
//                            ($contactResult) ? $lawyerContact = $contactResult[0]['UF_CRM_1571224508'] : $lawyerContact = '';
//                        }
//                        if($outlet['PROPERTY_98_VALUE']){
//                            $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_98_VALUE']],['ID','UF_CRM_1571224508']);
//                            ($contactResult) ? $accountantContact = $contactResult[0]['UF_CRM_1571224508'] : $accountantContact = '';
//                        }
//                        if($outlet['PROPERTY_99_VALUE']){
//                            $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_99_VALUE']],['ID','UF_CRM_1571224508']);
//                            ($contactResult) ? $decisionMakingContact = $contactResult[0]['UF_CRM_1571224508'] : $decisionMakingContact = '';
//                        }
//                        if($outlet['PROPERTY_100_VALUE']){
//                            $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_100_VALUE']],['ID','UF_CRM_1571224508']);
//                            ($contactResult) ? $marketingContact = $contactResult[0]['UF_CRM_1571224508'] : $marketingContact = '';
//                        }
//
//                        $onSentArr['OUTLETS'][] = [
//                            'OUTLET_ADDRESS' => $outlet['NAME'],
//                            'SERVICE_CONTACT'=> $serviceContact,
//                            'LAWYER_CONTACT'=> $lawyerContact,
//                            'ACCOUNTANT_CONTACT'=> $accountantContact,
//                            'DECISION_MAKING_CONTACT'=> $decisionMakingContact,
//                            'MARKETING_CONTACT'=> $marketingContact,
//                            '1C_OUTLET_ID' => $outlet['PROPERTY_93_VALUE'],
//                        ];
//                    }

                //договора
//                $contracts = Bitrixfunctions::getListElementsByFilter(
//                    ['IBLOCK_ID' => self::IBLOCK_31,'ID' => $invoiceData[0]['UF_CRM_1573053794']],
//                    ['ID','NAME','PROPERTY_89','PROPERTY_90','PROPERTY_91']
//                );
//                if($contracts)
//                    foreach ($contracts as $contract)
//                        $onSentArr['CONTRACT'] = [
//                            'CONTRACT' => $contract['NAME'],
//                            'DATE_FROM' => $contract['PROPERTY_90_VALUE'],
//                            '1C_CONTRACT_ID' => $contract['PROPERTY_91_VALUE'],
//                        ];

                //products
                $invoiceProducts = Bitrixfunctions::getInvoiceProducts($invoiceData[0]['ID']);
                if($invoiceProducts){
                    foreach ($invoiceProducts as $product){

                        //PROPERTY_88 - ID товара в 1С
                        $productProp = Bitrixfunctions::getListElementsByFilter(
                            ['ID' => $product['PRODUCT_ID'],'IBLOCK_ID' => self::PRODUCT_CATALOG_ID],
                            ['ID','NAME','PROPERTY_88']
                        );

                        if($productProp){

                            $product['1C_PRODUCT_ID'] = $productProp[0]['PROPERTY_88_VALUE'];
//                            $product['ID_2'] = $productProp[0]['ID'];

                            $onSentArr['PRODUCTS'][] = $product;

//                            $onSentArr['PRODUCTS'][] = [
//                                'NAME' => $productProp[0]['NAME'],
//                                '1C_PRODUCT_ID' => $productProp[0]['PROPERTY_88_VALUE'],
//                            ];

                        }

                    }
                }



                //                $sentDataRes = Bitrixfunctions::makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_INVOICE',
//                        'DATA' => $onSentArr,
//                    ]);


                //Если сделка новая то из 1С должна вернуться ID, ПОПРОБОВАТЬ ИЗМЕНИТЬ ПОЛЕ XML_ID на ID из 1С
                if(!$invoiceData[0]['UF_CRM_1571761946']/* && $sentDataRes['1C_ID']*/) { //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571761946'] = str_shuffle('invoice_').rand(1,5000);
                     $updInvoicetRes = Bitrixfunctions::updatInvoice($invoiceData[0]['ID'],$updFields);
                }

            }

        }
        Bitrixfunctions::logData($onSentArr);
    }

    //событие создание/обновление списков (договора/торговые точки
    public function workWithLists(&$arFields){

        if (Bitrixfunctions::is_1cUser() === false) {
            //Торговые точки
            if($arFields['IBLOCK_ID'] == self::IBLOCK_32)
                self::workWithList32($arFields);

            //Договора
            if($arFields['IBLOCK_ID'] == self::IBLOCK_31)
                self::workWithList31($arFields);
        }

    }

    private function workWithList32($arFields){

        $outlets = self::getOutlets(['IBLOCK_ID' => self::IBLOCK_32,'ID' => $arFields['ID']],'all');
        if($outlets) {

            $sentDataRes = Bitrixfunctions::makePostRequest(self::REQUEST_URL,
                    [
                        'ACTION' => 'CHECK_OUTLET',
                        'DATA' => $outlets[0],
                    ]);

            //Если точка новая то из 1С должна вернуться ID
            if(!$outlets[0]['1C_OUTLET_ID']/* && $sentDataRes['1C_ID']*/) { //!!!и результат в $sentDataRes true
//                     $updPropFields['93'] = $sentDataRes['1C_ID'];
                $updPropFields['93'] = str_shuffle('outlet_').rand(1,5000);;
                $updPropRes = Bitrixfunctions::updatePropertiesInListElement($outlets[0]['ID'],self::IBLOCK_32,$updPropFields);
            }

            Bitrixfunctions::logData($outlets);
        }

//        Bitrixfunctions::logData($arFields);
    }

    private function workWithList31($arFields){
        $elemResult = Bitrixfunctions::getListElementsByFilter(
            ['IBLOCK_ID' => self::IBLOCK_31,'ID' => $arFields['ID']],
                ['ID','NAME','PROPERTY_89','PROPERTY_90','PROPERTY_91']
        );

        $contracts = self::getContracts(['IBLOCK_ID' => self::IBLOCK_31,'ID' => $arFields['ID']],'all');

        if($contracts){

//            $sentDataRes = Bitrixfunctions::makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_CONTRACT',
//                        'DATA' => $contracts[0],
//                    ]);

            //Если договор новый то из 1С должна вернуться ID
            if(!$contracts[0]['1C_CONTRACT_ID']/* && $sentDataRes['1C_ID']*/) { //!!!и результат в $sentDataRes true
//                     $updPropFields['93'] = $sentDataRes['1C_ID'];
                $updPropFields['91'] = str_shuffle('contract_').rand(1,5000);
                $updPropRes = Bitrixfunctions::updatePropertiesInListElement($contracts[0]['ID'],self::IBLOCK_31,$updPropFields);
            }

        }
        
        Bitrixfunctions::logData($contracts);
    }


    private function returnCompanyOrContact1CiD($prop){
        $client1CiD = [];

        if($prop){
            $contrArr = explode('_',$prop);

            //получаем 1C_ID контакта
            if($contrArr[0] == 'C'){
                $contactResult = Bitrixfunctions::getContactData(['ID' => $contrArr[1]],['ID','UF_CRM_1571224508']);
                if($contactResult) {
                    $client1CiD['1C_CLIENT_ID'] = $contactResult[0]['UF_CRM_1571224508'];
                    $client1CiD['TYPE'] = $contrArr[0];
                }
            }
            //получаем 1C_ID компании
            if($contrArr[0] == 'CO'){
                $companyResult = Bitrixfunctions::getCompanyData(['ID' => $contrArr[1]],['ID','UF_CRM_1571234538']);
                if($companyResult) {
                    $client1CiD['1C_CLIENT_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                    $client1CiD['TYPE'] = $contrArr[0];
                }
            }
        }

        return $client1CiD;
    }


   //получение массива из правочника
    private function getRefferenceValArrByTypeAndId($refferId,$valId){
        $result = [];
        $typeArr = Bitrixfunctions::getReferenceBook(['ID' => 'DESC'],
            ['ENTITY_ID' => $refferId,'STATUS_ID' => $valId]);
        if($typeArr)
            $result = [
                'STATUS_ID' => $typeArr[0]['STATUS_ID'],
                'NAME' => $typeArr[0]['NAME'],
            ];
        return $result;
    }

    //коммуникация: телефоны, емейлы, мессенджеры
    private function getCommunications($entityType,$entityId){
        $result = [];
        $imFilter = ['ENTITY_ID'  => $entityType, 'ELEMENT_ID' => $entityId];
        $imSelect = ['TYPE_ID','VALUE_TYPE','VALUE'];
        $communications = Bitrixfunctions::getIMcontact($imFilter,$imSelect);
        if($communications){
            foreach ($communications as $comm){
                if($comm['TYPE_ID'] == 'EMAIL') {
                    $email = trim(substr(HTMLToTxt($comm['VALUE']),0,strpos(HTMLToTxt($comm['VALUE']),'[')));
                }
                else $email = HTMLToTxt($comm['VALUE']);

                $result[] = [
                    'TYPE_ID' => $comm['TYPE_ID'],
                    'TYPE' => $comm['VALUE_TYPE'],
                    'VALUE' => $email,
                ];
            }
        }
        return $result;
    }

    //торговые точки - получение точек по фильтру
    private function getOutlets($filter,$flag = false){
        $result = [];
        $outlets = Bitrixfunctions::getListElementsByFilter(
            $filter, ['ID','NAME','PROPERTY_92','PROPERTY_93','PROPERTY_96','PROPERTY_97','PROPERTY_98','PROPERTY_99','PROPERTY_100']);
        if($outlets)
            foreach ($outlets as $outlet){

                //получаем 1C ID контактов на точках, если они выбраны в точке
                $serviceContact = '';
                $lawyerContact = '';
                $accountantContact = '';
                $decisionMakingContact = '';
                $marketingContact = '';

                if($outlet['PROPERTY_96_VALUE']){
                    $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_96_VALUE']],['ID','UF_CRM_1571224508']);
                    ($contactResult) ? $serviceContact = $contactResult[0]['UF_CRM_1571224508'] : $serviceContact = '';
                }
                if($outlet['PROPERTY_97_VALUE']){
                    $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_97_VALUE']],['ID','UF_CRM_1571224508']);
                    ($contactResult) ? $lawyerContact = $contactResult[0]['UF_CRM_1571224508'] : $lawyerContact = '';
                }
                if($outlet['PROPERTY_98_VALUE']){
                    $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_98_VALUE']],['ID','UF_CRM_1571224508']);
                    ($contactResult) ? $accountantContact = $contactResult[0]['UF_CRM_1571224508'] : $accountantContact = '';
                }
                if($outlet['PROPERTY_99_VALUE']){
                    $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_99_VALUE']],['ID','UF_CRM_1571224508']);
                    ($contactResult) ? $decisionMakingContact = $contactResult[0]['UF_CRM_1571224508'] : $decisionMakingContact = '';
                }
                if($outlet['PROPERTY_100_VALUE']){
                    $contactResult = Bitrixfunctions::getContactData(['ID' => $outlet['PROPERTY_100_VALUE']],['ID','UF_CRM_1571224508']);
                    ($contactResult) ? $marketingContact = $contactResult[0]['UF_CRM_1571224508'] : $marketingContact = '';
                }

                $outletArr = [
                    'OUTLET_ADDRESS' => $outlet['NAME'],
                    'SERVICE_CONTACT'=> $serviceContact,
                    'LAWYER_CONTACT'=> $lawyerContact,
                    'ACCOUNTANT_CONTACT'=> $accountantContact,
                    'DECISION_MAKING_CONTACT'=> $decisionMakingContact,
                    'MARKETING_CONTACT'=> $marketingContact,
                    '1C_OUTLET_ID' => $outlet['PROPERTY_93_VALUE'],
                ];
                if($flag == 'all'){
                    $outletArr['CLIENT'] = self::returnCompanyOrContact1CiD($outlet['PROPERTY_92_VALUE']);
                    $outletArr['ID'] = $outlet['ID'];
                }

                $result[] = $outletArr;
            }
            return $result;
    }

    //договора - получение договоров по фильтру
    private function getContracts($filter, $flag = false){
        $result = [];
        $contracts = Bitrixfunctions::getListElementsByFilter(
            $filter,['ID','NAME','PROPERTY_89','PROPERTY_90','PROPERTY_91']);
        if($contracts)
            foreach ($contracts as $contract){

                $contractArr = [
                    'CONTRACT' => $contract['NAME'],
                    'DATE_FROM' => $contract['PROPERTY_90_VALUE'],
                    '1C_CONTRACT_ID' => $contract['PROPERTY_91_VALUE'],
                ];

                if($flag == 'all'){
                    $contractArr['CLIENT'] = self::returnCompanyOrContact1CiD($contract['PROPERTY_89_VALUE']);
                    $contractArr['ID'] = $contract['ID'];
                }

                $result[] = $contractArr;
            }

            return $result;
    }

    //реквизиты - получение всех реквизитов по фильтру
    private function getRequisites($filter){
        $result = [];

        $reqFilter = [
            "filter" => $filter,
            'select' => ['ID','NAME','RQ_NAME','RQ_INN','RQ_EMAIL','UF_CRM_1572097156'],
            'order' => ['ID' => 'DESC'],
        ];
        $mainRequisites = Bitrixfunctions::getRequisiteByFilter($reqFilter);
        if($mainRequisites){
            foreach ($mainRequisites as $requisite){

                //перевод даты из объекта в строку
//                $dateOfRegistr = Bitrixfunctions::convertDateObjToDate($requisite['UF_CRM_1572097156'],'d.m.Y');

                //банковские реквизиты
                $bankRequisites = Bitrixfunctions::getBankRequisitesByFilter(
                    ['ENTITY_ID' => $requisite['ID'],'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,]
                );

                //адреса
                $addresses = [];
                $addressesRequisites = Bitrixfunctions::getRequisiteAddressesByFilter(
                    ['ENTITY_ID' => $requisite['ID'],'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,]
                );
                if($addressesRequisites)
                    foreach ($addressesRequisites as $address) $addresses[] = $address;

                $result[] = [
                    'MAIN_REQUISITES' => $requisite,
                    'BANK' => $bankRequisites,
                    'ADDRESSES' => $addressesRequisites,
                ];
            }
        }

        return $result;
    }

    //получение массива значений польз. полей по VALUE_ENUM_ID
    public function getFieldValMassiveByValId($filter){
        $result = [];
        $valsArr = Bitrixfunctions::getUserFieldValueByValId($filter);
        ($valsArr) ? $result = ['VALUE_ID' => $valsArr[0]['ID'], 'NAME' => $valsArr[0]['VALUE']] : $result = [];
        return $result;
    }


}