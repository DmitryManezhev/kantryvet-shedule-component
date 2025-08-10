<?php
/* docs property code */
$docsProp = $arParams['DETAIL_DOCS_PROP'] ? $arParams['DETAIL_DOCS_PROP'] : 'DOCUMENTS';

if(
	array_key_exists($docsProp, $arResult["DISPLAY_PROPERTIES"]) &&
	is_array($arResult["DISPLAY_PROPERTIES"][$docsProp]) &&
	$arResult["DISPLAY_PROPERTIES"][$docsProp]["VALUE"]
){
	foreach($arResult['DISPLAY_PROPERTIES'][$docsProp]['VALUE'] as $key => $value){
		if(!intval($value)){
			unset($arResult['DISPLAY_PROPERTIES'][$docsProp]['VALUE'][$key]);
		}
	}

	if($arResult['DISPLAY_PROPERTIES'][$docsProp]['VALUE']){
		$arResult['DOCUMENTS'] = array_values($arResult['DISPLAY_PROPERTIES'][$docsProp]['VALUE']);
	}

	unset($arResult['DISPLAY_PROPERTIES'][$docsProp]);
}

$arResult['CONTACT_PROPERTIES'] = [];
foreach ($arResult['DISPLAY_PROPERTIES'] as $propertyCode => $property) {
	if ($propertyCode == 'PHONE' && $property['VALUE']) {
		$tel = $property['VALUE'] ? preg_replace('/[^\d]/', '', $property['VALUE']) : '';
		$arResult['CONTACT_PROPERTIES'][$propertyCode] = [
			'NAME' => $property['NAME'],
			'VALUE' => $property['VALUE'],
			'TYPE' => 'LINK',
			'HREF' => 'tel:+' . $tel,
			'SORT' => 100,
		];

		unset($arResult['DISPLAY_PROPERTIES'][$propertyCode]);
	}
	elseif ($propertyCode == 'EMAIL' && $property['VALUE']) {
		$mailto = $property['VALUE'];
		$arResult['CONTACT_PROPERTIES'][$propertyCode] = [
			'NAME' => $property['NAME'],
			'VALUE' => $property['VALUE'],
			'TYPE' => 'LINK',
			'HREF' => 'mailto:' . $mailto,
			'SORT' => 200,
		];

		unset($arResult['DISPLAY_PROPERTIES'][$propertyCode]);
	}
	elseif (strpos($propertyCode, 'SOCIAL') !== false && $property['VALUE']) {
		$socialCode = str_replace('SOCIAL_', '', $propertyCode);
		$arResult['SOCIAL_PROPERTIES'][$propertyCode] = [
			'VALUE' => $property['VALUE'],
			'CODE' => $socialCode,
			'PATH' => SITE_TEMPLATE_PATH . '/images/svg/social/' . $socialCode . '.svg',
		];

		unset($arResult['DISPLAY_PROPERTIES'][$propertyCode]);
	}
	elseif(in_array($propertyCode, array('QUALIFICATION', 'WORK'))){
		$arResult['DISPLAY_PROPERTIES_TOP'][$propertyCode] = $property;
		unset($arResult['DISPLAY_PROPERTIES'][$propertyCode]);
	}
	elseif($propertyCode === 'POST'){
		unset($arResult['DISPLAY_PROPERTIES'][$propertyCode]);
	}
	elseif($propertyCode === 'LINK_ADDRESS' && $property['VALUE']){
		$arResult['ADDRESS'] = [];

		$dbRes = CIBlockElement::GetList(array(), array('IBLOCK_ID' => CKantryvetmedCache::$arIBlocks[SITE_ID]['kantryvet_kantryvetmed_content']['kantryvet_kantryvetmed_contact'][0], 'ID' => $property['VALUE'], 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'DETAIL_PAGE_URL'));
		while($arRes = $dbRes->GetNextElement()){
			$fields = $arRes->GetFields();
			$arResult['ADDRESS'][$fields['ID']] = $fields;
			$arResult['ADDRESS'][$fields['ID']]['PROPERTIES'] = $arRes->GetProperties();
		}
		
		if($arResult['ADDRESS']['IBLOCK_SECTION_ID']){
			$arSectionAddress = CKantryvetmedCache::CIblockSection_GetList(array("CACHE" => array("TAG" => CKantryvetmedCache::$arIBlocks[SITE_ID]['kantryvet_kantryvetmed_content']['kantryvet_kantryvetmed_contact'][0]), 'MULTI' => 'N'), array('IBLOCK_ID' => CKantryvetmedCache::$arIBlocks[SITE_ID]['kantryvet_kantryvetmed_content']['kantryvet_kantryvetmed_contact'][0], 'ID' => $arResult['ADDRESS']['IBLOCK_SECTION_ID']));
			$arParentSectionAddress = CKantryvetmedCache::CIblockSection_GetList(array("CACHE" => array("TAG" => CKantryvetmedCache::$arIBlocks[SITE_ID]['kantryvet_kantryvetmed_content']['kantryvet_kantryvetmed_contact'][0]), 'MULTI' => 'N'), array('IBLOCK_ID' => CKantryvetmedCache::$arIBlocks[SITE_ID]['kantryvet_kantryvetmed_content']['kantryvet_kantryvetmed_contact'][0], "<=LEFT_BORDER" => $arSectionAddress[0]["LEFT_MARGIN"], ">=RIGHT_BORDER" => $arSectionAddress[0]["RIGHT_MARGIN"], "DEPTH_LEVEL" => 1), false, array('NAME'));
			
			$arResult['ADDRESS']['SECTION_NAME'] = $arParentSectionAddress[0]['NAME'];
		}

		unset($arResult['DISPLAY_PROPERTIES'][$propertyCode]);
	}
}

