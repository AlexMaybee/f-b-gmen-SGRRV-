<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
require_once ($_SERVER['DOCUMENT_ROOT'].'/local/modules/crmgenesis.1exchange/classes/class.php');

$APPLICATION->SetTitle("Страница обмена 1с");
$obj = new Exchangefunctions;
$obj->test();
