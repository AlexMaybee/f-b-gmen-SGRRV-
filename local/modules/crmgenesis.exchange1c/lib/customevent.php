<?php

/*
 * @file for outgoing data functions
 * */

namespace Crmgenesis\Exchange1c;

class customevent{

    const REQUEST_URL = 'https://cp.crmgenesis.com/z_alex_requests_test.php';
    const PRODUCT_CATALOG_ID = 26;

    /*
     * @get Fields on Event
     * @get needed fields from Contact
     *
     * */

    public function workWithContact(&$arFields){

        $onSentArr = [];

        $bitrixfunctionsObj = new bitrixfunctions();

        if($bitrixfunctionsObj->is_1cUser() === false){

            $contactSelect = [


                'UF_CRM_1571224508', //ID в 1С
                'UF_CRM_1565362223845', //Направление

                'ID','NAME','LAST_NAME',
                'TYPE_ID','COMPANY_ID','COMMENTS',
                'SOURCE_ID','BIRTHDATE','ASSIGNED_BY_ID',
                'UF_CRM_1572857630', //Главный контрагент
                'UF_CRM_1572076993739', //Адрес доставки
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

                $onSentArr = [
                    '1C_CONTACT_ID' => $contactData[0]['UF_CRM_1571224508'], //ID 1C
                    'NAME' => HTMLToTxt($contactData[0]['NAME']),
                    'LAST_NAME' => HTMLToTxt($contactData[0]['LAST_NAME']),
                    'TYPE' => $contactData[0]['TYPE_ID'],
                    'COMMENTS' => $contactData[0]['COMMENTS'],
                    'ASSIGNED_BY' => $assignedName,
                    'SHIPPING_ADDRESS' => $contactData[0]['UF_CRM_1572076993739'], //Адрес Доставки
                    'REQUISITES' => [],
                    'COMMUNICATION' => [],
                    '1C_COMPANY_ID' => '',
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
                        $onSentArr['COMMUNICATION'][] = [
                                'TYPE_ID' => $comm['TYPE_ID'],
                                'TYPE' => $comm['VALUE_TYPE'],
                                'VALUE' => HTMLToTxt($comm['VALUE']),
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



                        
                        $onSentArr['REQUISITES'][] = [
                            'ID' => $requisite['ID'],
                            'TITLE' => $requisite['NAME'],
                            'FIO' => $requisite['RQ_NAME'],
                            'INN' => $requisite['RQ_INN'],
                            'VAT_CERT_NUM' => $requisite['RQ_VAT_CERT_NUM'],
                            'DOC_EMAIL' => $requisite['RQ_EMAIL'], //обмен доками
                            'REGISTER_DATE' => $dateOfRegistr, //дата регистрации
                            'BANK_REQUISITES' => $bankRequisites, //Банковские реквизиты, arr
                            'ADDRESSES' => [], //Банковские реквизиты, arr
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


            $companySelect = ['ID','TITLE',
               'CONTACT_ID','ASSIGNED_BY_ID',
                'COMMENTS','COMPANY_TYPE','INDUSTRY','COMPANY_TYPE',
                'UF_CRM_1571234538', //ID в 1С

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

                $onSentArr = [
                    '1C_COMPANY_ID' => $companyData[0]['UF_CRM_1571234538'], //ID 1C
                    'TITLE' => HTMLToTxt($companyData[0]['TITLE']),
                    'TYPE' => $companyData[0]['COMPANY_TYPE'], //Type
                    'INDUSTRY' => $companyData[0]['INDUSTRY'], //INDUSTRY
                    'TYPE' => $companyData[0]['COMPANY_TYPE'],
                    'COMMENTS' => $companyData[0]['COMMENTS'],
                    'ASSIGNED_BY' => $assignedName,
                    'REQUISITES' => [],
                    'CONTACTS' => [],
                    'COMMUNICATION' => [],
                ];


                $imFilter = [
                    'ENTITY_ID'  => 'COMPANY',
                    'ELEMENT_ID' => $companyData[0]['ID'],
                ];
                $imSelect = ['TYPE_ID','VALUE_TYPE','VALUE'];

                $communications = $bitrixfunctionsObj->getIMcontact($imFilter,$imSelect);
                if($communications){
                    foreach ($communications as $comm){
                        $onSentArr['COMMUNICATION'][] = [
                            'TYPE_ID' => $comm['TYPE_ID'],
                            'TYPE' => $comm['VALUE_TYPE'],
                            'VALUE' => HTMLToTxt($comm['VALUE']),
                        ];

//                        $onSentArr['COMMUNICATION'][] = $comm;
                    }
                }


                //получение всех контактов к сделке + их 1с_ID
                $contacts = $bitrixfunctionsObj->getContactData(
                    ['COMPANY_ID' => $companyData[0]['ID']],['ID','UF_CRM_1571224508']);
                if($contacts)
                    foreach ($contacts as $contact)
                        $onSentArr['CONTACTS'][]['1C_COMPANY_ID'] = $contact['UF_CRM_1571224508'];


                //получение рекизитов
                $reqFilter = [
                    "filter" => [
                        "ENTITY_ID" => $companyData[0]['ID'],
                        "ENTITY_TYPE_ID"=>\CCrmOwnerType::Company/*,'PRESET_ID'=>1*/
                    ],
                ];
                $mainRequisites = $bitrixfunctionsObj->getRequisiteByFilter($reqFilter);
                if($mainRequisites) {
                    foreach ($mainRequisites as $requisite)
                    $onSentArr['REQUISITES'][] = [
                        'TITLE' => $requisite['NAME'],
                        'FIO' => $requisite['RQ_NAME'],
                        'INN' => $requisite['RQ_INN'],
                        'VAT_CERT_NUM' => $requisite['RQ_VAT_CERT_NUM'],
                        'COMPANY_NAME' => $requisite['RQ_COMPANY_NAME'],
                        'DIRECTOR' => $requisite['RQ_DIRECTOR'],
                        'ACCOUNTANT' => $requisite['RQ_ACCOUNTANT'],
                        'EDRPOU' => $requisite['RQ_EDRPOU'],
                    ];
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
                'ID','TITLE','CATEGORY_ID','ASSIGNED_BY_ID',
                'COMPANY_ID','CONTACT_ID','STAGE_ID',
                'OPPORTUNITY','COMMENTS','CURRENCY_ID',
                'UF_CRM_1571137429', //ID в 1С
//                'UF_CRM_5D6538200FE39', //ID манагера по работе с клиентами
//                'UF_CRM_5D65381FE0D50', //Подразделение


                'UF_CRM_1571754349830', //Торговая точка (адрес)
                'UF_CRM_1571753979685', //Соглашение
                'CLOSEDATE', //Дата отгрузки
                'UF_CRM_1571754477845', //Группа контрагента
                'TYPE_ID', //Операция
                'UF_CRM_5D65381FF4038', //Категория
                'UF_CRM_1571754768355', //Договор
                'UF_CRM_1571755263449', //Склад
                'UF_CRM_1571755963331', //Способ доставки
                'UF_CRM_1571755984914', //Адрес доставки
                'UF_CRM_1571756036294', //Зона доставки
                'UF_CRM_1571756516', //Менеджер

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
                if($dealData[0]['UF_CRM_1571756516']){
                    $managerData = $bitrixfunctionsObj->getUserData($dealData[0]['UF_CRM_1571756516']);
                    ($managerData)
//                    ? $managerName = $managerData['LAST_NAME'].' '.$managerData['NAME']
                        ? $managerName = $managerData['EMAIL'] //Временно!!!
                        : $managerName = '';
                }

                $onSentArr = [
                    'TITLE' => HTMLToTxt($dealData[0]['TITLE']),
                    '1C_DEAL_ID' => $dealData[0]['UF_CRM_1571137429'], //ID 1C
                    'ASSIGNED_BY' => $assignedName,
                    'CATEGORY_ID' => $dealData[0]['CATEGORY_ID'],
                    'STAGE' => $dealData[0]['STAGE_ID'], //STAGE
                    'OPPORTUNITY' => $dealData[0]['OPPORTUNITY'], //DEAL_SUM
                    'CURRENCY_ID' => $dealData[0]['CURRENCY_ID'], //CURRENCY
                    'COMMENTS' => $dealData[0]['COMMENTS'],

                    'POINT_SALE' => $dealData[0]['UF_CRM_1571754349830'],
                    'AGREEMENT' => $dealData[0]['UF_CRM_1571753979685'],
                    'SHIPMENT_DATE' => $dealData[0]['CLOSEDATE'],
                    'CLIENT_CROUP' => $dealData[0]['UF_CRM_1571754477845'],
                    'OPERATION' => $dealData[0]['TYPE_ID'],
                    'CLIENT_CATEGORY' => $dealData[0]['UF_CRM_5D65381FF4038'],
                    'CONTRACT' => $dealData[0]['UF_CRM_1571754768355'],
                    'STORE' => $dealData[0]['UF_CRM_1571755263449'],
                    'DELIVERY_METHOD' => $dealData[0]['UF_CRM_1571755963331'],
                    'DELIVERY_ADDRESS' => $dealData[0]['UF_CRM_1571755984914'],
                    'DELIVERY_ZONE' => $dealData[0]['UF_CRM_1571756036294'],
                    'MANAGER' => $managerName, //RESP MANAGER For CLIEnT
                    'CONTACTS' => [],
                    'PRODUCTS' => [],
                    '1C_COMPANY_ID' => '',



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