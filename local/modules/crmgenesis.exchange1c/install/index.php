<?php

//подключение файла с какими-то данными модуля и проверкой на D7
include_once(dirname(__DIR__).'/lib/main.php');

//подключение файла с базовыми функциями
include_once(dirname(__DIR__).'/lib/bitrixfunctions.php');

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\ModuleManager;


//Это подключение файла с классом тек. модуля
use \Crmgenesis\Exchange1c\Main;

//подключение файла с базовыми функциями здесь, чтобы вызывать в нужном классе его функции
use \Crmgenesis\Exchange1c\bitrixfunctions;

//Lang-файлы
Loc::loadMessages(__FILE__);

class crmgenesis_exchange1c extends CModule{

    var $MODULE_ID = 'crmgenesis.exchange1c';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $strError = '';


    public function __construct(){
        $arModuleVersion = [];
        include(__DIR__."/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("CRM_GENESIS_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("CRM_GENESIS_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("CRM_GENESIS_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("CRM_GENESIS_PARTNER_URI");
    }

    public function InstallEvents(){
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmContactAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithContact');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmContactUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithContact');

        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmCompanyAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithCompany');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmCompanyUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithCompany');

        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmDealAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithDeal');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmDealUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithDeal');

        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmInvoiceAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithInvoice');
        EventManager::getInstance()->registerEventHandler('crm', 'OnAfterCrmInvoiceUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithInvoice');

        return true;
    }

    public function UnInstallEvents(){
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmContactAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithContact');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmContactUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithContact');

        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmCompanyAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithCompany');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmCompanyUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithCompany');

        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmDealAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithDeal');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmDealUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithDeal');

        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmInvoiceAdd', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithInvoice');
        EventManager::getInstance()->unRegisterEventHandler('crm', 'OnAfterCrmInvoiceUpdate', Main::MODULE_ID, '\Crmgenesis\Exchange1c\customevent', 'workWithInvoice');

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
            ModuleManager::registerModule(Main::MODULE_ID);
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage("CRM_GENESIS_INSTALL_ERROR_VERSION"));
        }
    }

    public function DoUninstall(){
        ModuleManager::unRegisterModule(Main::MODULE_ID);
        $this->UnInstallEvents();
        $this->UnInstallFiles();
    }

}