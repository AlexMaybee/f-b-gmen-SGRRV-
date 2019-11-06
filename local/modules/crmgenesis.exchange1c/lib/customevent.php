<?php

/*
 * @file for outgoing data functions
 * */

namespace Crmgenesis\Exchange1c;

class customevent{

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
        $bitrixfunctionsObj = new bitrixfunctions();
        $onSentArr = [];

        if($bitrixfunctionsObj->is_1cUser() === false){

            $contactSelect = [
                'UF_CRM_1571224508', //ID в 1С
                'UF_CRM_1565362223845', //Направление
                'UF_CRM_1572857630', //Главный контрагент
                'UF_CRM_1572076993739', //Адрес доставки
                'UF_CRM_1573040726', //Деятельность
                'ID','NAME','LAST_NAME',
                'TYPE_ID','COMPANY_ID','COMMENTS',
                'SOURCE_ID','BIRTHDATE','ASSIGNED_BY_ID',
            ];
            $contactData = $bitrixfunctionsObj->getContactData(['ID' => $arFields['ID']],$contactSelect);

            if($contactData){
                $assignedName = '';
                if($contactData[0]['ASSIGNED_BY_ID']){
                    $assignedByData = $bitrixfunctionsObj->getUserData($contactData[0]['ASSIGNED_BY_ID']);
                    ($assignedByData)
//                    ? $assignedName = $assignedByData['LAST_NAME'].' '.$assignedByData['NAME']
                        ? $assignedName = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedName = '';
                }

                //главный контрагент - компания либо контакт
                $mainContragent = '';
                if($contactData[0]['UF_CRM_1572857630']){
                    $contrArr = explode('_',$contactData[0]['UF_CRM_1572857630']);
                    //получаем 1C_ID контакта
                    if($contrArr[0] == 'C'){
                        $contactResult= $bitrixfunctionsObj->getContactData(['ID' => $contrArr[1]],['ID','UF_CRM_1571224508']);
                        ($contactResult) ? $mainContragent = $contactResult[0]['UF_CRM_1571224508'] : $mainContragent = '';
                    }
                    //получаем 1C_ID компании
                    if($contrArr[0] == 'CO'){
                        $companyResult = $bitrixfunctionsObj->getCompanyData(['ID' => $contrArr[1]],['ID','UF_CRM_1571234538']);
                        ($companyResult) ? $mainContragent = $companyResult[0]['UF_CRM_1571234538'] : $mainContragent = '';
                    }
                }

                $onSentArr = [
                    '1C_CONTACT_ID' => $contactData[0]['UF_CRM_1571224508'], //ID 1C
                    'NAME' => HTMLToTxt($contactData[0]['NAME']),
                    'LAST_NAME' => HTMLToTxt($contactData[0]['LAST_NAME']),
                    'TYPE' => $contactData[0]['TYPE_ID'],
                    '1C_COMPANY_ID' => '',
                    '1C_MAIN_CONTRAGENT_ID' => $mainContragent,
                    'SOURCE_ID' => $contactData[0]['SOURCE_ID'],
                    'ASSIGNED_BY' => $assignedName, //Потом Переделать на Ф + И
                    'COMMENTS' => $contactData[0]['COMMENTS'],
                    'SHIPPING_ADDRESS' => $contactData[0]['UF_CRM_1572076993739'], //Адрес Доставки
                    'ACTIVITY' => $contactData[0]['UF_CRM_1573040726'], //Деятельность (размер)
                    'OUTLETS' => [],
                    'CONTRACTS' => [],
                    'COMMUNICATION' => [],
                    'REQUISITES' => [],
                ];

                //тел, почта и мессенджеры
                $imFilter = [
                    'ENTITY_ID'  => 'CONTACT',
                    'ELEMENT_ID' => $contactData[0]['ID'],
                ];
                $imSelect = ['TYPE_ID','VALUE_TYPE','VALUE'];
                $communications = $bitrixfunctionsObj->getIMcontact($imFilter,$imSelect);
                if($communications){
                    foreach ($communications as $comm){
                        if($comm['TYPE_ID'] == 'EMAIL') {
                            $email = trim(substr(HTMLToTxt($comm['VALUE']),0,strpos(HTMLToTxt($comm['VALUE']),'[')));
                        }
                        else $email = HTMLToTxt($comm['VALUE']);

                        $onSentArr['COMMUNICATION'][] = [
                                'TYPE_ID' => $comm['TYPE_ID'],
                                'TYPE' => $comm['VALUE_TYPE'],
//                                'VALUE' => HTMLToTxt($comm['VALUE']),
                                'VALUE' => $email,
                            ];
                    }
                }

                //если есть ID компании, то получаем и ее ID в 1с
                if($contactData[0]['COMPANY_ID'] > 0){
                    $companyResult = $bitrixfunctionsObj->getCompanyData(
                        ['ID' => $contactData[0]['COMPANY_ID']],
                        ['ID','UF_CRM_1571234538']
                    );
                    if($companyResult)
                        $onSentArr['1C_COMPANY_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                }

                //торговые точки
                $outlets = $bitrixfunctionsObj->getListElementsByFilter(
                    ['IBLOCK_ID' => self::IBLOCK_32,'PROPERTY_92' => 'C_'.$contactData[0]['ID']],
                    ['ID','NAME','PROPERTY_92','PROPERTY_93']
                );
                if($outlets)
                    foreach ($outlets as $outlet)
                        $onSentArr['OUTLETS'][] = [
                            'OUTLET_ADDRESS' => $outlet['NAME'],
                            '1C_ID' => $outlet['PROPERTY_93_VALUE'],
                        ];

                //договора
                $contracts = $bitrixfunctionsObj->getListElementsByFilter(
                    ['IBLOCK_ID' => self::IBLOCK_31,'PROPERTY_89' => 'C_'.$contactData[0]['ID']],
                    ['ID','NAME','PROPERTY_89','PROPERTY_90','PROPERTY_91']
                );
                if($contracts)
                    foreach ($contracts as $contract)
                        $onSentArr['CONTRACTS'][] = [
                            'CONTRACT' => $contract['NAME'],
                            'DATE_FROM' => $contract['PROPERTY_90_VALUE'],
                            '1C_ID' => $contract['PROPERTY_91_VALUE'],
                        ];

                //реквизиты
                $reqFilter = [
                    "filter" => [
                        "ENTITY_ID" => $contactData[0]['ID'],
                        "ENTITY_TYPE_ID" => \CCrmOwnerType::Contact/*,'PRESET_ID'=>1*/
                    ],
                    'select' => [
                        'ID','NAME','RQ_NAME','RQ_INN','RQ_EMAIL','UF_CRM_1572097156',
                    ],
                    'order' => ['ID' => 'DESC'],
                ];
                $mainRequisites = $bitrixfunctionsObj->getRequisiteByFilter($reqFilter);
                if($mainRequisites){
                    foreach ($mainRequisites as $requisite){

                        //перевод даты из объекта в строку
                        $dateOfRegistr = $bitrixfunctionsObj->convertDateObjToDate($requisite['UF_CRM_1572097156'],'d.m.Y');

                        //банковские реквизиты
                        $bankRequisites = $bitrixfunctionsObj->getBankRequisitesByFilter(
                            ['ENTITY_ID' => $requisite['ID'],'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,]
                        );

                        //адреса
                        $addresses = [];
                        $addressesRequisites = $bitrixfunctionsObj->getRequisiteAddressesByFilter(
                            ['ENTITY_ID' => $requisite['ID'],'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,]
                        );
                        if($addressesRequisites){
                            foreach ($addressesRequisites as $address){
//                                if($address['TYPE_ID'] == 6) $address['TYPE_ID'] = 'CORP_ADDRESS';
//                                if($address['TYPE_ID'] == 1) $address['TYPE_ID'] = 'PRYS_ADDRESS';
                                $addresses[] = $address;
                            }
                        }

                        
                        $onSentArr['REQUISITES'][] = [
                            'ID' => $requisite['ID'],
                            'TITLE' => $requisite['NAME'],
                            'FIO' => $requisite['RQ_NAME'],
                            'INN' => $requisite['RQ_INN'],
                            'VAT_CERT_NUM' => $requisite['RQ_VAT_CERT_NUM'],
                            'DOC_EMAIL' => $requisite['RQ_EMAIL'], //обмен доками
                            'REGISTER_DATE' => $dateOfRegistr, //дата регистрации
                            'BANK' => $bankRequisites, //Банковские реквизиты, arr
                            'ADDRESSES' => $addresses, //Адреса, arr
                        ];


//                        $onSentArr['REQUISITES'][] = $requisite;
                    }
                }

                //!!! Еще нужно физ адрес и юр. адрес, + банк


//                $sentDataRes = $bitrixfunctionsObj->makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_CONTACT',
//                        'DATA' => $onSentArr,
//                    ]);

                //здесь!
                //Если контакт новый, то из 1С должна вернуться ID
                if(!$contactData[0]['UF_CRM_1571224508']/* && $sentDataRes['1C_ID']*/){ //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571224508'] = '2222222333322222';
                    $updContactRes = $bitrixfunctionsObj->updateContact($contactData[0]['ID'],$updFields);
                }

            }

        }
//        else $arFields['1C_USER'] = 'Current user IS A 1C user!';

        $bitrixfunctionsObj->logData($onSentArr);
    }


    /*
      * @get Fields on Event
      * @get needed fields from Company
      *
      * */
    public function workWithCompany(&$arFields){

        $onSentArr = [];
        $bitrixfunctionsObj = new bitrixfunctions();

        if($bitrixfunctionsObj->is_1cUser() === false){


            $companySelect = [
                'UF_CRM_1571234538', //ID в 1С
                'UF_CRM_1572858286', //Тип клиента
                'UF_CRM_1572857700', //Главный контрагент
                'UF_CRM_1572858629', //Источник первичного интереса
                'UF_CRM_1572861522', //Адрес доставки
                'ID','TITLE',
                'CONTACT_ID','ASSIGNED_BY_ID',
                'COMMENTS','COMPANY_TYPE'
            ];
            $companyData = $bitrixfunctionsObj->getCompanyData(['ID' => $arFields['ID']],$companySelect);

            if($companyData){

                $assignedName = '';
                if($companyData[0]['ASSIGNED_BY_ID']){
                    $assignedByData = $bitrixfunctionsObj->getUserData($companyData[0]['ASSIGNED_BY_ID']);
                    ($assignedByData)
//                    ? $assignedName = $assignedByData['LAST_NAME'].' '.$assignedByData['NAME']
                        ? $assignedName = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedName = '';
                }

                //главный контрагент - компания либо контакт
                $mainContragent = '';
                if($companyData[0]['UF_CRM_1572857700']){
                    $contrArr = explode('_',$companyData[0]['UF_CRM_1572857700']);
                    //получаем 1C_ID контакта
                    if($contrArr[0] == 'C'){
                        $contactResult= $bitrixfunctionsObj->getContactData(['ID' => $contrArr[1]],['ID','UF_CRM_1571224508']);
                        ($contactResult) ? $mainContragent = $contactResult[0]['UF_CRM_1571224508'] : $mainContragent = '';
                    }
                    //получаем 1C_ID компании
                    if($contrArr[0] == 'CO'){
                        $companyResult = $bitrixfunctionsObj->getCompanyData(['ID' => $contrArr[1]],['ID','UF_CRM_1571234538']);
                        ($companyResult) ? $mainContragent = $companyResult[0]['UF_CRM_1571234538'] : $mainContragent = '';
                    }
                }

                $onSentArr = [
                    '1C_COMPANY_ID' => $companyData[0]['UF_CRM_1571234538'], //ID 1C
                    'TITLE' => HTMLToTxt($companyData[0]['TITLE']),
                    'TYPE' => $companyData[0]['UF_CRM_1572858286'],
                    '1C_MAIN_CONTRAGENT_ID' => $mainContragent,
                    'SOURCE_ID' => $companyData[0]['UF_CRM_1572858629'],
                    'SHIPPING_ADDRESS' => $companyData[0]['UF_CRM_1572861522'], //Адрес Доставки
                    'COMMENTS' => $companyData[0]['COMMENTS'],
                    'ASSIGNED_BY' => $assignedName,
                    'ACTIVITY' => $companyData[0]['COMPANY_TYPE'], //Деятельность (размер)
                    'OUTLETS' => [],
                    'CONTRACTS' => [],
                    'COMMUNICATION' => [],
                    'REQUISITES' => [],
                ];


                $imFilter = [
                    'ENTITY_ID'  => 'COMPANY',
                    'ELEMENT_ID' => $companyData[0]['ID'],
                ];
                $imSelect = ['TYPE_ID','VALUE_TYPE','VALUE'];

                $communications = $bitrixfunctionsObj->getIMcontact($imFilter,$imSelect);
                if($communications){
                    foreach ($communications as $comm){
                        if($comm['TYPE_ID'] == 'EMAIL') {
                            $email = trim(substr(HTMLToTxt($comm['VALUE']),0,strpos(HTMLToTxt($comm['VALUE']),'[')));
                        }
                        else $email = HTMLToTxt($comm['VALUE']);

                        $onSentArr['COMMUNICATION'][] = [
                            'TYPE_ID' => $comm['TYPE_ID'],
                            'TYPE' => $comm['VALUE_TYPE'],
//                                'VALUE' => HTMLToTxt($comm['VALUE']),
                            'VALUE' => $email,
                        ];
                    }
                }


                //получение всех контактов к сделке + их 1с_ID
                $contacts = $bitrixfunctionsObj->getContactData(
                    ['COMPANY_ID' => $companyData[0]['ID']],['ID','UF_CRM_1571224508']);
                if($contacts)
                    foreach ($contacts as $contact)
                        $onSentArr['CONTACTS'][]['1C_COMPANY_ID'] = $contact['UF_CRM_1571224508'];

                //торговые точки
                $outlets = $bitrixfunctionsObj->getListElementsByFilter(
                    ['IBLOCK_ID' => self::IBLOCK_32,'PROPERTY_92' => 'CO_'.$companyData[0]['ID']],
                    ['ID','NAME','PROPERTY_92','PROPERTY_93']
                );
                if($outlets)
                    foreach ($outlets as $outlet)
                        $onSentArr['OUTLETS'][] = [
                            'OUTLET_ADDRESS' => $outlet['NAME'],
                            '1C_ID' => $outlet['PROPERTY_93_VALUE'],
                        ];

                //договора
                $contracts = $bitrixfunctionsObj->getListElementsByFilter(
                    ['IBLOCK_ID' => self::IBLOCK_31,'PROPERTY_89' => 'CO_'.$companyData[0]['ID']],
                    ['ID','NAME','PROPERTY_89','PROPERTY_90','PROPERTY_91']
                );
                if($contracts)
                    foreach ($contracts as $contract)
                        $onSentArr['CONTRACTS'][] = [
                            'CONTRACT' => $contract['NAME'],
                            'DATE_FROM' => $contract['PROPERTY_90_VALUE'],
                            '1C_ID' => $contract['PROPERTY_91_VALUE'],
                        ];

                //получение рекизитов
                $reqFilter = [
                    "filter" => [
                        "ENTITY_ID" => $companyData[0]['ID'],
                        "ENTITY_TYPE_ID"=>\CCrmOwnerType::Company/*,'PRESET_ID'=>1*/
                    ],
                    'select' => [
                        '*','UF_*',
                    ],
                    'order' => ['ID' => 'DESC'],
                ];
                $mainRequisites = $bitrixfunctionsObj->getRequisiteByFilter($reqFilter);
                if($mainRequisites) {
                    foreach ($mainRequisites as $requisite){

                        //перевод даты из объекта в строку
                        $dateOfRegistr = $bitrixfunctionsObj->convertDateObjToDate($requisite['UF_CRM_1572097156'],'d.m.Y');

                        //банковские реквизиты
                        $bankRequisites = $bitrixfunctionsObj->getBankRequisitesByFilter(
                            ['ENTITY_ID' => $requisite['ID'],'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite]
                        );

                        //адреса
                        $addressesRequisites = $bitrixfunctionsObj->getRequisiteAddressesByFilter(
                            ['ENTITY_ID' => $requisite['ID'],'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite]
                        );

                        $onSentArr['REQUISITES'][] = [
                            'TITLE' => $requisite['NAME'],
                            'FIO' => $requisite['RQ_NAME'],
                            'INN' => $requisite['RQ_INN'],
                            'VAT_CERT_NUM' => $requisite['RQ_VAT_CERT_NUM'],
                            'COMPANY_NAME' => $requisite['RQ_COMPANY_NAME'],
                            'DIRECTOR' => $requisite['RQ_DIRECTOR'],
                            'ACCOUNTANT' => $requisite['RQ_ACCOUNTANT'],
                            'EDRPOU' => $requisite['RQ_EDRPOU'],
                            'DOC_EMAIL' => $requisite['RQ_EMAIL'], //обмен доками
                            'REGISTER_DATE' => $dateOfRegistr, //дата регистрации
                            'BANK' => $bankRequisites, //Банковские реквизиты, arr
                            'ADDRESSES' => $addressesRequisites, //Адреса, arr
                        ];
                    }

                }


//                $sentDataRes = $bitrixfunctionsObj->makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_COMPANY',
//                        'DATA' => $onSentArr,
//                    ]);

                //здесь!
                //Если контакт новый, то из 1С должна вернуться ID
                if(!$companyData[0]['UF_CRM_1571234538']/* && $sentDataRes['1C_ID']*/){ //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571234538'] = 'aueTEST123';
                    $updCompanyRes = $bitrixfunctionsObj->updateCompany($companyData[0]['ID'],$updFields);
                }

            }

        }
//        $onSentArr['arFields'] = $arFields;

        $bitrixfunctionsObj->logData($onSentArr);
    }


    /*
    * @get Fields on Event
    * @get needed fields from Deals
    *
    * */
    public function workWithDeal(&$arFields){
        $onSentArr = [];
        $bitrixfunctionsObj = new bitrixfunctions();

        if($bitrixfunctionsObj->is_1cUser() === false){
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
            $dealData = $bitrixfunctionsObj->getDealData(['ID' => $arFields['ID']],$dealSelect);
            if($dealData) {

                $assignedName = '';
                if($dealData[0]['ASSIGNED_BY_ID']){
                    $assignedByData = $bitrixfunctionsObj->getUserData($dealData[0]['ASSIGNED_BY_ID']);
                    ($assignedByData)
//                    ? $assignedName = $assignedByData['LAST_NAME'].' '.$assignedByData['NAME']
                        ? $assignedName = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedName = '';
                }


                $managerName = '';
                if($dealData[0]['UF_CRM_1573047946']){
                    $managerData = $bitrixfunctionsObj->getUserData($dealData[0]['UF_CRM_1573047946']);
                    ($managerData)
//                    ? $managerName = $managerData['LAST_NAME'].' '.$managerData['NAME']
                        ? $managerName = $managerData['EMAIL'] //Временно!!!
                        : $managerName = '';
                }


                $onSentArr = [
                    'TITLE' => HTMLToTxt($dealData[0]['TITLE']),
                    '1C_DEAL_ID' => $dealData[0]['UF_CRM_1571137429'], //ID 1C

                    //получить текст каждой выбранной точки
//                          'POINT_SALE' => $dealData[0]['UF_CRM_1572869181'],

                    //Список договоров из list
//                         'CONTRACT' => $dealData[0]['UF_CRM_1572869538'],

                    'SHIPMENT_DATE' => $dealData[0]['CLOSEDATE'],
                    'SHIPMENT_METHOD' => $dealData[0]['UF_CRM_1572870720'],
                    'SHIPMENT_ZONE' => $dealData[0]['UF_CRM_1573033748'],
                    'STORE' => $dealData[0]['UF_CRM_1573043562'],
                    'OPPORTUNITY' => $dealData[0]['OPPORTUNITY'], //DEAL_SUM
                    'CURRENCY_ID' => $dealData[0]['CURRENCY_ID'], //CURRENCY
                    'COMMENTS' => $dealData[0]['COMMENTS'],

                    'ASSIGNED_BY' => $assignedName,

                    'AGREEMENT' => $dealData[0]['UF_CRM_1573032260'],
                    'CLIENT_CROUP' => $dealData[0]['UF_CRM_1573032335'],
                    'SUB_DIVISION' => $dealData[0]['UF_CRM_1573034084'],
                    'OPERATION' => $dealData[0]['TYPE_ID'],

                    'CATEGORY_ID' => $dealData[0]['CATEGORY_ID'],
                    'STAGE' => $dealData[0]['STAGE_ID'], //STAGE

                    'MANAGER' => $managerName,

                    '1C_COMPANY_ID' => '',
                    'CONTACTS' => [],
                    'PRODUCTS' => [],
                    'OUTLETS' => [],
                    'CONTRACT' => [],
                ];

                //торговые точки, массив
                $outlets = $bitrixfunctionsObj->getListElementsByFilter(
                    ['IBLOCK_ID' => self::IBLOCK_32,'ID' => $dealData[0]['UF_CRM_1572869181']],
                    ['ID','NAME','PROPERTY_92','PROPERTY_93']
                );
                if($outlets)
                    foreach ($outlets as $outlet)
                        $onSentArr['OUTLETS'][] = [
                            'OUTLET_ADDRESS' => $outlet['NAME'],
                            '1C_ID' => $outlet['PROPERTY_93_VALUE'],
                        ];

                //договора
                $contracts = $bitrixfunctionsObj->getListElementsByFilter(
                    ['IBLOCK_ID' => self::IBLOCK_31,'ID' => $dealData[0]['UF_CRM_1572869538']],
                    ['ID','NAME','PROPERTY_89','PROPERTY_90','PROPERTY_91']
                );
                if($contracts)
                    foreach ($contracts as $contract)
                        $onSentArr['CONTRACT'] = [
                            'CONTRACT' => $contract['NAME'],
                            'DATE_FROM' => $contract['PROPERTY_90_VALUE'],
                            '1C_ID' => $contract['PROPERTY_91_VALUE'],
                        ];



                //Получаем товарі сделки + их код из 1С
                $dealProducts = $bitrixfunctionsObj->getDealProducts($dealData[0]['ID']);
                if($dealProducts){
                    foreach ($dealProducts as $product){

                        //PROPERTY_88 - ID товара в 1С
                        $productProp = $bitrixfunctionsObj->getListElementsByFilter(
                            ['ID' => $product['PRODUCT_ID'],'IBLOCK_ID' => self::PRODUCT_CATALOG_ID],
                            ['ID','NAME','PROPERTY_88']
                        );

                        if($productProp)
                            $onSentArr['PRODUCTS'][] = [
                                'NAME' => $productProp[0]['NAME'],
                                '1C_PRODUCT_ID' => $productProp[0]['PROPERTY_88_VALUE'],
                            ];
                    }
                }

                //Все контакты и их ID в 1С
                $contactsIds = $bitrixfunctionsObj->getAllDealContacts($dealData[0]['ID']);
                if($contactsIds){
//                    $dealData[0]['CONTACTS'] = [];
                    $contacts = $bitrixfunctionsObj->getContactData(
                        ['ID' => $contactsIds],['ID','UF_CRM_1571224508']);
                    if($contacts){
                        foreach ($contacts as $contact)
                            $onSentArr['CONTACTS'][]['1C_CONTACT_ID'] = $contact['UF_CRM_1571224508'];
                    }
                }

                //Компания и ее ID в 1С
                if($dealData[0]['COMPANY_ID'] > 0){
                    $companyResult = $bitrixfunctionsObj->getCompanyData(
                        ['ID' => $dealData[0]['COMPANY_ID']],
                        ['ID','UF_CRM_1571234538']
                    );
                    if($companyResult) $onSentArr['1C_COMPANY_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                }


                //                $sentDataRes = $bitrixfunctionsObj->makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_DEAL',
//                        'DATA' => $onSentArr,
//                    ]);

                //здесь!
                //Если сделка новая то из 1С должна вернуться ID
                if(!$dealData[0]['UF_CRM_1571137429']/* && $sentDataRes['1C_ID']*/){ //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571137429'] = 'dealID1Ctest';
                    $updDealRes = $bitrixfunctionsObj->updateDeal($dealData[0]['ID'],$updFields);
                }

            }
        }

        $bitrixfunctionsObj->logData($onSentArr);
    }


    /*
      * @get Fields on Event
      * @get needed fields from Invoice
      *
      * */
    public function workWithInvoice(&$arFields)
    {

        $onSentArr = [];
        $bitrixfunctionsObj = new bitrixfunctions();

        if ($bitrixfunctionsObj->is_1cUser() === false) {

            $invoiceSelect = [
                'ID','ORDER_TOPIC','ACCOUNT_NUMBER','STATUS_ID','PAY_VOUCHER_DATE',
                'DATE_BILL','DATE_PAY_BEFORE','RESPONSIBLE_ID',
                'CURRENCY','PRICE',
                'UF_COMPANY_ID','UF_CONTACT_ID','UF_DEAL_ID','UF_QUOTE_ID',
                'USER_DESCRIPTION',
                'PRODUCT_ROWS',
                'INVOICE_PROPERTIES', //ID манагера по работе с клиентами
                'UF_CRM_1571761946', //ID в 1С

            ];
            $invoiceData = $bitrixfunctionsObj->getInvoiceData(['ID' => $arFields['ID']],$invoiceSelect);
            if($invoiceData) {

                $assignedName = '';
                if($invoiceData[0]['RESPONSIBLE_ID']){
                    $assignedByData = $bitrixfunctionsObj->getUserData($invoiceData[0]['RESPONSIBLE_ID']);
                    ($assignedByData)
//                    ? $assignedName = $assignedByData['LAST_NAME'].' '.$assignedByData['NAME']
                        ? $assignedName = $assignedByData['EMAIL'] //Временно!!!
                        : $assignedName = '';
                }

                $onSentArr = [
                    '1C_INVOICE_ID' => $invoiceData[0]['UF_CRM_1571761946'],
                    'TITLE' => $invoiceData[0]['ORDER_TOPIC'],
                    'NUMBER' => $invoiceData[0]['ACCOUNT_NUMBER'],
                    'SUM' => $invoiceData[0]['PRICE'],
                    'CURRENCY' => $invoiceData[0]['CURRENCY'],
                    'ASSIGNED_BY' => $assignedName,
                    '1C_CONTACT_ID' => '',
                    '1C_COMPANY_ID' => '',
                    '1C_DEAL_ID' => '',
                    '1C_QUOTE_ID' => '',
                    'PRODUCTS' => [],
                ];

                //получение товаров

                //получение 1C_ID сделки, компании и контакта, ну и предложения

                if($invoiceData[0]['UF_CONTACT_ID'] > 0){
                    $contactData = $bitrixfunctionsObj->getContactData(
                        ['ID' => $invoiceData[0]['UF_CONTACT_ID']],['ID','UF_CRM_1571224508']);
                    if($contactData)
                        $onSentArr['1C_CONTACT_ID'] = $contactData[0]['UF_CRM_1571224508'];
                }

                if($invoiceData[0]['UF_COMPANY_ID'] > 0){
                    $companyResult = $bitrixfunctionsObj->getCompanyData(
                        ['ID' => $invoiceData[0]['UF_COMPANY_ID']],
                        ['ID','UF_CRM_1571234538']
                    );
                    if($companyResult) $onSentArr['1C_COMPANY_ID'] = $companyResult[0]['UF_CRM_1571234538'];
                }

                if($invoiceData[0]['UF_DEAL_ID'] > 0){
                    $dealResult = $bitrixfunctionsObj->getDealData(
                        ['ID' => $invoiceData[0]['UF_DEAL_ID']],
                        ['ID','UF_CRM_1571137429']
                    );
                    if($dealResult) $onSentArr['1C_DEAL_ID'] = $dealResult[0]['UF_CRM_1571137429'];
                }

                //qoute SAME!!!
                if($invoiceData[0]['UF_QUOTE_ID'] > 0){
                    $quoteResult = $bitrixfunctionsObj->getQuoteData(
                        ['ID' => $invoiceData[0]['UF_QUOTE_ID']],
                        ['ID','UF_CRM_1571762030']
                    );
                    if($quoteResult) $onSentArr['1C_QUOTE_ID'] = $quoteResult[0]['UF_CRM_1571762030'];
                }


                //products
                $dealProducts = $bitrixfunctionsObj->getInvoiceProducts($invoiceData[0]['ID']);
                if($dealProducts){
                    foreach ($dealProducts as $product){

                        //PROPERTY_88 - ID товара в 1С
                        $productProp = $bitrixfunctionsObj->getListElementsByFilter(
                            ['ID' => $product['PRODUCT_ID'],'IBLOCK_ID' => self::PRODUCT_CATALOG_ID],
                            ['ID','NAME','PROPERTY_88']
                        );

                        if($productProp)
                            $onSentArr['PRODUCTS'][] = [
                                'NAME' => $productProp[0]['NAME'],
                                '1C_PRODUCT_ID' => $productProp[0]['PROPERTY_88_VALUE'],
                            ];
                    }
                }


                //                $sentDataRes = $bitrixfunctionsObj->makePostRequest(self::REQUEST_URL,
//                    [
//                        'ACTION' => 'CHECK_INVOICE',
//                        'DATA' => $onSentArr,
//                    ]);


                //Если сделка новая то из 1С должна вернуться ID
                if(!$invoiceData[0]['UF_CRM_1571761946']/* && $sentDataRes['1C_ID']*/) { //!!!и результат в $sentDataRes true
//                    $updFields['UF_CRM_1571224508'] = $sentDataRes['1C_ID'];
                    $updFields['UF_CRM_1571761946'] = 'invoiceID1Ctest';
                     $updInvoicetRes = $bitrixfunctionsObj->updatInvoice($invoiceData[0]['ID'],$updFields);
                }

            }

        }
        $bitrixfunctionsObj->logData($onSentArr);
    }

}