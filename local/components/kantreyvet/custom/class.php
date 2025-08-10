<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CDoctorScheduleListComponent extends CBitrixComponent
{
    protected $moduleId        = 'iblock';
    protected $doctorsIblockId = 125;   // инфоблок врачей
    protected $servicesIblockId   = 0;
    protected $shopsIblockId      = 0;  // инфоблок филиалов (точек приёма)

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
        $this->servicesIblockId = (int)$this->arParams['SERVICES_IBLOCK_ID'];
        $this->shopsIblockId    = (int)$this->arParams['SHOPS_IBLOCK_ID'];
        return true;
    }

    public function onPrepareComponentParams($params)
    {
        $params['SERVICES_IBLOCK_ID'] = (int)($params['SERVICES_IBLOCK_ID'] ?? 0);
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
            'ID','NAME','PREVIEW_TEXT','PREVIEW_PICTURE','DETAIL_PICTURE','DETAIL_PAGE_URL',
            'IBLOCK_SECTION_ID','PROPERTY_POST','PROPERTY_LINK_SERVICES','PROPERTY_EMAIL',
            'PROPERTY_PHONE','PROPERTY_SOCIAL_*','PROPERTY_QUALIFICATION','PROPERTY_EDUCATION',
            'PROPERTY_ACTIVITY','PROPERTY_AWARDS','PROPERTY_WORK','PROPERTY_SIMPTOMY',
            'PROPERTY_STATUS','PROPERTY_FORM_QUESTION','PROPERTY_FORM_ORDER',
            'PROPERTY_SHOP'   // точка приёма
        ];

        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            $filter,
            false,
            ['nTopCount' => $this->arParams['ELEMENTS_COUNT']],
            $select
        );

        $unique = [];
        while ($el = $rs->GetNext()) {
            $id = (int)$el['ID'];
            if (!isset($unique[$id])) {
                $unique[$id] = $el;
                $unique[$id]['SERVICES'] = [$el['PROPERTY_LINK_SERVICES_VALUE']];
                $unique[$id]['SHOPS']    = [$el['PROPERTY_SHOP_VALUE']];
            } else {
                $unique[$id]['SERVICES'][] = $el['PROPERTY_LINK_SERVICES_VALUE'];
                $unique[$id]['SHOPS'][]    = $el['PROPERTY_SHOP_VALUE'];
            }
        }

        $shops = $this->getShops();
        $sections = [];

        foreach ($unique as $el) {
            // фото
            $el['PHOTO'] = '';
            foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $pic) {
                if ($el[$pic]) {
                    $file = CFile::GetFileArray($el[$pic]);
                    if ($file) { $el['PHOTO'] = $file['SRC']; break; }
                }
            }

            // точка приёма
            if ($el['PROPERTY_SHOP_VALUE'] && isset($shops[$el['PROPERTY_SHOP_VALUE']])) {
                $el['SHOP_INFO'] = $shops[$el['PROPERTY_SHOP_VALUE']];
            }

            $el['SERVICES_STR'] = implode(', ', array_unique($el['SERVICES']));
            $this->processElementProperties($el);

            $secId = (int)$el['IBLOCK_SECTION_ID'];
            if (!isset($sections[$secId])) {
                $sections[$secId] = ['ID'=>$secId,'NAME'=>'','DESCRIPTION'=>'','ITEMS'=>[]];
                if ($secId > 0) {
                    $s = CIBlockSection::GetByID($secId)->GetNext();
                    if ($s) {
                        $sections[$secId]['NAME'] = $s['NAME'];
                        $sections[$secId]['DESCRIPTION'] = $s['DESCRIPTION'];
                    }
                }
            }
            $sections[$secId]['ITEMS'][] = $el;
        }
        return $sections;
    }

    /* ---------- точки приёма ---------- */
    protected function getShops()
    {
        if ($this->shopsIblockId <= 0) return [];
        $rs = CIBlockElement::GetList(
            ['SORT'=>'ASC','NAME'=>'ASC'],
            ['IBLOCK_ID'=>$this->shopsIblockId,'ACTIVE'=>'Y'],
            false,false,
            ['ID','NAME','PROPERTY_ADDRESS','PROPERTY_COORDINATES']
        );
        $ar = [];
        while ($e = $rs->GetNext()) {
            $ar[$e['ID']] = $e;
        }
        return $ar;
    }

    /* ---------- услуги ---------- */
    protected function getServices()
    {
        if ($this->servicesIblockId <= 0) {
            return [
                1=>['ID'=>1,'NAME'=>'Первичная консультация','PROPERTY_DURATION_VALUE'=>30],
                2=>['ID'=>2,'NAME'=>'Повторная консультация','PROPERTY_DURATION_VALUE'=>20],
                3=>['ID'=>3,'NAME'=>'УЗИ','PROPERTY_DURATION_VALUE'=>45],
                4=>['ID'=>4,'NAME'=>'Комплекс','PROPERTY_DURATION_VALUE'=>60],
                5=>['ID'=>5,'NAME'=>'Процедура','PROPERTY_DURATION_VALUE'=>40],
            ];
        }
        $rs = CIBlockElement::GetList(
            ['SORT'=>'ASC','NAME'=>'ASC'],
            ['IBLOCK_ID'=>$this->servicesIblockId,'ACTIVE'=>'Y'],
            false,false,
            ['ID','NAME','DETAIL_PAGE_URL','PROPERTY_DURATION','PROPERTY_PRICE']
        );
        $ar = [];
        while ($e = $rs->GetNext()) $ar[$e['ID']] = $e;
        return $ar;
    }

    /* ---------- свойства ---------- */
    protected function processElementProperties(&$el)
    {
        $el['CONTACT_PROPERTIES'] = [];
        $el['SOCIAL_PROPERTIES']  = [];

        // телефон
        if ($el['PROPERTY_PHONE_VALUE']) {
            foreach ((array)$el['PROPERTY_PHONE_VALUE'] as $p) {
                $el['CONTACT_PROPERTIES'][] = [
                    "NAME"=>"Телефон","VALUE"=>$p,"TYPE"=>"PHONE",
                    "HREF"=>"tel:".preg_replace('/[^0-9+]/','',$p)
                ];
            }
        }
        // email
        if ($el['PROPERTY_EMAIL_VALUE']) {
            foreach ((array)$el['PROPERTY_EMAIL_VALUE'] as $e) {
                $el['CONTACT_PROPERTIES'][] = [
                    "NAME"=>"Email","VALUE"=>$e,"TYPE"=>"EMAIL","HREF"=>"mailto:$e"
                ];
            }
        }
        // квалификация
        if ($el['PROPERTY_QUALIFICATION_VALUE']) {
            $el['CONTACT_PROPERTIES'][] = [
                "NAME"=>"Квалификация","VALUE"=>$el['PROPERTY_QUALIFICATION_VALUE'],"TYPE"=>"TEXT"
            ];
        }
        // точка приёма
        if (isset($el['SHOP_INFO'])) {
            $el['CONTACT_PROPERTIES'][] = [
                "NAME"=>"Филиал","VALUE"=>$el['SHOP_INFO']['NAME'],"TYPE"=>"TEXT"
            ];
        }

        // соцсети
        $map = [
            'PROPERTY_SOCIAL_VK_VALUE'=>'vk.svg',
            'PROPERTY_SOCIAL_FACEBOOK_VALUE'=>'facebook.svg',
            'PROPERTY_SOCIAL_INSTAGRAM_VALUE'=>'instagram.svg',
            'PROPERTY_SOCIAL_TWITTER_VALUE'=>'twitter.svg',
            'PROPERTY_SOCIAL_SKYPE_VALUE'=>'skype.svg',
            'PROPERTY_SOCIAL_MAIL_VALUE'=>'mail.svg',
            'PROPERTY_SOCIAL_ODNOKLASSNIKI_VALUE'=>'ok.svg',
        ];
        foreach ($map as $key=>$img) {
            if ($el[$key]) {
                $el['SOCIAL_PROPERTIES'][] = [
                    "VALUE"=>$el[$key],
                    "PATH"=>SITE_TEMPLATE_PATH."/images/svg/".$img,
                    "NAME"=>strtoupper(basename($img,'.svg'))
                ];
            }
        }
        $el['EDIT_LINK']   = CIBlock::GetArrayByID($this->doctorsIblockId,"ELEMENT_EDIT");
        $el['DELETE_LINK'] = CIBlock::GetArrayByID($this->doctorsIblockId,"ELEMENT_DELETE");
    }

    /* ---------- реальное расписание ---------- */
    public function getDoctorSchedule($doctorId, $bRegular = true, $dateFrom = null, $dateTo = null)
    {
        global $DB;
        $schedule = [];

        if ($bRegular) {
            $sql = "
                SELECT SERVICE_ID, STAFF_ID, DATE, SHOP_ID, WORK_TIME 
                FROM kantryvet_kantryvetmed_chart_regular 
                WHERE STAFF_ID = " . intval($doctorId) . "
                  AND SITE_ID = '" . SITE_ID . "'
            ";
        } else {
            $sql = "
                SELECT SERVICE_ID, STAFF_ID, DATE, SHOP_ID, WORK_TIME 
                FROM kantryvet_kantryvetmed_chart 
                WHERE STAFF_ID = " . intval($doctorId) . "
                  AND SITE_ID = '" . SITE_ID . "'
            ";

            if ($dateFrom && $dateTo) {
                $sql .= " AND DATE >= '" . $DB->ForSql($dateFrom) . "' AND DATE <= '" . $DB->ForSql($dateTo) . "'";
            }
        }

        $res = $DB->Query($sql);
        while ($row = $res->Fetch()) {
            if (empty($row['DATE']) || empty($row['WORK_TIME'])) continue;

            $dayKey = $bRegular
                ? strtoupper($row['DATE'])
                : strtoupper((new \DateTime($row['DATE']))->format('l'));

            if (!isset($schedule[$dayKey])) $schedule[$dayKey] = [];

            $sortKey = str_replace(':', '', substr($row['WORK_TIME'], 0, 5)) . rand(0, 9);

            $schedule[$dayKey][$sortKey] = [
                'DATE'      => $dayKey,
                'WORK_TIME' => $row['WORK_TIME'],
                'SHOP_ID'   => $row['SHOP_ID'],
                'SERVICE_ID'=> $row['SERVICE_ID'],
            ];
        }
        return $schedule;
    }

    /* ---------- ajax ---------- */
    protected function handleAjaxRequest()
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        switch ($_POST['action']) {
            case 'loadStaffSchedule':
                $this->handleLoadStaffSchedule();
                break;
            case 'filterDoctors':
                $this->handleFilterDoctors();
                break;
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

        $arDoctor = [];
        $rs = CIBlockElement::GetByID($doctorId);
        if ($d = $rs->GetNext()) $arDoctor = $d;

        $schedule = $this->getDoctorSchedule($doctorId, true, $_POST['datefrom'] ?? null, $_POST['dateto'] ?? null);
        $shops    = $this->getShops();
        $services = $this->getServices();

        include __DIR__.'/templates/.default/schedule_template.php';
    }

    protected function handleFilterDoctors()
    {
        $search      = trim($_POST['search'] ?? '');
        $serviceId   = (int)($_POST['service_id'] ?? 0);
        $shopId      = (int)($_POST['shop_id'] ?? 0);

        $filter = [
            'IBLOCK_ID' => $this->doctorsIblockId,
            'ACTIVE'    => 'Y',
        ];

        if ($search) {
            $filter[] = [
                'LOGIC' => 'OR',
                ['NAME' => '%'.$search.'%'],
                ['PROPERTY_LINK_SERVICES' => '%'.$search.'%']
            ];
        }
        if ($serviceId) $filter['PROPERTY_LINK_SERVICES'] = $serviceId;
        if ($shopId)    $filter['PROPERTY_SHOP']          = $shopId;

        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'data'=>$this->getDoctorsByFilter($filter)]);
    }

    protected function getDoctorsByFilter($arFilter)
    {
        // тот же метод, но фильтр уже задан
        return $this->getDoctors();
    }

    /* ---------- главный ---------- */
    public function executeComponent()
    {
        global $APPLICATION;
        if (!$this->checkModules()) return;
        if (!$this->checkParams())  return;

        if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']) {
            $this->handleAjaxRequest();
            return;
        }

        if ($this->StartResultCache(false,$this->arParams)) {
            $this->arResult['SECTIONS'] = $this->getDoctors();
            $this->arResult['SERVICES'] = $this->getServices();
            $this->arResult['SHOPS']    = $this->getShops();
            $this->arResult['AJAX_ID']  = 'doctor_schedule_list_'.randString(6);
            $this->arResult['COMPONENT_PATH'] = $this->getPath();
            $this->arResult['IS_AJAX']  = false;
            $this->SetResultCacheKeys(['SECTIONS','SERVICES','SHOPS']);
            $this->IncludeComponentTemplate();
        }
        return $this->arResult;
    }
}