<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule("iblock"))
    return;

$arIBlockType = CIBlockParameters::GetIBlockTypes();

$arIBlock = array();
$rsIBlock = CIBlock::GetList(array("sort" => "asc"), array("ACTIVE" => "Y"));
while($arr = $rsIBlock->Fetch())
{
    $arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
}

// Получаем инфоблоки для региональности
$arRegionalityIBlock = array();
$rsRegionality = CIBlock::GetList(array("sort" => "asc"), array("ACTIVE" => "Y"));
while($arr = $rsRegionality->Fetch())
{
    if (stripos($arr["CODE"], "region") !== false || stripos($arr["NAME"], "регион") !== false || 
        stripos($arr["CODE"], "city") !== false || stripos($arr["NAME"], "город") !== false ||
        stripos($arr["CODE"], "contact") !== false || stripos($arr["NAME"], "контакт") !== false) {
        $arRegionalityIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
    }
}

$arComponentParameters = array(
    "GROUPS" => array(
        "IBLOCKS" => array(
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_IBLOCKS_SETTINGS"),
            "SORT" => 100,
        ),
        "DISPLAY" => array(
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_DISPLAY_SETTINGS"),
            "SORT" => 200,
        ),
        "AJAX" => array(
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_AJAX_SETTINGS"),
            "SORT" => 300,
        ),
        "CACHE" => array(
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_CACHE_SETTINGS"),
            "SORT" => 400,
        ),
    ),
    "PARAMETERS" => array(
        // Настройки инфоблоков
        "SERVICES_IBLOCK_ID" => array(
            "PARENT" => "IBLOCKS",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SERVICES_IBLOCK_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlock,
            "ADDITIONAL_VALUES" => "Y",
        ),
        "REGIONALITY_IBLOCK_ID" => array(
            "PARENT" => "IBLOCKS",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_REGIONALITY_IBLOCK_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arRegionalityIBlock,
            "ADDITIONAL_VALUES" => "Y",
        ),
        
        // Настройки отображения
        "ELEMENTS_COUNT" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_ELEMENTS_COUNT"),
            "TYPE" => "STRING",
            "DEFAULT" => "50",
        ),
        "SHOW_SEARCH" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SHOW_SEARCH"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SHOW_SPECIALIZATION_FILTER" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SHOW_SPECIALIZATION_FILTER"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SHOW_REGION_FILTER" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SHOW_REGION_FILTER"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SHOW_PHOTO" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SHOW_PHOTO"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SHOW_CONTACTS" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SHOW_CONTACTS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SHOW_SECTION_NAME" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SHOW_SECTION_NAME"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "SHOW_DETAIL_LINK" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_SHOW_DETAIL_LINK"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "HIDE_RECORD_BUTTON" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_HIDE_RECORD_BUTTON"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "DEFAULT_REGION_ID" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_DEFAULT_REGION_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ),
        
        // Настройки карты
        "TYPE_MAP" => array(
            "PARENT" => "DISPLAY",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_TYPE_MAP"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "YANDEX" => "Яндекс.Карты",
                "GOOGLE" => "Google Maps",
            ),
            "DEFAULT" => "YANDEX",
        ),
        
        // AJAX настройки
        "USE_AJAX" => array(
            "PARENT" => "AJAX",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_USE_AJAX"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
        "AJAX_MODE" => array(
            "PARENT" => "AJAX",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_AJAX_MODE"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        
        // Кеширование
        "CACHE_TYPE" => array(
            "PARENT" => "CACHE",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_CACHE_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "A" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_CACHE_TYPE_AUTO"),
                "Y" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_CACHE_TYPE_YES"),
                "N" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_CACHE_TYPE_NO"),
            ),
            "DEFAULT" => "A",
        ),
        "CACHE_TIME" => array(
            "PARENT" => "CACHE",
            "NAME" => Loc::getMessage("DOCTOR_SCHEDULE_LIST_CACHE_TIME"),
            "TYPE" => "STRING",
            "DEFAULT" => "3600",
        ),
    ),
);
?>