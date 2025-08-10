<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);
Loc::loadMessages(__FILE__);

global $APPLICATION;

$listClasses = 'grid-list--gap-32';
$templateData = ['SCHEDULE' => true];
?>

<div class="doctor-schedule-list-component" id="<?=$arResult["AJAX_ID"]?>">
    <?php if ($arResult['SECTIONS']): ?>
        <div class="doctor-schedule-wrapper">
            <div class="row">

                <!-- Левая панель – список врачей -->
                <div class="col-lg-4 col-md-5 col-sm-12">
                    <div class="staff-list-inner">
                        <div class="staff-list-inner__section-content">
                            <div class="staff-list-inner__section-title switcher-title">
                                <?=Loc::getMessage("DOCTOR_SCHEDULE_LIST_DOCTORS_TITLE")?>
                            </div>

                            <!-- Фильтры -->
                            <div class="doctors-filters" style="margin-bottom: 20px;">

                                <!-- Поиск -->
                                <?php if ($arParams["SHOW_SEARCH"] == "Y"):?>
                                    <div class="doctors-search" style="margin-bottom: 15px; position: relative;">
                                        <input type="text"
                                               class="form-control doctors-search__input"
                                               placeholder="<?=Loc::getMessage("DOCTOR_SCHEDULE_LIST_SEARCH_PLACEHOLDER")?>"
                                               id="doctorSearch"
                                               style="padding-left: 35px;">
                                        <i class="fa fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6c757d;"></i>
                                    </div>
                                <?php endif;?>

                                <!-- Фильтр по услугам -->
                                <?php if ($arParams["SHOW_SERVICES_FILTER"] == "Y" && !empty($arResult["SERVICES"])):?>
                                    <div class="doctors-services-filter" style="margin-bottom: 15px;">
                                        <select class="form-control doctors-services-filter__select" id="servicesFilter">
                                            <option value=""><?=Loc::getMessage("DOCTOR_SCHEDULE_LIST_ALL_SERVICES")?></option>
                                            <?php foreach ($arResult['SERVICES'] as $serviceId => $service): ?>
                                                <option value="<?=htmlspecialcharsbx($serviceId)?>">
                                                    <?=htmlspecialcharsbx($service['NAME'])?>
                                                </option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                <?php endif;?>

                                <!-- Фильтр по филиалам -->
                                <?php if ($arParams["SHOW_SHOPS_FILTER"] == "Y" && !empty($arResult["SHOPS"])):?>
                                    <div class="doctors-shops-filter">
                                        <select class="form-control doctors-shops-filter__select" id="shopsFilter">
                                            <option value=""><?=Loc::getMessage("DOCTOR_SCHEDULE_LIST_ALL_SHOPS")?></option>
                                            <?php foreach ($arResult["SHOPS"] as $shopId => $shop):?>
                                                <option value="<?=$shopId?>" <?=($arParams["DEFAULT_SHOP_ID"] == $shopId ? "selected" : "")?>>
                                                    <?=htmlspecialcharsbx($shop["NAME"])?>
                                                </option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                <?php endif;?>

                            </div>
                        </div>

                        <!-- Список врачей -->
                        <div class="staff-list-inner__sections">
                            <?php foreach ($arResult['SECTIONS'] as $arSection): ?>
                                <div class="staff-list-inner__section">
                                    <?php if ($arSection['NAME'] && $arParams['SHOW_SECTION_NAME'] != 'N'): ?>
                                        <div class="staff-list-inner__section-content">
                                            <div class="staff-list-inner__section-title switcher-title">
                                                <?= $arSection['NAME'] ?>
                                            </div>
                                            <?php if ($arSection['DESCRIPTION']): ?>
                                                <div class="staff-list-inner__section-description">
                                                    <?= $arSection['DESCRIPTION'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="staff-list-inner__list staff-list-inner__list--items-1 grid-list grid-list--items-1 <?= $listClasses ?>">
                                        <?php foreach ($arSection['ITEMS'] as $arItem): ?>
                                            <?php
                                            $previewImageSrc = $arItem['PHOTO'] ?: SITE_TEMPLATE_PATH . '/images/svg/noimage_staff.svg';
                                            $bDetailLink   = $arParams['SHOW_DETAIL_LINK'] != 'N';
                                            $bOnlineButton = $arParams['HIDE_RECORD_BUTTON'] !== 'Y';

                                            // Правильно формируем данные для фильтрации
                                            $servicesIds = !empty($arItem['SERVICES']) ? implode(',', $arItem['SERVICES']) : '';
                                            $shopsIds    = !empty($arItem['SHOPS']) ? implode(',', $arItem['SHOPS']) : '';
                                            ?>
                                            <div class="staff-list-inner__wrapper grid-list__item stroke-theme-parent-all colored_theme_hover_bg-block animate-arrow-hover grid-list-border-outer doctor-item"
                                                 data-doctor-id="<?=$arItem['ID']?>"
                                                 data-services="<?=htmlspecialcharsbx($servicesIds)?>"
                                                 data-shops="<?=htmlspecialcharsbx($shopsIds)?>"
                                                 data-name="<?=htmlspecialcharsbx($arItem['NAME'])?>"
                                                 style="cursor: pointer;">
                                                <div class="staff-list-inner__item height-100 rounded-4 shadow-hovered shadow-no-border-hovered">

                                                    <?php if ($arParams['SHOW_PHOTO'] == 'Y' && $arItem['PHOTO']): ?>
                                                        <div class="staff-list-inner__image-wrapper">
                                                            <?php if ($bDetailLink): ?>
                                                                <a class="staff-list-inner__image" href="<?=$arItem['DETAIL_PAGE_URL']?>">
                                                                    <span class="staff-list-inner__image-bg rounded" style="background-image:url(<?=$previewImageSrc?>);"></span>
                                                                </a>
                                                            <?php else: ?>
                                                                <div class="staff-list-inner__image">
                                                                    <span class="staff-list-inner__image-bg rounded" style="background-image:url(<?=$previewImageSrc?>);"></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="staff-list-inner__content-wrapper">
                                                        <div class="staff-list-inner__top">
                                                            <div class="staff-list-inner__label">
                                                                <?= $arItem['PROPERTY_POST_VALUE'] ?: $arItem['SERVICES_STR'] ?>
                                                            </div>

                                                            <?php if ($bDetailLink): ?>
                                                                <a class="staff-list-inner__name dark_link color-theme-target switcher-title" href="<?=$arItem['DETAIL_PAGE_URL']?>">
                                                                    <?=$arItem['NAME']?>
                                                                </a>
                                                            <?php else: ?>
                                                                <div class="staff-list-inner__name switcher-title">
                                                                    <?=$arItem['NAME']?>
                                                                </div>
                                                            <?php endif; ?>

                                                            <?php if (isset($arItem['SHOP_INFO'])): ?>
                                                                <div class="staff-list-inner__shop" style="font-size:12px;color:#6c757d;margin-top:4px;">
                                                                    <i class="fa fa-map-marker" style="margin-right:4px;"></i>
                                                                    <?= $arItem['SHOP_INFO']['NAME'] ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="staff-list-inner__bottom">
                                                            <div class="staff-list-inner__properties line-block line-block--40">
                                                                <?php if ($bOnlineButton): ?>
                                                                    <div class="staff-list-inner__property staff-list-inner__property--feedback line-block__item">
                                                                        <div class="staff-list-inner__feedback">
                                                                            <div class="btn btn-default btn-transparent-border animate-load btn-md"
                                                                                 data-event="jqm"
                                                                                 data-param-id="<?=TSolution::getFormID('online');?>"
                                                                                 data-autoload-specialist="<?=TSolution::formatJsName($arItem['ID'])?>">
                                                                                <span><?=Loc::getMessage('DOCTOR_SCHEDULE_LIST_RECORD_ONLINE')?></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <?php if ($arParams['SHOW_CONTACTS'] == 'Y' && !empty($arItem['CONTACT_PROPERTIES'])): ?>
                                                                    <?php foreach ($arItem['CONTACT_PROPERTIES'] as $property): ?>
                                                                        <div class="staff-list-inner__property line-block__item">
                                                                            <div class="staff-list-inner__property-label"><?=$property['NAME']?></div>
                                                                            <div class="staff-list-inner__property-value">
                                                                                <?php if ($property['TYPE'] == 'PHONE' || $property['TYPE'] == 'EMAIL'): ?>
                                                                                    <a rel="nofollow" href="<?=$property['HREF']?>" class="dark_link"><?=$property['VALUE']?></a>
                                                                                <?php else: ?>
                                                                                    <?=$property['VALUE']?>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>

                                                                <?php if (!empty($arItem['SOCIAL_PROPERTIES'])): ?>
                                                                    <div class="staff-list-inner__property staff-list-inner__property--socials line-block__item">
                                                                        <div class="staff-list-inner__socials">
                                                                            <div class="social__items">
                                                                                <?php foreach ($arItem['SOCIAL_PROPERTIES'] as $social): ?>
                                                                                    <div class="social__item">
                                                                                        <a class="social__link fill-theme-hover banner-light-icon-fill"
                                                                                           rel="nofollow" href="<?=$social['VALUE']?>"
                                                                                           title="<?=$social['NAME']?>">
                                                                                            <?=TSolution::showIconSvg('', $social['PATH']);?>
                                                                                        </a>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>

                                                            <?php if ($arItem['PREVIEW_TEXT']): ?>
                                                                <div class="staff-list-inner__description">
                                                                    <?php if ($arItem['PREVIEW_TEXT_TYPE'] == 'text'): ?>
                                                                        <p><?=$arItem['PREVIEW_TEXT']?></p>
                                                                    <?php else: ?>
                                                                        <?=$arItem['PREVIEW_TEXT']?>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Правая панель – расписание -->
                <div class="col-lg-8 col-md-7 col-sm-12">
                    <div class="schedule-panel">
                        <div class="schedule-panel__header">
                            <h3 class="schedule-panel__title">
                                <?=Loc::getMessage("DOCTOR_SCHEDULE_LIST_SCHEDULE_TITLE")?>
                            </h3>
                        </div>

                        <div class="schedule-content" id="scheduleContent">
                            <?php if ($templateData['SCHEDULE']): ?>
                                <div class="js-load-staff-schedule loading-state" data-site_id="<?=SITE_ID?>">
                                    <div class="schedule-placeholder">
                                        <div class="schedule-placeholder__icon">
                                            <i class="fa fa-calendar-alt" style="font-size: 48px; opacity: 0.5;"></i>
                                        </div>
                                        <div class="schedule-placeholder__text">
                                            <?=Loc::getMessage("DOCTOR_SCHEDULE_LIST_SELECT_DOCTOR")?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning"><?=Loc::getMessage("DOCTOR_SCHEDULE_LIST_NO_DOCTORS")?></div>
    <?php endif; ?>
</div>

<script>
BX.ready(function() {
    new DoctorScheduleListComponent({
        containerId  : '<?=$arResult["AJAX_ID"]?>',
        ajaxUrl      : '<?=$APPLICATION->GetCurPage()?>',
        useAjax      : <?=($arParams["USE_AJAX"] == "Y" ? "true" : "false")?>,
        componentPath: '<?=$arResult["COMPONENT_PATH"]?>',
        services     : <?=json_encode($arResult["SERVICES"])?>,
        shops        : <?=json_encode($arResult["SHOPS"])?>,
        siteId       : '<?=SITE_ID?>'
    }).init();
});
</script>

<?$APPLICATION->AddHeadString('<link rel="stylesheet" type="text/css" href="'.$this->GetFolder().'/style.css">');?>