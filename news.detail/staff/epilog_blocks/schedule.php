<?php
use \Bitrix\Main\Localization\Loc;

$bTab = isset($tabCode) && $tabCode === 'schedule';

$isAjaxStaff = TSolution::checkAjaxRequest() && isset($_POST['BLOCK']) && $_POST['BLOCK'] === 'staff-schedule';
?>
<?//show schedule block?>
<?if($templateData['SCHEDULE']):?>
    <?if($isAjaxStaff):?>
        <?
        $APPLICATION->RestartBuffer();

        $site_id = $_POST['site_id'];
        \CKantryvetmedChartTable::$siteID = $site_id;
        $bRegular = \CKantryvetmedChartTable::viewMode() == 'REGULAR';

        $serviceIblock = \Bitrix\Main\Config\Option::get(TSolution::moduleID, 'SERVICES_IBLOCK_ID', \CKantryvetmedCache::$arIBlocks[$site_id]['kantryvet_kantryvetmed_content']['kantryvet_kantryvetmed_services'][0], $site_id);
        $contactIblock = \Bitrix\Main\Config\Option::get(TSolution::moduleID, 'CONTACT_IBLOCK_ID', \CKantryvetmedCache::$arIBlocks[$site_id]['kantryvet_kantryvetmed_content']['kantryvet_kantryvetmed_contact'][0], $site_id);

        function getPeriod() {
            $result = array();

            if (isset($_POST['datefrom']) && $_POST['datefrom']) {
                $result['start']['date'] = \DateTime::createFromFormat('U', $_POST['datefrom']);
            }
            else {
                $result['start']['date'] = new \DateTime('monday this week', new \DateTimeZone('UTC'));
            }
            $result['start']['str'] = $result['start']['date']->format('d.m.Y');

            if (isset($_POST['dateto']) && $_POST['dateto']) {
                $result['end']['date'] = \DateTime::createFromFormat('U', $_POST['dateto']);
            }
            else {
                $result['end']['date'] = new \DateTime('sunday this week', new \DateTimeZone('UTC'));
            }
            $result['end']['str'] = $result['end']['date']->format('d.m.Y');

            return $result;
        }

        $arrShops = $arrServ = array();
        $arrAllData = array(
            'MONDAY' => array(),
            'TUESDAY' => array(),
            'WEDNESDAY' => array(),
            'THURSDAY' => array(),
            'FRIDAY' => array(),
            'SATURDAY' => array(),
            'SUNDAY' => array(),
        );

        if ($bRegular) {
            $formatYear = new \DateTime('monday this week', new \DateTimeZone('UTC'));
            $formatYear = $formatYear->format("Y");
            $params = array(
                'order' => array('DATE' => 'asc'),
                'select' => array(
                    '*',
                ),
                'filter' => array(
                    'SITE_ID' => $site_id,
                    'STAFF_ID' => $arResult['ID'],
                ),
            );
        }
        else {
            $arDates = getPeriod();
            $formatYear = $arDates['end']['date']->format('Y');
            $params = array(
                'filter' => array(
                    'SITE_ID' => $site_id,
                    'STAFF_ID' => $arResult['ID'],
                    '>=DATE' => \Bitrix\Main\Type\Date::createFromPhp($arDates['start']['date']),
                    '<=DATE' => \Bitrix\Main\Type\Date::createFromPhp($arDates['end']['date']),
                ),
            );
        }

        $res = \CKantryvetmedChartTable::getList($params);

        $formatDay = '';
        $formatDate = array();

        if($bRegular) {
            for ($start = 0; $start <= 6; ++$start) {
                $formatDay = $arrAllData[$start];
                $formatDate[$formatDay] = GetMessage($arrAllData[$start]);	
                $formatDate[$formatDay.'_SHORT'] = GetMessage($arrAllData[$start].'_SHORT');			
            }
        }
        else {
            for ($start = $arDates['start']['date']; $start <= $arDates['end']['date']; $start->add( new DateInterval("P1D"))) {
                $formatDay = strtoupper($start->format("l"));
                $formatDate[$formatDay] = $start->format("j")." ".GetMessage(strtoupper($start->format("M")));	
                $formatDate[$formatDay.'_SHORT'] = $start->format("j")." ".GetMessage(strtoupper($start->format("M")).'_SHORT');			
            }
        }
            
        $bEmptyData = true;
        while ($staff = $res->Fetch()) {
            $arrShops[] = $staff['SHOP_ID'];
            $arrServ[] = $staff['SERVICE_ID'];
            $bEmptyData = false;

            if ($bRegular) {
                $key = preg_replace('/[^\d]/', '', $staff['WORK_TIME']).($staff['ID'] % 10);
                $arrAllData[$staff['DATE']][$key] = array(
                    'DATE' => $staff['DATE'], 
                    'WORK_TIME' => $staff['WORK_TIME'], 
                    'SHOP_ID' => $staff['SHOP_ID'], 
                    'SERVICE_ID' => $staff['SERVICE_ID']
                );
            }
            else {
                $key = preg_replace('/[^\d]/', '', $staff['WORK_TIME']).($staff['ID'] % 10);
                $arrAllData[strtoupper($staff['DATE']->format('l'))][$key] = array(
                    'DATE' => $staff['DATE'], 
                    'WORK_TIME' => $staff['WORK_TIME'], 
                    'SHOP_ID' => $staff['SHOP_ID'], 
                    'SERVICE_ID' => $staff['SERVICE_ID']
                );
            }
        }

        foreach ($arrAllData as &$currentDate) {
            ksort($currentDate);
        }
        unset($currentDate);

        if(!empty($arrShops)){
            $arItemsShops = \CKantryvetmedCache::CIBLockElement_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => \CKantryvetmedCache::GetIBlockCacheTag($contactIblock))), array('IBLOCK_ID' => $contactIblock, 'ACTIVE' => 'Y', 'ID' => $arrShops), false, false, array('ID', 'NAME', 'PROPERTY_ADDRESS', 'PROPERTY_MAP', 'PROPERTY_PHONE', 'PROPERTY_EMAIL', 'PROPERTY_METRO', 'PROPERTY_SCHEDULE', 'DETAIL_PAGE_URL'));
            
            $shopsInfo = array_column($arItemsShops, NULL, 'ID');

            $resProp = \CIBlock::GetProperties($contactIblock, Array(), Array());
            while($res_arr = $resProp->Fetch()){
                $shopPropName[$res_arr['CODE']] = $res_arr['NAME'];
            }
        }

        if(!empty($serviceIblock)){
            $arItemsServ = \CKantryvetmedCache::CIBLockElement_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => \CKantryvetmedCache::GetIBlockCacheTag($serviceIblock))), array('IBLOCK_ID' => $serviceIblock, 'ACTIVE' => 'Y', 'ID' => $arrServ), false, false, array('ID', 'NAME', 'DETAIL_PAGE_URL'));

            $servInfo = array_column($arItemsServ, NULL, 'ID');
        }

        if(!$bRegular) {
            $arDatesPrev = getPeriod();
            $arDatesNext = getPeriod();

            $strToPrevWeek = '';
            $strToNextvWeek = '';

            if( isset($_GET['datefrom']) ) {
                $datefromPrev = $arDatesPrev['start']['date']->sub(new \DateInterval('P1W'))->getTimestamp();
                $datefromNext = $arDatesNext['start']['date']->add(new \DateInterval('P1W'))->getTimestamp();
            }
            if( isset($_GET['dateto']) ) {
                $datetoPrev = $arDatesPrev['end']['date']->sub(new \DateInterval('P1W'))->getTimestamp();
                $datetoNext = $arDatesNext['end']['date']->add(new \DateInterval('P1W'))->getTimestamp();
            }

            if( !isset($_GET['datefrom']) && !isset($_GET['dateto']) ) {
                $datefromPrev = $arDatesPrev['start']['date']->sub(new \DateInterval('P1W'))->getTimestamp();
                $datetoPrev = $arDatesPrev['end']['date']->sub(new \DateInterval('P1W'))->getTimestamp();

                $datefromNext = $arDatesNext['start']['date']->add(new \DateInterval('P1W'))->getTimestamp();
                $datetoNext = $arDatesNext['end']['date']->add(new \DateInterval('P1W'))->getTimestamp();
            }
        }
        
        $arPlacemarks = array();
        $mapLAT = $mapLON = $iCountShops = 0;
        
        foreach ($shopsInfo as $arItem) {
            $arCoords = explode(',', $arItem['PROPERTY_MAP_VALUE']);
            $mapLAT += $arCoords[0];
            $mapLON += $arCoords[1];

            $phones = '';
            $arItem['PROPERTY_PHONE_VALUE'] = (is_array($arItem['PROPERTY_PHONE_VALUE']) ? $arItem['PROPERTY_PHONE_VALUE'] : ($arItem['PROPERTY_PHONE_VALUE'] ? array($arItem['PROPERTY_PHONE_VALUE']) : array()));
            foreach ($arItem['PROPERTY_PHONE_VALUE'] as $phone) {
                $phones .= '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array('+', ' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
            }

            $emails = '';
            $arItem['PROPERTY_EMAIL_VALUE'] = (is_array($arItem['PROPERTY_EMAIL_VALUE']) ? $arItem['PROPERTY_EMAIL_VALUE'] : ($arItem['PROPERTY_EMAIL_VALUE'] ? array($arItem['PROPERTY_EMAIL_VALUE']) : array()));
            foreach ($arItem['PROPERTY_EMAIL_VALUE'] as $email) {
                $emails .= '<a class="dark_link" href="mailto:' .$email. '">' .$email . '</a><br>';
            }

            $metrolist = '';
            $arItem['PROPERTY_METRO_VALUE'] = (is_array($arItem['PROPERTY_METRO_VALUE']) ? $arItem['PROPERTY_METRO_VALUE'] : ($arItem['PROPERTY_METRO_VALUE'] ? array($arItem['PROPERTY_METRO_VALUE']) : array()));
            foreach ($arItem['PROPERTY_METRO_VALUE'] as $metro) {
                $metrolist .= '<div class="metro"><i></i>'. $metro . '</div>';
            }

            $address = $arItem['NAME'].($arItem['PROPERTY_ADDRESS_VALUE'] ? ', '.$arItem['PROPERTY_ADDRESS_VALUE'] : '');

            $popupOptions = [
                'ITEM' => [
                    'NAME' => $address,
                    'URL' => $arItem['DETAIL_PAGE_URL'],
                    'EMAIL' => $arItem['PROPERTY_EMAIL_VALUE'],
                    'EMAIL_HTML' => $emails,
                    'PHONE' => $arItem['PROPERTY_PHONE_VALUE'],
                    'PHONE_HTML' => $phones,
                    'METRO' => $arItem['PROPERTY_METRO_VALUE'],
                    'METRO_HTML' => $metrolist,
                    'SCHEDULE' => $arItem['PROPERTY_SCHEDULE_VALUE']['TYPE'] === 'HTML' ? $arItem['~PROPERTY_SCHEDULE_VALUE']['TEXT'] : $arItem['PROPERTY_SCHEDULE_VALUE']['TEXT'],
                    'DISPLAY_PROPERTIES' => [
                        'METRO' => [
                            'NAME' => Loc::getMessage('MYMS_TPL_METRO'),
                        ],
                        'SCHEDULE' => [
                            'NAME' => Loc::getMessage('MYMS_TPL_SCHEDULE'),
                        ],
                        'PHONE' => [
                            'NAME' =>  Loc::getMessage('MYMS_TPL_PHONE'),
                        ],
                        'EMAIL' => [
                            'NAME' => Loc::getMessage('MYMS_TPL_EMAIL'),
                        ]
                    ]
                ],
                'PARAMS' => [
                    'TITLE' => '',
                    'BTN_CLASS' => '',
                ],
                'SHOW_QUESTION_BTN' => 'N',
                'SHOW_RECORD_ONLINE_BTN' => 'Y',
                'SHOW_RECORD_ONLINE_BTN_ATTRS' => 'data-autoload-specialist="'.$arResult['ID'].'"',
                'SHOW_SOCIAL' => 'N',
                'SHOW_CLOSE' => 'N',
                'SHOW_TITLE' => 'N',
            ];

            $arPlacemarks[] = array(
                "LAT" => $arCoords[0],
                "LON" => $arCoords[1],
                "TEXT" => TSolution\Functions::getItemMapHtml($popupOptions),
            );

            ++$iCountShops;
        }
        ?>
        <div class="staff-schedule <?=($bRegular ? 'staff-schedule--regular' : '')?>">
            <div class="staff-schedule__top">
                <?if(!$bRegular):?>
                    <div class="switch_block">
                        <span class="arrow_link prev_week stroke-theme-parent-all colored_theme_hover_bg-block animate-arrow-hover" data-datefrom="<?=$datefromPrev?>" data-dateto="<?=$datetoPrev?>"  title="<?=GetMessage('PREV_WEEK')?>"><span class="arrow-all stroke-theme-target"><?=TSolution::showIconSvg(' arrow-all__item-arrow', SITE_TEMPLATE_PATH.'/images/svg/Arrow_map.svg');?><span class="arrow-all__item-line colored_theme_hover_bg-el"></span></span></span>
                        <div class ="switch_week_title">
                            <?=$formatDate['MONDAY'].' - '.$formatDate['SUNDAY'].' '.$formatYear?>
                        </div>
                        <span class="arrow_link next_week  stroke-theme-parent-all colored_theme_hover_bg-block animate-arrow-hover" data-datefrom="<?=$datefromNext?>" data-dateto="<?=$datetoNext?>" title="<?=GetMessage('NEXT_WEEK')?>"><span class="arrow-all stroke-theme-target"><?=TSolution::showIconSvg(' arrow-all__item-arrow', SITE_TEMPLATE_PATH.'/images/svg/Arrow_map.svg');?><span class="arrow-all__item-line colored_theme_hover_bg-el"></span></span></span>
                    </div>
                <?endif;?>
                <div class="staff-schedule__head hidden-xs">
                    <div class="row">
                        <div class="col-sm-<?=$bRegular ? '1' : '2'?>">
                            <?=GetMessage('WORK_DATE_TITLE'.($bRegular ? '_SHORT' : ''));?>
                        </div>
                        <div class="col-sm-<?=$bRegular ? '11' : '10'?> ">
                            <div class="sub_row columns_titles row">
                                <div class="col-sm-3">
                                    <?=GetMessage('WORK_TIME_TITLE');?>
                                </div>
                                <?if($iCountShops):?>
                                    <div class="col-sm-4">
                                        <?=GetMessage('WORK_SHOP_TITLE');?>
                                    </div>
                                <?endif?>
                                <div class="col-sm-5">
                                    <?=GetMessage('SERVICE_TITLE');?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>				
            </div>

            <div class="staff-schedule__body">
                <?foreach ($arrAllData as $keyShed => $valueShed):?>
                    <div class="staff-schedule__row <?=(empty($valueShed) ? 'staff-schedule__row--empty' : '')?>">
                        <div class="row">
                            <div class="col-sm-<?=$bRegular ? '1' : '2'?> col-xs-<?=$bRegular ? '2' : '4'?> staff-schedule__date">
                                <div class="week_name"><?=GetMessage($keyShed);?></div>
                                <?if(!$bRegular):?>
                                    <div class="date">
                                        <?=$formatDate[$keyShed]?>
                                    </div>
                                    <div class="date short">
                                        <?=$formatDate[$keyShed.'_SHORT']?>
                                    </div>
                                <?endif;?>
                            </div>
                            <div class="col-sm-<?=$bRegular ? '11' : '10'?> staff-schedule__info col-xs-<?=$bRegular ? '10' : '8'?>">
                                <?if(empty($valueShed)):?>
                                    <div class="sub_row no_rec"><?=GetMessage("NO_REC");?></div>
                                <?endif;?>
                                <?foreach ($valueShed as $keyStr => $valueStr){?>
                                    <div class="sub_row">
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <?=$valueStr['WORK_TIME'];?>
                                            </div>
                                            <?if($iCountShops):?>
                                                <div class="col-sm-4">
                                                    <div class="staff-staff-schedule___address-coord show_on_map">
                                                        <span class="text_wrap font_14 color-theme" data-coordinates="<?=$shopsInfo[$valueStr['SHOP_ID']]['PROPERTY_MAP_VALUE']?>" data-scale="17">
                                                            <?=TSolution::showIconSvg('on_map fill-theme', SITE_TEMPLATE_PATH.'/images/svg/show_on_map.svg');?>
                                                            <span class="text dotted"><?=($shopsInfo[$valueStr['SHOP_ID']]['PROPERTY_ADDRESS_VALUE'] ? $shopsInfo[$valueStr['SHOP_ID']]['PROPERTY_ADDRESS_VALUE'] : $shopsInfo[$valueStr['SHOP_ID']]['NAME'])?></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?endif;?>
                                            <div class="col-sm-5 services_block">
                                                <?if($servInfo[$valueStr['SERVICE_ID']]['DETAIL_PAGE_URL']):?>
                                                    <a class="dark_link" href="<?=$servInfo[$valueStr['SERVICE_ID']]['DETAIL_PAGE_URL']?>">
                                                <?endif;?>
                                                    <?=$servInfo[$valueStr['SERVICE_ID']]['NAME'];?>
                                                <?if($servInfo[$valueStr['SERVICE_ID']]['DETAIL_PAGE_URL']):?>
                                                    </a>
                                                <?endif;?>
                                            </div>
                                        </div>
                                    </div>
                                <?}?>
                            </div>
                        </div>
                    </div>
                <?endforeach;?>
            </div>
            <?if(!$bEmptyData && $iCountShops):?>
                <?$typeMap = $arParams['TYPE_MAP'];?>
                <div class="staff-schedule__map-wrapper">
                    <div class="staff-schedule__map">
                        <?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('staff-map-schedule');?>
                        <?
                        $mapLAT = floatval($mapLAT / $iCountShops);
                        $mapLON = floatval($mapLON / $iCountShops);
                        ?>
                        <?if($typeMap == 'GOOGLE'):?>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:map.google.view",
                                "map",
                                array(
                                    "API_KEY" => \Bitrix\Main\Config\Option::get('fileman', 'google_map_api_key', ''),
                                    "INIT_MAP_TYPE" => "ROADMAP",
                                    "COMPONENT_TEMPLATE" => "map",
                                    "COMPOSITE_FRAME_MODE" => "A",
                                    "COMPOSITE_FRAME_TYPE" => "AUTO",
                                    "CONTROLS" => array(
                                        0 => "SMALL_ZOOM_CONTROL",
                                        1 => "TYPECONTROL",
                                    ),
                                    "OPTIONS" => array(
                                        0 => "ENABLE_DBLCLICK_ZOOM",
                                        1 => "ENABLE_DRAGGING",
                                    ),
                                    "MAP_DATA" => serialize(array("google_lat" => $mapLAT, "google_lon" => $mapLON, "google_scale" => 17, "PLACEMARKS" => $arPlacemarks)),
                                    "MAP_HEIGHT" => "500",
                                    "MAP_WIDTH" => "100%",
                                    "MAP_ID" => "STAFF_SCHEDULE_MAP",
                                    "ZOOM_BLOCK" => array(
                                        "POSITION" => "right center",
                                    )
                                ),
                                false
                            );?>
                        <?else:?>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:map.yandex.view",
                                "map",
                                array(
                                    "API_KEY" => \Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key', ''),
                                    "INIT_MAP_TYPE" => "MAP",
                                    "COMPONENT_TEMPLATE" => "map",
                                    "COMPOSITE_FRAME_MODE" => "A",
                                    "COMPOSITE_FRAME_TYPE" => "AUTO",
                                    "CONTROLS" => array(
                                        0 => "ZOOM",
                                        1 => "SMALLZOOM",
                                        2 => "TYPECONTROL",
                                    ),
                                    "OPTIONS" => array(
                                        0 => "ENABLE_DBLCLICK_ZOOM",
                                        1 => "ENABLE_DRAGGING",
                                    ),
                                    "MAP_DATA" => serialize(array("yandex_lat" => $mapLAT, "yandex_lon" => $mapLON, "yandex_scale" => 17, "PLACEMARKS" => $arPlacemarks)),
                                    "MAP_WIDTH" => "100%",
                                    "MAP_HEIGHT" => "500",
                                    "MAP_ID" => "STAFF_SCHEDULE_MAP",
                                    "ZOOM_BLOCK" => array(
                                        "POSITION" => "right center",
                                    )
                                ),
                                false
                            );?>
                        <?endif;?>
                        <?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID("staff-map-schedule", "");?>
                    </div>
                </div>
            <?endif;?>
        </div>
        <?die();?>
    <?else:?>
        <?if(!isset($html_schedule)):?>
            <?ob_start();?>
            <div class="js-load-staff-schedule loading-state" data-site_id="<?=SITE_ID?>"></div>
            <?$html_schedule = trim(ob_get_clean());?>
        <?endif;?>

        <?if($bTab):?>
            <?if(!isset($bShow_schedule)):?>
                <?$bShow_schedule = true;?>
            <?else:?>
                <div class="tab-pane <?=(!($iTab++) ? 'active' : '')?>" id="schedule">
                    <div class="ordered-block__title switcher-title font_22"><?=$arParams["T_SCHEDULE"]?></div>
                    <?=$html_schedule?>
                </div>
            <?endif;?>
        <?else:?>
            <div class="detail-block ordered-block schedule">
                <div class="ordered-block__title switcher-title font_22"><?=$arParams["T_SCHEDULE"]?></div>
                <?=$html_schedule?>
            </div>
        <?endif;?>
    <?endif;?>
<?endif;?>