<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = array(
    "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_COMPONENT_NAME"),
    "DESCRIPTION" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_COMPONENT_DESCRIPTION"),
    "ICON" => "/images/calendar.gif",
    "CACHE_PATH" => "Y",
    "SORT" => 10,
    "PATH" => array(
        "ID" => "aspro_allcorp3medc",
        "CHILD" => array(
            "ID" => "staff",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_COMPONENT_GROUP_STAFF"),
            "SORT" => 10,
        )
    ),
);
?>