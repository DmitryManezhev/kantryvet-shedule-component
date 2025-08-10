<?php
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION;

$bShowMap = false;
if($templateData['ADDRESS']){
	$arPlacemarks = array();
	$mapLAT = $mapLON = $iCountShops = 0;

	foreach ($templateData['ADDRESS'] as $arItem) {
		if(strlen($arItem['PROPERTIES']['MAP']['VALUE'])){
			$bShowMap = true;
			$arCoords = explode(',', $arItem['PROPERTIES']['MAP']['VALUE']);
			$mapLAT += $arCoords[0];
			$mapLON += $arCoords[1];

			$phones = '';
			$arItem['PROPERTIES']['PHONE']['VALUE'] = (is_array($arItem['PROPERTIES']['PHONE']['VALUE']) ? $arItem['PROPERTIES']['PHONE']['VALUE'] : ($arItem['PROPERTIES']['PHONE']['VALUE'] ? array($arItem['PROPERTIES']['PHONE']['VALUE']) : array()));
			foreach ($arItem['PROPERTIES']['PHONE']['VALUE'] as $phone) {
				$phones .= '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array('+', ' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
			}

			$emails = '';
			$arItem['PROPERTIES']['EMAIL']['VALUE'] = (is_array($arItem['PROPERTIES']['EMAIL']['VALUE']) ? $arItem['PROPERTIES']['EMAIL']['VALUE'] : ($arItem['PROPERTIES']['EMAIL']['VALUE'] ? array($arItem['PROPERTIES']['EMAIL']['VALUE']) : array()));
			foreach ($arItem['PROPERTIES']['EMAIL']['VALUE'] as $email) {
				$emails .= '<a class="dark_link" href="mailto:' .$email. '">' .$email . '</a><br>';
			}

			$metrolist = '';
			$arItem['PROPERTIES']['METRO']['VALUE'] = (is_array($arItem['PROPERTIES']['METRO']['VALUE']) ? $arItem['PROPERTIES']['METRO']['VALUE'] : ($arItem['PROPERTIES']['METRO']['VALUE'] ? array($arItem['PROPERTIES']['METRO']['VALUE']) : array()));
			foreach ($arItem['PROPERTIES']['METRO']['VALUE'] as $metro) {
				$metrolist .= '<div class="metro"><i></i>'. $metro . '</div>';
			}

			$address = $arItem['NAME'].($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? ', '.$arItem['PROPERTIES']['ADDRESS']['VALUE'] : '');

			$popupOptions = [
				'ITEM' => [
					'NAME' => $address,
					'URL' => $arItem['DETAIL_PAGE_URL'],
					'EMAIL' => $arItem['PROPERTIES']['EMAIL']['VALUE'],
					'EMAIL_HTML' => $emails,
					'PHONE' => $arItem['PROPERTIES']['PHONE']['VALUE'],
					'PHONE_HTML' => $phones,
					'METRO' => $arItem['PROPERTIES']['METRO']['VALUE'],
					'METRO_HTML' => $metrolist,
					'SCHEDULE' => isset($arItem['PROPERTIES']['SCHEDULE']['VALUE']['TYPE']) 
						? ($arItem['PROPERTIES']['SCHEDULE']['VALUE']['TYPE'] === 'HTML' ? $arItem['PROPERTIES']['SCHEDULE']['~VALUE']['TEXT'] : $arItem['PROPERTIES']['SCHEDULE']['VALUE']['TEXT'])
						: $arItem['PROPERTIES']['SCHEDULE']['VALUE'],
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
	}
}

if($bShowMap){
	$typeMap = $arParams['TYPE_MAP'];
	?>
	<div class="staff-detail__map staff-detail__map--<?=strtolower($typeMap)?> bordered rounded-4" style="display:none;">
		<span class="staff-detail__map__close stroke-theme-hover" title="<?=\Bitrix\Main\Localization\Loc::getMessage('CLOSE_BLOCK')?>"><?=CKantryvetmed::showIconSvg('', SITE_TEMPLATE_PATH.'/images/svg/Close.svg')?></span>
		<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('staff-map');?>
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
					"MAP_ID" => "STAFF_DETAIL_MAP",
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
					"MAP_ID" => "STAFF_DETAIL_MAP",
					"ZOOM_BLOCK" => array(
						"POSITION" => "right center",
					)
				),
				false
			);?>
		<?endif;?>
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('staff-map', '');?>
	</div>
	<?
}

CJSCore::Init('kantryvet_fancybox');

// use tabs?
$bUseDetailTabs = $arParams['USE_DETAIL_TABS'] === 'Y';

// blocks order
if(
	!$bUseDetailTabs &&
	array_key_exists('DETAIL_BLOCKS_ALL_ORDER', $arParams) &&
	$arParams["DETAIL_BLOCKS_ALL_ORDER"]
){
	$arBlockOrder = explode(",", $arParams["DETAIL_BLOCKS_ALL_ORDER"]);
}
else{
	$arBlockOrder = explode(",", $arParams["DETAIL_BLOCKS_ORDER"]);
	$arTabOrder = explode(",", $arParams["DETAIL_BLOCKS_TAB_ORDER"]);
}
?>
<div class="staff-epilog">
	<?foreach($arBlockOrder as $blockCode):?>
		<?include 'epilog_blocks/'.$blockCode.'.php';?>
	<?endforeach;?>
</div>
