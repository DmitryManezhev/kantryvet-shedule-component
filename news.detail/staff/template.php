<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);

use \Bitrix\Main\Localization\Loc;

global $arTheme, $APPLICATION;

$templateData = array_filter([
	'ADDRESS' => $arResult['ADDRESS'],
	'REVIEWS' => TSolution\Functions::getCrossLinkedItems($arResult, array('LINK_REVIEWS'), array('LINK_STAFF'), $arParams),
	'SERVICES' => TSolution\Functions::getCrossLinkedItems($arResult, array('LINK_SERVICES'), array('LINK_STAFF'), $arParams),
	'GOODS' => TSolution\Functions::getCrossLinkedItems($arResult, array('LINK_GOODS', 'LINK_GOODS_FILTER')),
	'SCHEDULE' => $arParams['EMPTY_CHART'] !== 'Y',
]);

$bOnlineButton = $arParams['HIDE_RECORD_BUTTON'] !== 'Y' && (!isset($arResult['PROPERTIES']['FORM_RECORD']) || $arResult['PROPERTIES']['FORM_RECORD']['VALUE_XML_ID'] !== 'YES');
$bShowMap = false;
?>
<div class="staff-detail">
	<div class="staff-detail__card <?= $arResult['IMAGE'] ? 'staff-detail__card--with-picture' : '' ?>">
		<div class="staff-detail__card-info">
			<div class="staff-detail__card-row staff-detail__card-row--border-bottom staff-detail__top-wrapper">
				<div class="staff-detail__line staff-detail__line--between">
					<div class="staff-detail__name-wrapper">
						<? if ($arResult['PROPERTIES']['POST']['VALUE']) : ?>
							<div class="staff-detail__label">
								<?= Loc::getMessage('STAFF_DETAIL__LABEL__POST') ?>
							</div>
							<div class="staff-detail__post">
								<?= $arResult['PROPERTIES']['POST']['VALUE'] ?>
							</div>
						<? endif ?>
					</div>
					<? if ($bOnlineButton) : ?>
						<div>
							<div class="staff-detail__feedback">
								<div class="btn btn-default animate-load" data-event="jqm" data-param-id="<?=TSolution::getFormID('online');?>" data-autoload-specialist="<?=TSolution::formatJsName($arResult['ID'])?>"><span><?=(strlen($arTheme['EXPRESSION_FOR_ONLINE_RECORD']['VALUE']) ? $arTheme['EXPRESSION_FOR_ONLINE_RECORD']['VALUE'] : Loc::getMessage('RECORD_ONLINE'))?></span></div>
							</div>
						</div>
					<? endif ?>
				</div>
				<div class="staff-detail__properties-wrapper">
					<div class="staff-detail__properties  line-block line-block--40">
						<? if ($arResult['CONTACT_PROPERTIES']) : ?>
							<? foreach ($arResult['CONTACT_PROPERTIES'] as $property) : ?>
								<div class="staff-detail__property  line-block__item">
									<div class="staff-detail__property-label">
										<?= $property['NAME'] ?>
									</div>
									<div class="staff-detail__property-value">
										<? if ($property['TYPE'] == 'LINK') : ?>
											<a rel="nofollow" href="<?= $property['HREF'] ?>"
											   class="dark_link">
												<?= $property['VALUE'] ?>
											</a>
										<? else : ?>
											<?= $property['VALUE'] ?>
										<? endif ?>
									</div>
								</div>
							<? endforeach; ?>
						<? endif ?>
						<? if ($arResult['SOCIAL_PROPERTIES']) : ?>
							<div class="staff-detail__property staff-detail__property--socials  line-block__item">
								<div class="staff-detail__property-value">
									<div class="social__items">
										<? foreach ($arResult['SOCIAL_PROPERTIES'] as $social): ?>
											<div class="social__item">
												<a class="social__link fill-theme-hover banner-light-icon-fill"
												   rel="nofollow" href="<?= $social['VALUE'] ?>">
													<?= TSolution::showIconSvg('', $social['PATH']); ?>
												</a>
											</div>
										<? endforeach; ?>
									</div>
								</div>
							</div>
						<? endif ?>
					</div>
				</div>
			</div>
			<div class="staff-detail__card-row staff-detail__bottom-wrapper">
				<?if($arResult['DISPLAY_PROPERTIES_TOP'] || ($templateData['ADDRESS'] && !$templateData['SCHEDULE'])):?>
					<div class="staff-detail__top-properties">
						<?if($arResult['DISPLAY_PROPERTIES_TOP']):?>
							<?foreach($arResult['DISPLAY_PROPERTIES_TOP'] as $arProp):?>
								<?if($arProp['VALUE']):?>
									<div class="staff-detail__top-property">
										<div class="staff-detail__top-property-label"><?=$arProp['NAME']?></div>
										<?if(is_array($arProp["DISPLAY_VALUE"]) && count($arProp["DISPLAY_VALUE"]) > 1):?>
											<?=implode(', ', $arProp["DISPLAY_VALUE"]);?>
										<?else:?>
											<?=$arProp["DISPLAY_VALUE"];?>
										<?endif;?>										
									</div>
								<?endif;?>
							<?endforeach;?>
						<?endif;?>
						<?if($templateData['ADDRESS'] && !$templateData['SCHEDULE']):?>
							<div class="staff-detail__top-property staff-detail__top-property--address">
								<div class="staff-detail__top-property-label"><?=Loc::getMessage('T_ADDRESS_TITLE')?></div>
								<div class="staff-detail__top-property__addresses line-block line-block--40">
									<?foreach ($templateData['ADDRESS'] as $id => $address) {?>
										<div class="staff-detail__top-property__address line-block__item">
											<div class="staff-detail__top-property-value"><?=($address['PROPERTIES']['ADDRESS']['~VALUE'] ?? $address['NAME'])?></div>
											<?if(strlen($address['PROPERTIES']['MAP']['VALUE'])):?>
												<?$bShowMap = true;?>
												<div class="staff-detail__top-property__address-coord show_on_map">
													<span class="text_wrap font_14 color-theme" data-coordinates="<?=$address['PROPERTIES']['MAP']['VALUE']?>" data-scale="17">
														<?=TSolution::showIconSvg('on_map fill-theme', SITE_TEMPLATE_PATH.'/images/svg/show_on_map.svg');?>
														<span class="text dotted"><?=GetMessage('T_ON_MAP')?></span>
													</span>
												</div>
											<?endif;?>
										</div>
									<?}?>
								</div>
							</div>
						<?endif?>
					</div>
				<?endif;?>
			</div>
		</div>
		<? if($arResult['IMAGE']) : ?>
			<div class="staff-detail__card-image">
				<div class="staff-detail__image-wrapper">
					<div class="staff-detail__image">
					<span class="staff-detail__image-bg" title="<?= htmlspecialchars($arResult['IMAGE']['TITLE']) ?>"
						style="background-image: url(<?= $arResult['IMAGE']['PREVIEW_SRC'] ?>);"></span>
					</div>
				</div>
			</div>
		<? endif ?>
	</div>
	<div class="staff-detail__map-wrapper"></div>
