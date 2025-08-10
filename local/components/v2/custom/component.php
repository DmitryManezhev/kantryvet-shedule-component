<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// Проверяем наличие модуля инфоблоков
if (!Loader::includeModule("iblock")) {
    ShowError(Loc::getMessage("DOCTOR_SCHEDULE_LIST_MODULE_NOT_INSTALLED"));
    return;
}

// Подключаем класс компонента
require_once(__DIR__ . '/class.php');

// Создаем экземпляр компонента
$component = new CDoctorScheduleListComponent();

// Устанавливаем параметры
$component->initComponent('kantreyvet:custom');
$component->arParams = $arParams;
$component->arResult = $arResult;
$component->arComponentDescription = $arComponentDescription ?? [];

// Выполняем компонент
$component->executeComponent();

// Возвращаем результат
$arResult = $component->arResult;
?>