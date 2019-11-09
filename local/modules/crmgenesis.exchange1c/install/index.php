<?php

//подключение файла с какими-то данными модуля и проверкой на D7
include_once(dirname(__DIR__).'/lib/main.php');

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\ModuleManager;


//Это подключение файла с классом тек. модуля
use \Crmgenesis\Exchange1c\Main;
use \Crmgenesis\Exchange1c\Customevent;

//Lang-файлы
Loc::loadMessages(__FILE__);

class crmgenesis_exchange1c extends CModule{

    public $MODULE_ID = 'crmgenesis.exchange1c';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct(){
        $arModuleVersion = [];
        include(__DIR__."/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("CRM_GENESIS_EXCHANGE_1C_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("CRM_GENESIS_EXCHANGE_1C_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("CRM_GENESIS_EXCHANGE_1C_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("CRM_GENESIS_EXCHANGE_1C_PARTNER_URI");
    }

    public function InstallEvents(){
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmContactAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithContact');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmContactUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithContact');

        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmCompanyAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithCompany');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmCompanyUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithCompany');

        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmDealAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithDeal');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithDeal');

        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmInvoiceAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithInvoice');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmInvoiceUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithInvoice');

        EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithLists');
        EventManager::getInstance()->registerEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithLists');

        return true;
    }

    public function UnInstallEvents(){
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmContactAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithContact');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmContactUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithContact');

        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmCompanyAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithCompany');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmCompanyUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithCompany');

        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmDealAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithDeal');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmDealUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithDeal');

        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmInvoiceAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithInvoice');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmInvoiceUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithInvoice');

        EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithLists');
        EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, 'Crmgenesis\Exchange1c\Customevent', 'workWithLists');

        return true;
    }

    public function InstallFiles($arParams = [])
    {
        CopyDirFiles(Main::GetPatch()."/install/exchange1c/", $_SERVER["DOCUMENT_ROOT"]."/exchange1c/", true, true);
        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/exchange1c/");

        return true;
    }

    public function DoInstall(){
        global $APPLICATION;
        if(Main::isVersionD7())
        {
            $this->InstallFiles();
            $this->InstallEvents();
            ModuleManager::registerModule($this->MODULE_ID);
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage("CRM_GENESIS_EXCHANGE_1C_INSTALL_ERROR_VERSION"));
        }
    }

    public function DoUninstall(){
        ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallEvents();
        $this->UnInstallFiles();
    }

}