</div>

<?// detail description?>
<?
$bDetailText = !!strlen($arResult['DETAIL_TEXT']);
$bPreviewText = !!strlen($arResult['FIELDS']['PREVIEW_TEXT']);
$bCharacteristics = !empty($arResult["CHARACTERISTICS"]);

$templateData['DETAIL_TEXT'] = $bDetailText || $bPreviewText || $bCharacteristics;
?>
<?if ($templateData['DETAIL_TEXT']):?>
	<?$this->SetViewTarget('STAFF_DETAIL_TEXT_INFO');?>
		<div class="content" itemprop="description">
			<?if ($bCharacteristics):?>
				<div class="staff-detail__ex-properties">
					<?foreach($arResult["CHARACTERISTICS"] as $arProp):?>
						<div class="staff-detail__ex-property">
							<div class="staff-detail__ex-property-label color_333 <?if($arProp["HINT"] && $arParams["SHOW_HINTS"] == "Y"){?>whint<?}?>">
								<span><?=$arProp["NAME"]?></span>
								<?if($arProp["HINT"] && $arParams["SHOW_HINTS"]=="Y"):?><div class="hint hint--down"><span class="hint__icon rounded bg-theme-hover border-theme-hover bordered"><i>?</i></span><div class="tooltip"><?=$arProp["HINT"]?></div></div><?endif;?>
							</div>
						
							<div class="staff-detail__ex-property-value">
								<?if(is_array($arProp["DISPLAY_VALUE"]) && count($arProp["DISPLAY_VALUE"]) > 1):?>
									<?=implode(', ', $arProp["DISPLAY_VALUE"]);?>
								<?else:?>
									<?=$arProp["DISPLAY_VALUE"];?>
								<?endif;?>
							</div>
						</div>
					<?endforeach;?>
				</div>
			<?endif;?>

			<?if ($bPreviewText):?>
				<div class="introtext">
					<?if($arResult['PREVIEW_TEXT_TYPE'] == 'text'):?>
						<p><?=$arResult['FIELDS']['PREVIEW_TEXT'];?></p>
					<?else:?>
						<?=$arResult['FIELDS']['PREVIEW_TEXT'];?>
					<?endif;?>
				</div>
			<?endif?>
			<?if ($bDetailText):?>
				<?=$arResult['DETAIL_TEXT'];?>
			<?endif;?>
		</div>
	<?$this->EndViewTarget();?>