if ($arResult['CONTACT_PROPERTIES']) {
	usort($arResult['CONTACT_PROPERTIES'], function ($a, $b) {
		return ($a['SORT'] > $b['SORT']);
	});
}

if($arResult['DISPLAY_PROPERTIES']){
	$arResult['CHARACTERISTICS'] = CKantryvetmed::PrepareItemProps($arResult['DISPLAY_PROPERTIES']);
	$arResult["CHARACTERISTICS"] = array_filter($arResult['CHARACTERISTICS'], function($arProp) {
		return (is_array($arProp["DISPLAY_VALUE"]) && count($arProp["DISPLAY_VALUE"]) > 1) 
				|| (is_string($arProp["DISPLAY_VALUE"]) && strlen($arProp["DISPLAY_VALUE"]));
	});
	
}

$arResult['IMAGE'] = null;
if ($arParams['DISPLAY_PICTURE'] != "N") {
	$pictureField = ($arResult['FIELDS']['DETAIL_PICTURE'] ? 'DETAIL_PICTURE' : 'PREVIEW_PICTURE');
	CKantryvetmed::getFieldImageData($arResult, [$pictureField]);
	$picture = $arResult[$pictureField];
	$preview = CFile::ResizeImageGet($picture['ID'], ['width' => 500, 'height' => 500], BX_RESIZE_IMAGE_PROPORTIONAL_ALT, true);
	if ($picture) {
		$arResult['IMAGE'] = [
			'DETAIL_SRC' => $picture['SRC'],
			'PREVIEW_SRC' => $preview['src'],
			'TITLE' => (strlen($picture['DESCRIPTION']) ? $picture['DESCRIPTION'] : (strlen($picture['TITLE']) ? $picture['TITLE'] : $arResult['NAME'])),
			'ALT' => (strlen($picture['DESCRIPTION']) ? $picture['DESCRIPTION'] : (strlen($picture['ALT']) ? $picture['ALT'] : $arResult['NAME'])),
		];
	}
}
