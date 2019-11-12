<?
define('BX_SESSION_ID_CHANGE', false);
define('BX_SKIP_POST_UNQUOTE', true);
define('NO_AGENT_CHECK', true);
define("STATISTIC_SKIP_ACTIVITY_CHECK", true);
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
Loader::includeSharewareModule("crmgenesis.exchange1c");
/** @global CMain $APPLICATION */
global $APPLICATION;


IncludeModuleLangFile(__FILE__);

$exch1cEnabled = COption::GetOptionString('crm', 'crm_exch1c_enable', 'N');
$exch1cEnabled = ($exch1cEnabled === 'Y');
if ($exch1cEnabled) {
    if ($license_name = COption::GetOptionString("main", "~controller_group_name")) {
        preg_match("/(project|tf)$/is", $license_name, $matches);
        if (strlen($matches[0]) > 0)
            $exch1cEnabled = false;
    }
}


$result = [
    'time' => date('d.m.Y H:i:s', strtotime('now')),
    'result' => false,
    'error' => false,
];

//if($data['ACTION'] === 'CHECK_CONTACT') $result['result'] = 'GOOOD MORNING, MY FRIEND!';
//else $result['error'] = 'BAD REQUEST!';
//echo json_encode($result);




$data = (array) json_decode(trim(file_get_contents("php://input")));
//$data = json_decode(json_encode($data), true);

if($data['ACTION']){

    switch ($data['ACTION']):

        //Прием контакта
        case 'CONTACT':
            $result = Crmgenesis\Exchange1c\Incomeb24::workWithIncomeContact($data);
            break;

        default:
            $result['error'] = 'Wrong ACTION!';
            break;
    endswitch;
}
else $result['error'] = 'BAD REQUEST!';



//print_r((new Crmgenesis\Exchange1c\incomeb24)->workWithIncomeContact(['test' => 'Test TExt NEW1444']));


echo json_encode($result);

//функция логирования из bitrixfunctions
//(new Crmgenesis\Exchange1c\bitrixfunctions)->logData(['YYYYY' => 'PPOOOOOOOHHHH']);

?>

<h1>Hello, this is the <span style="color:red;font-weight: bolder;">1cExchange</span>!!!</h1>;
