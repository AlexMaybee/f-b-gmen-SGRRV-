<?php

class crmgenesis_1exchange extends CModule{
    var $MODULE_ID = "crmgenesis.1exchange";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME = 'CRM GENESIS';
    var $PARTNER_URI = 'https://crmgenesis.com/';

    public function crmgenesis_1exchange(){
        $arModuleVersion = [];
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->MODULE_NAME = 'Модуль обмена с 1с';
        $this->MODULE_DESCRIPTION = "При помощи данного модуля происходит обмен сделками, счетами, контактами и компаниями. 
        Обмен происходит при изменении сущности любым пользователем, кроме пользователя, который предназначен для обмена.";
    }

    public function InstallFiles(){

        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/local/modules/".$this->MODULE_ID."/install/1c_exchange/",
            $_SERVER["DOCUMENT_ROOT"]."/1c_exchange/", true, true);

        return true;
    }

    public function UnInstallFiles(){

        DeleteDirFilesEx("/1c_exchange/");

        return true;
    }

    public function DoInstall()
    {
        global $APPLICATION;
        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);

        //привязка js-файла при загрузке страницы
//        RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "CustomSettings", "addHideFunctionToPage");

    }

    public function DoUninstall()
    {
        global $APPLICATION;
        $this->UnInstallFiles();

        //отвязка функции от события создания компании
//        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "CustomSettings", "addHideFunctionToPage");

        UnRegisterModule($this->MODULE_ID);

    }

}