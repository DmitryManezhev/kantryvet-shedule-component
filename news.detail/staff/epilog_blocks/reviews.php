<?php

use \Bitrix\Main\Localization\Loc;

$bTab = isset($tabCode) && $tabCode === 'reviews';

$bReviewsList = $templateData['REVIEWS'] && $templateData['REVIEWS']['IBLOCK_ID'] && $templateData['REVIEWS']['VALUE'];
$bShowAddReviewButton = $arParams['SHOW_BLOCK_REVIEWS'] !== 'N';
?>
<?if ($bReviewsList || $bShowAddReviewButton):?>
	<?ob_start();?>
		<?if ($bShowAddReviewButton):?>
			<?
			TSolution\Functions::showBlockHtml([
				'FILE' => 'review/review_button.php',
				'PARAMS' => [
					'FORM_ID' => 'feedback',
					'BUTTON_TITLE' => Loc::getMessage('REVIEWS__BTN__SEND'),
					'DESCRIPTION' => Loc::getMessage('REVIEWS__DESCRIPTION'),
					'STAFF_ID' => $arResult['ID'],
				],
			]);
			?>
		<?endif;?>
	
		<?if ($bReviewsList):?>
			<?
			$GLOBALS['arrReviewsFilter'] = ['ID' =>  $templateData['REVIEWS']['VALUE']];
			$GLOBALS['arrReviewsFilter'] = array_merge($GLOBALS['arrReviewsFilter'], (array)$GLOBALS['arRegionLink']);

			$bCheckAjaxBlock = CKantryvetmed::checkRequestBlock("reviews-list-inner");
			?>
			<?$APPLICATION->IncludeComponent(
				"bitrix:news.list",
				"reviews-list",
				array(
					"IBLOCK_TYPE" => "kantryvet_kantryvetmed_content",
					"IBLOCK_ID" => $templateData['REVIEWS']['IBLOCK_ID'],
					"NEWS_COUNT" => "20",
					"SORT_BY1" => "ACTIVE_FROM",
					"SORT_ORDER1" => "DESC",
					"SORT_BY2" => "SORT",
					"SORT_ORDER2" => "ASC",
					"FILTER_NAME" => "arrReviewsFilter",
					"FIELD_CODE" => array(
						0 => "NAME",
						1 => "PREVIEW_TEXT",
						2 => "PREVIEW_PICTURE",
						3 => "DETAIL_PICTURE",
						4 => "",
					),
					"PROPERTY_CODE" => array(
						0 => "POST",
						1 => "RATING",
						2 => "",
					),
					"CHECK_DATES" => "Y",
					"DETAIL_URL" => "",
					"AJAX_MODE" => "N",
					"AJAX_OPTION_JUMP" => "N",
					"AJAX_OPTION_STYLE" => "Y",
					"AJAX_OPTION_HISTORY" => "N",
					"CACHE_TYPE" => "A",
					"CACHE_TIME" => "36000000",
					"CACHE_FILTER" => "Y",
					"CACHE_GROUPS" => "N",
					"PREVIEW_TRUNCATE_LEN" => "230",
					"ACTIVE_DATE_FORMAT" => "d.m.Y",
					"SET_TITLE" => "N",
					"SET_STATUS_404" => "N",
					"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
					"ADD_SECTIONS_CHAIN" => "N",
					"HIDE_LINK_WHEN_NO_DETAIL" => "N",
					"PARENT_SECTION" => "",
					"PARENT_SECTION_CODE" => "",
					"INCLUDE_SUBSECTIONS" => "Y",
					"PAGER_TEMPLATE" => ".default",
					"DISPLAY_TOP_PAGER" => "N",
					"DISPLAY_BOTTOM_PAGER" => "N",
					"PAGER_TITLE" => "",
					"PAGER_SHOW_ALWAYS" => "N",
					"PAGER_DESC_NUMBERING" => "N",
					"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
					"PAGER_SHOW_ALL" => "N",
					"SHOW_TABS" => "N",
					"SHOW_IMAGE" => "Y",
					"SHOW_NAME" => "Y",
					"SHOW_DETAIL" => "Y",
					"COUNT_IN_LINE" => "3",

					"NAME_LARGE" => false,
					"MORE_BTN_CLASS" => "btn-md",
					"ROW_VIEW" => true,
					"BORDER" => true,
					"ITEM_HOVER_SHADOW" => true,
					"DARK_HOVER" => false,
					"ROUNDED" => true,
					"ROUNDED_IMAGE" => true,
					"ITEM_PADDING" => "48",
					"ELEMENTS_ROW" => 1,
					"MAXWIDTH_WRAP" => false,
					"MOBILE_SCROLLED" => false,
					"NARROW" => true,
					"ITEMS_OFFSET" => "32",
					"IMAGE" => true,
					"IMAGE_SIZE" => "small",
					"RATING_RIGHT" => true,
					"ITEM_TOP_PART_ROW" => true,
					"ITEMS_COUNT_SLIDER" => "1",
					"SLIDER_BUTTONS_BORDERED" => false,
					"LOGO_CENTER" => true,
					"TOP_PART_COLUMN" => false,
					"SHOW_PREVIEW" => true,
					"SHOW_TITLE" => false,
					"SHOW_SECTION" => "Y",
					"TITLE_POSITION" => "",
					"TITLE" => "",
					"RIGHT_TITLE" => "",
					"RIGHT_LINK" => "",
					"CHECK_REQUEST_BLOCK" => $bCheckAjaxBlock,
					"IS_AJAX" => CKantryvetmed::checkAjaxRequest() && $bCheckAjaxBlock,
					"FRONT_PAGE" => false,
					"NAME_SIZE" => "18",
					"SUBTITLE" => "",
					"SHOW_PREVIEW_TEXT" => "N",
				),
				false, array("HIDE_ICONS" => "Y")
			);?>
		<?endif;?>
	<?$html_reviews = trim(ob_get_clean());?>
	<?if ($html_reviews && strpos($html_reviews, 'error') === false):?>
		<?if ($bTab):?>
			<?if(!isset($bShow_reviews)):?>
				<?$bShow_reviews = true;?>
			<?else:?>
				<div class="tab-pane <?=(!($iTab++) ? 'active' : '')?>" id="reviews">
					<div class="ordered-block__title switcher-title font_22"><?=$arParams["T_REVIEWS"] ?: Loc::getMessage('EPILOG_BLOCK__REVIEWS');?></div>
					<?=$html_reviews;?>
				</div>
			<?endif;?>
		<?else:?>
			<div class="detail-block ordered-block">
				<div class="ordered-block__title switcher-title font_22"><?=$arParams['T_REVIEWS'] ?: Loc::getMessage('EPILOG_BLOCK__REVIEWS');?></div>
				<?=$html_reviews;?>
			</div>
		<?endif;?>
	<?endif;?>
<?endif;?>