<?endif;?>

<?// files?>
<?$templateData['DOCUMENTS'] = boolval($arResult['DOCUMENTS']);?>
<?if($templateData['DOCUMENTS']):?>
	<?$this->SetViewTarget('STAFF_FILES_INFO');?>
	<div class="doc-list-inner__list  grid-list  grid-list--items-1 grid-list--no-gap ">
			<?foreach($arResult['DOCUMENTS'] as $arItem):?>
				<?
				$arDocFile = TSolution::GetFileInfo($arItem);
				$docFileDescr = $arDocFile['DESCRIPTION'];
				$docFileSize = $arDocFile['FILE_SIZE_FORMAT'];
				$docFileType = $arDocFile['TYPE'];
				$bDocImage = false;
				if ($docFileType == 'jpg' || $docFileType == 'jpeg' || $docFileType == 'bmp' || $docFileType == 'gif' || $docFileType == 'png') {
					$bDocImage = true;
				}
				?>
				<div class="doc-list-inner__wrapper grid-list__item colored_theme_hover_bg-block grid-list-border-outer fill-theme-parent-all">
					<div class="doc-list-inner__item height-100 rounded-4 shadow-hovered shadow-no-border-hovered">
						<?if($arDocFile):?>
							<div class="doc-list-inner__icon-wrapper">
								<a class="file-type doc-list-inner__icon">
									<i class="file-type__icon file-type__icon--<?=$docFileType?>"></i>
								</a>
							</div>
						<?endif;?>
						<div class="doc-list-inner__content-wrapper">
							<div class="doc-list-inner__top">
								<?if($arDocFile):?>
									<?if($bDocImage):?>
										<a href="<?=$arDocFile['SRC']?>" class="doc-list-inner__name fancy dark_link color-theme-target switcher-title" data-caption="<?=htmlspecialchars($docFileDescr)?>"><?=$docFileDescr?></a>
									<?else:?>
										<a href="<?=$arDocFile['SRC']?>" target="_blank" class="doc-list-inner__name dark_link color-theme-target switcher-title" title="<?=htmlspecialchars($docFileDescr)?>">
											<?=$docFileDescr?>
										</a>
									<?endif;?>
									<div class="doc-list-inner__label"><?=$docFileSize?></div>
								<?else:?>
									<div class="doc-list-inner__name switcher-title"><?=$docFileDescr?></div>
								<?endif;?>
								<?if($arDocFile):?>
									<?if($bDocImage):?>
										<a class="doc-list-inner__icon-preview-image doc-list-inner__link-file fancy fill-theme-parent" data-caption="<?= htmlspecialchars($docFileDescr)?>" href="<?=$arDocFile['SRC']?>">
											<?=TSolution::showIconSvg('image-preview fill-theme-target', SITE_TEMPLATE_PATH.'/images/svg/preview_image.svg');?>
										</a>
									<?else:?>
										<a class="doc-list-inner__icon-preview-image doc-list-inner__link-file fill-theme-parent" target="_blank" href="<?=$arDocFile['SRC']?>">
											<?=TSolution::showIconSvg('image-preview fill-theme-target', SITE_TEMPLATE_PATH.'/images/svg/file_download.svg');?>
										</a>
									<?endif;?>
								<?endif;?>
							</div>
						</div>
					</div>
				</div>
			<?endforeach;?>
		</div>
	<?$this->EndViewTarget();?>
<?endif;?>