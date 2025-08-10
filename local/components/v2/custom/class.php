<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CDoctorScheduleListComponent extends CBitrixComponent
{
    protected $moduleId         = 'iblock';
    protected $doctorsIblockId  = 125;   // инфоблок врачей
    protected $servicesIblockId = 126;   // инфоблок услуг
    protected $shopsIblockId    = 0;     // инфоблок филиалов (будет определяться автоматически)

    /* ---------- проверки ---------- */
    protected function checkModules()
    {
        if (!Loader::includeModule($this->moduleId)) {
            $this->abortResultCache();
            ShowError(Loc::getMessage('DOCTOR_SCHEDULE_LIST_MODULE_NOT_INSTALLED'));
            return false;
        }
        return true;
    }

    protected function checkParams()
    {
        $this->servicesIblockId = (int)($this->arParams['SERVICES_IBLOCK_ID'] ?: 126);
        $this->shopsIblockId    = (int)($this->arParams['SHOPS_IBLOCK_ID'] ?: 0);

        // Если инфоблок филиалов не задан, пытаемся найти автоматически
        if ($this->shopsIblockId <= 0) {
            $this->shopsIblockId = $this->findShopsIblock();
        }
        
        return true;
    }

    protected function findShopsIblock()
    {
        // Пытаемся найти инфоблок филиалов по коду или названию
        $rs = CIBlock::GetList(
            [],
            [
                'ACTIVE' => 'Y',
                'SITE_ID' => SITE_ID
            ]
        );
        
        while ($arIBlock = $rs->Fetch()) {
            $code = strtolower($arIBlock['CODE']);
            $name = strtolower($arIBlock['NAME']);
            
            if (strpos($code, 'shop') !== false || 
                strpos($code, 'office') !== false ||
                strpos($code, 'clinic') !== false ||
                strpos($name, 'филиал') !== false ||
                strpos($name, 'офис') !== false ||
strpos($name, 'Контакты') !== false ||
strpos($name, 'kantryvet_kantryvetmed_contact') !== false ||
                strpos($name, 'клиник') !== false) {
                return $arIBlock['ID'];
            }
        }
        
        return 0;
    }

    public function onPrepareComponentParams($params)
    {
        $params['SERVICES_IBLOCK_ID'] = (int)($params['SERVICES_IBLOCK_ID'] ?? 126);
        $params['SHOPS_IBLOCK_ID']    = (int)($params['SHOPS_IBLOCK_ID']    ?? 0);
        $params['ELEMENTS_COUNT']     = max(1, (int)($params['ELEMENTS_COUNT'] ?? 50));
        $params['CACHE_TYPE']         = $params['CACHE_TYPE'] ?? 'A';
        $params['CACHE_TIME']         = max(1, (int)($params['CACHE_TIME']  ?? 3600));
        $params['SHOW_SEARCH']        = $params['SHOW_SEARCH'] ?? 'Y';
        $params['SHOW_SERVICES_FILTER'] = $params['SHOW_SERVICES_FILTER'] ?? 'Y';
        $params['SHOW_SHOPS_FILTER']    = $params['SHOW_SHOPS_FILTER']    ?? 'Y';
        $params['SHOW_PHOTO']         = $params['SHOW_PHOTO']  ?? 'Y';
        $params['SHOW_CONTACTS']      = $params['SHOW_CONTACTS'] ?? 'Y';
        $params['SHOW_SECTION_NAME']  = $params['SHOW_SECTION_NAME'] ?? 'Y';
        $params['HIDE_RECORD_BUTTON'] = $params['HIDE_RECORD_BUTTON'] ?? 'N';
        $params['SHOW_DETAIL_LINK']   = $params['SHOW_DETAIL_LINK'] ?? 'Y';
        $params['DEFAULT_SHOP_ID']    = (int)($params['DEFAULT_SHOP_ID'] ?? 0);
        $params['USE_AJAX']           = $params['USE_AJAX'] ?? 'Y';
        $params['AJAX_MODE']          = $params['AJAX_MODE'] ?? 'N';
        $params['TYPE_MAP']           = $params['TYPE_MAP'] ?? 'YANDEX';
        return $params;
    }

    /* ---------- врачи ---------- */
    protected function getDoctors()
    {
        $filter = [
            'IBLOCK_ID' => $this->doctorsIblockId,
            'ACTIVE'    => 'Y',
        ];
        
        if ($this->arParams['DEFAULT_SHOP_ID'] > 0) {
            $filter['PROPERTY_SHOP'] = $this->arParams['DEFAULT_SHOP_ID'];
        }

        $select = [
            'ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 
            'DETAIL_PAGE_URL', 'IBLOCK_SECTION_ID', 'PROPERTY_POST', 'PROPERTY_LINK_SERVICES', 
            'PROPERTY_EMAIL', 'PROPERTY_PHONE', 'PROPERTY_SOCIAL_*', 'PROPERTY_QUALIFICATION', 
            'PROPERTY_EDUCATION', 'PROPERTY_ACTIVITY', 'PROPERTY_AWARDS', 'PROPERTY_WORK', 
            'PROPERTY_SIMPTOMY', 'PROPERTY_STATUS', 'PROPERTY_FORM_QUESTION', 'PROPERTY_FORM_ORDER',
            'PROPERTY_SHOP'
        ];

        // Сначала получаем уникальных врачей
        $rsElements = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            $filter,
            false,
            ['nTopCount' => $this->arParams['ELEMENTS_COUNT']],
            ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 
             'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'PROPERTY_POST']
        );

        $doctors = [];
        while ($arElement = $rsElements->GetNext()) {
            $doctors[$arElement['ID']] = $arElement;
            $doctors[$arElement['ID']]['SERVICES'] = [];
            $doctors[$arElement['ID']]['SHOPS'] = [];
        }

        // Теперь получаем все свойства для найденных врачей
        if (!empty($doctors)) {
            $doctorIds = array_keys($doctors);
            $rsProps = CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $this->doctorsIblockId,
                    'ID' => $doctorIds,
                    'ACTIVE' => 'Y'
                ],
                false, false, $select
            );

            while ($arElement = $rsProps->GetNext()) {
                $id = $arElement['ID'];
                if (isset($doctors[$id])) {
                    // Объединяем данные
                    $doctors[$id] = array_merge($doctors[$id], $arElement);
                    
                    // Обрабатываем множественные свойства
                    if (!empty($arElement['PROPERTY_LINK_SERVICES_VALUE'])) {
                        $services = is_array($arElement['PROPERTY_LINK_SERVICES_VALUE']) 
                            ? $arElement['PROPERTY_LINK_SERVICES_VALUE'] 
                            : [$arElement['PROPERTY_LINK_SERVICES_VALUE']];
                        $doctors[$id]['SERVICES'] = array_merge($doctors[$id]['SERVICES'], $services);
                    }
                    
                    if (!empty($arElement['PROPERTY_SHOP_VALUE'])) {
                        $shops = is_array($arElement['PROPERTY_SHOP_VALUE']) 
                            ? $arElement['PROPERTY_SHOP_VALUE'] 
                            : [$arElement['PROPERTY_SHOP_VALUE']];
                        $doctors[$id]['SHOPS'] = array_merge($doctors[$id]['SHOPS'], $shops);
                    }
                }
            }
        }

        $shops = $this->getShops();
        $sections = [];

        foreach ($doctors as $id => $arElement) {
            // Убираем дубликаты
            $arElement['SERVICES'] = array_unique($arElement['SERVICES']);
            $arElement['SHOPS'] = array_unique($arElement['SHOPS']);
            
            // Обрабатываем фото
            $arElement['PHOTO'] = '';
            foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $pic) {
                if ($arElement[$pic]) {
                    $file = CFile::GetFileArray($arElement[$pic]);
                    if ($file) { 
                        $arElement['PHOTO'] = $file['SRC']; 
                        break; 
                    }
                }
            }

            // Информация о филиале
            if (!empty($arElement['SHOPS']) && isset($shops[$arElement['SHOPS'][0]])) {
                $arElement['SHOP_INFO'] = $shops[$arElement['SHOPS'][0]];
            }

            // Строка услуг
            $arElement['SERVICES_STR'] = '';
            if (!empty($arElement['SERVICES'])) {
                $serviceNames = [];
                $allServices = $this->getServices();
                foreach ($arElement['SERVICES'] as $serviceId) {
                    if (isset($allServices[$serviceId])) {
                        $serviceNames[] = $allServices[$serviceId]['NAME'];
                    }
                }
                $arElement['SERVICES_STR'] = implode(', ', $serviceNames);
            }

            // Обрабатываем дополнительные свойства
            $this->processElementProperties($arElement);

            // Группируем по разделам
            $secId = (int)$arElement['IBLOCK_SECTION_ID'];
            if (!isset($sections[$secId])) {
                $sections[$secId] = [
                    'ID' => $secId,
                    'NAME' => '',
                    'DESCRIPTION' => '',
                    'ITEMS' => []
                ];
                
                if ($secId > 0) {
                    $rsSection = CIBlockSection::GetByID($secId);
                    if ($arSection = $rsSection->GetNext()) {
                        $sections[$secId]['NAME'] = $arSection['NAME'];
                        $sections[$secId]['DESCRIPTION'] = $arSection['DESCRIPTION'];
                    }
                }
            }
            $sections[$secId]['ITEMS'][] = $arElement;
        }

        return $sections;
    }

    /* ---------- филиалы ---------- */
    protected function getShops()
    {
        if ($this->shopsIblockId <= 115) {
            return $this->getShopsFromScheduleData();
        }
        
        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ['IBLOCK_ID' => $this->shopsIblockId, 'ACTIVE' => 'Y'],
            false, false,
            ['ID', 'NAME', 'PROPERTY_ADDRESS', 'PROPERTY_COORDINATES', 'PROPERTY_PHONE', 'PROPERTY_EMAIL']
        );
        
        $shops = [];
        while ($arElement = $rs->GetNext()) {
            $shops[$arElement['ID']] = $arElement;
        }
        
        // Если ничего не найдено, пытаемся получить из расписания
        if (empty($shops)) {
            return $this->getShopsFromScheduleData();
        }
        
        return $shops;
    }

    protected function getShopsFromScheduleData()
    {
        global $DB;
        
        $sql = "
            SELECT DISTINCT SHOP_ID 
            FROM kantryvet_kantryvetmed_chart_regular 
            WHERE SITE_ID = '" . $DB->ForSql(SITE_ID) . "'
            UNION
            SELECT DISTINCT SHOP_ID 
            FROM kantryvet_kantryvetmed_chart 
            WHERE SITE_ID = '" . $DB->ForSql(SITE_ID) . "'
        ";
        
        $result = $DB->Query($sql);
        $shops = [];
        
        while ($row = $result->Fetch()) {
            $shopId = $row['SHOP_ID'];
            if ($shopId) {
                $shops[$shopId] = [
                    'ID' => $shopId,
                    'NAME' => 'Филиал #' . $shopId,
                    'PROPERTY_ADDRESS_VALUE' => 'Адрес уточняется',
                    'PROPERTY_COORDINATES_VALUE' => '',
                    'PROPERTY_PHONE_VALUE' => '',
                    'PROPERTY_EMAIL_VALUE' => ''
                ];
            }
        }
        
        return $shops;
    }

    /* ---------- услуги ---------- */
    protected function getServices()
    {
        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ['IBLOCK_ID' => $this->servicesIblockId, 'ACTIVE' => 'Y'],
            false, false,
            ['ID', 'NAME', 'DETAIL_PAGE_URL', 'PROPERTY_DURATION', 'PROPERTY_PRICE']
        );
        
        $services = [];
        while ($arElement = $rs->GetNext()) {
            $services[$arElement['ID']] = $arElement;
        }
        
        return $services;
    }

    /* ---------- свойства врачей ---------- */
    protected function processElementProperties(&$arElement)
    {
        $arElement['CONTACT_PROPERTIES'] = [];
        $arElement['SOCIAL_PROPERTIES']  = [];

        // Телефон
        if (!empty($arElement['PROPERTY_PHONE_VALUE'])) {
            $phones = is_array($arElement['PROPERTY_PHONE_VALUE']) 
                ? $arElement['PROPERTY_PHONE_VALUE'] 
                : [$arElement['PROPERTY_PHONE_VALUE']];
            
            foreach ($phones as $phone) {
                if ($phone) {
                    $arElement['CONTACT_PROPERTIES'][] = [
                        "NAME" => "Телефон",
                        "VALUE" => $phone,
                        "TYPE" => "PHONE",
                        "HREF" => "tel:" . preg_replace('/[^0-9+]/', '', $phone)
                    ];
                }
            }
        }

        // Email
        if (!empty($arElement['PROPERTY_EMAIL_VALUE'])) {
            $emails = is_array($arElement['PROPERTY_EMAIL_VALUE']) 
                ? $arElement['PROPERTY_EMAIL_VALUE'] 
                : [$arElement['PROPERTY_EMAIL_VALUE']];
                
            foreach ($emails as $email) {
                if ($email) {
                    $arElement['CONTACT_PROPERTIES'][] = [
                        "NAME" => "Email",
                        "VALUE" => $email,
                        "TYPE" => "EMAIL",
                        "HREF" => "mailto:" . $email
                    ];
                }
            }
        }

        // Квалификация
        if (!empty($arElement['PROPERTY_QUALIFICATION_VALUE'])) {
            $arElement['CONTACT_PROPERTIES'][] = [
                "NAME" => "Квалификация",
                "VALUE" => $arElement['PROPERTY_QUALIFICATION_VALUE'],
                "TYPE" => "TEXT"
            ];
        }

        // Филиал
        if (isset($arElement['SHOP_INFO'])) {
            $arElement['CONTACT_PROPERTIES'][] = [
                "NAME" => "Филиал",
                "VALUE" => $arElement['SHOP_INFO']['NAME'],
                "TYPE" => "TEXT"
            ];
        }

        // Социальные сети
        $socialMap = [
            'PROPERTY_SOCIAL_VK_VALUE' => 'vk.svg',
            'PROPERTY_SOCIAL_FACEBOOK_VALUE' => 'facebook.svg',
            'PROPERTY_SOCIAL_INSTAGRAM_VALUE' => 'instagram.svg',
            'PROPERTY_SOCIAL_TWITTER_VALUE' => 'twitter.svg',
            'PROPERTY_SOCIAL_SKYPE_VALUE' => 'skype.svg',
            'PROPERTY_SOCIAL_MAIL_VALUE' => 'mail.svg',
            'PROPERTY_SOCIAL_ODNOKLASSNIKI_VALUE' => 'ok.svg',
        ];

        foreach ($socialMap as $key => $img) {
            if (!empty($arElement[$key])) {
                $arElement['SOCIAL_PROPERTIES'][] = [
                    "VALUE" => $arElement[$key],
                    "PATH" => SITE_TEMPLATE_PATH . "/images/svg/" . $img,
                    "NAME" => strtoupper(basename($img, '.svg'))
                ];
            }
        }

        $arElement['EDIT_LINK']   = CIBlock::GetArrayByID($this->doctorsIblockId, "ELEMENT_EDIT");
        $arElement['DELETE_LINK'] = CIBlock::GetArrayByID($this->doctorsIblockId, "ELEMENT_DELETE");
    }

    /* ---------- расписание врача ---------- */
    public function getDoctorSchedule($doctorId, $bRegular = true, $dateFrom = null, $dateTo = null)
    {
        global $DB;
        
        if (!$doctorId) {
            return [];
        }
        
        $schedule = [];
        $tableName = $bRegular ? 'kantryvet_kantryvetmed_chart_regular' : 'kantryvet_kantryvetmed_chart';
        
        $sql = "
            SELECT SERVICE_ID, STAFF_ID, DATE, SHOP_ID, WORK_TIME 
            FROM {$tableName}
            WHERE STAFF_ID = " . intval($doctorId) . "
              AND SITE_ID = '" . $DB->ForSql(SITE_ID) . "'
        ";
        
        if (!$bRegular && $dateFrom && $dateTo) {
            $sql .= " AND DATE >= '" . $DB->ForSql($dateFrom) . "' AND DATE <= '" . $DB->ForSql($dateTo) . "'";
        }
        
        $sql .= " ORDER BY WORK_TIME ASC";
        
        $result = $DB->Query($sql);
        if (!$result) {
            return [];
        }
        
        while ($row = $result->Fetch()) {
            if (empty($row['DATE']) || empty($row['WORK_TIME'])) {
                continue;
            }

            $dayKey = $bRegular 
                ? strtoupper($row['DATE']) 
                : strtoupper((new DateTime($row['DATE']))->format('l'));

            if (!isset($schedule[$dayKey])) {
                $schedule[$dayKey] = [];
            }

            // Создаем уникальный ключ для сортировки
            $timeKey = str_replace([':', '-'], '', $row['WORK_TIME']) . '_' . $row['SERVICE_ID'];

            $schedule[$dayKey][$timeKey] = [
                'DATE'       => $dayKey,
                'WORK_TIME'  => $row['WORK_TIME'],
                'SHOP_ID'    => $row['SHOP_ID'],
                'SERVICE_ID' => $row['SERVICE_ID'],
            ];
        }

        // Сортируем слоты внутри каждого дня
        foreach ($schedule as $day => $slots) {
            ksort($schedule[$day]);
        }

        return $schedule;
    }

    /* ---------- AJAX запросы ---------- */
    protected function handleAjaxRequest()
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'loadStaffSchedule':
                $this->handleLoadStaffSchedule();
                break;
            case 'filterDoctors':
                $this->handleFilterDoctors();
                break;
            default:
                echo json_encode(['error' => 'Unknown action']);
        }
        die();
    }

    protected function handleLoadStaffSchedule()
    {
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        if (!$doctorId) {
            echo '<div class="alert alert-danger">Неверный ID врача</div>';
            return;
        }

        // Получаем данные врача
        $arDoctor = [];
        $rs = CIBlockElement::GetByID($doctorId);
        if ($doctor = $rs->GetNext()) {
            $arDoctor = $doctor;
        }

        // Получаем расписание
        $schedule = $this->getDoctorSchedule(
            $doctorId, 
            true, 
            $_POST['datefrom'] ?? null, 
            $_POST['dateto'] ?? null
        );
        
        $shops = $this->getShops();
        $services = $this->getServices();

        // Подключаем шаблон расписания
        include __DIR__ . '/templates/.default/schedule_template.php';
    }

    protected function handleFilterDoctors()
    {
        $search = trim($_POST['search'] ?? '');
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $shopId = (int)($_POST['shop_id'] ?? 0);

        // Здесь можно реализовать фильтрацию и вернуть JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Фильтрация выполнена',
            'data' => []
        ]);
    }

    /* ---------- основной метод ---------- */
    public function executeComponent()
    {
        if (!$this->checkModules()) {
            return;
        }
        
        if (!$this->checkParams()) {
            return;
        }

        // Обработка AJAX запросов
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
            $this->handleAjaxRequest();
            return;
        }

        // Кеширование результатов
        if ($this->StartResultCache(false, [$this->arParams, $_GET])) {
            
            $this->arResult['SECTIONS'] = $this->getDoctors();
            $this->arResult['SERVICES'] = $this->getServices();
            $this->arResult['SHOPS'] = $this->getShops();
            $this->arResult['AJAX_ID'] = 'doctor_schedule_list_' . randString(6);
            $this->arResult['COMPONENT_PATH'] = $this->getPath();
            $this->arResult['IS_AJAX'] = false;

            $this->SetResultCacheKeys(['SECTIONS', 'SERVICES', 'SHOPS', 'AJAX_ID']);
            $this->IncludeComponentTemplate();
        }

        return $this->arResult;
    }
}