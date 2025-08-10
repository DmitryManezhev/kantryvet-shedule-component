<?php

use \Bitrix\Main\Localization\Loc;

?>
<?if($arParams['DETAIL_USE_COMMENTS'] == 'Y' && $arParams['DETAIL_BLOG_USE'] == 'Y'):?>
	<?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/js/rating_likes.js");?>
	<?ob_start();?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:catalog.comments",
		"main",
		array(
			'CACHE_TYPE' => $arParams['CACHE_TYPE'],
			'CACHE_TIME' => $arParams['CACHE_TIME'],
			'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
			"COMMENTS_COUNT" => (isset($arParams["MESSAGES_PER_PAGE"]) ? $arParams["MESSAGES_PER_PAGE"] : $arParams['COMMENTS_COUNT']),
			"ELEMENT_CODE" => "",
			"ELEMENT_ID" => $arResult["ID"],
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"IBLOCK_TYPE" => "kantryvet_kantryvetmed_catalog",
			"SHOW_DEACTIVATED" => "N",
			"TEMPLATE_THEME" => "blue",
			"URL_TO_COMMENT" => "",
			"AJAX_POST" => "Y",
			"WIDTH" => "",
			"COMPONENT_TEMPLATE" => ".default",
			"BLOG_USE" => $arParams["DETAIL_BLOG_USE"],
			"BLOG_TITLE" => $arParams["DETAIL_BLOG_TITLE"],
			"BLOG_URL" => $arParams["DETAIL_BLOG_URL"],
			"PATH_TO_SMILE" => '/bitrix/images/blog/smile/',
			"EMAIL_NOTIFY" => $arParams["DETAIL_BLOG_EMAIL_NOTIFY"],
			"SHOW_SPAM" => "Y",
			"SHOW_RATING" => "Y",
			"VK_USE" => $arParams["DETAIL_VK_USE"],
			"VK_TITLE" => $arParams["DETAIL_VK_TITLE"],
			"VK_API_ID" => $arParams["DETAIL_VK_API_ID"],
			"FB_USE" => $arParams["DETAIL_BLOG_USE"],
			"FB_ORDER_BY" => "reverse_time",
			"FB_APP_ID" => $arParams["DETAIL_FB_APP_ID"],
			"FB_TITLE" => $arParams["DETAIL_FB_TITLE"],
			"FB_USER_ADMIN_ID" => "",
			"RATING_TYPE" => "like_graphic",
			"FB_COLORSCHEME" => "light",
		),
		false, array("HIDE_ICONS" => "Y")
	);?>
	<?$html = trim(ob_get_clean());?>
	<? if ($html && strpos($html, 'error') === false): ?>
		<div class="detail-block ordered-block">
			<div class="ordered-block__title switcher-title font_22"><?= $arParams['T_COMMENTS'] ?: Loc::getMessage('EPILOG_BLOCK__COMMENTS') ?></div>
			<?= $html ?>
		</div>
	<? endif; ?>
<?endif;?>