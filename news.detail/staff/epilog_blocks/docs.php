<?php
use \Bitrix\Main\Localization\Loc;

$bTab = isset($tabCode) && $tabCode === 'docs';
?>
<?//show docs block?>
<?if($templateData['DOCUMENTS']):?>
    <?if($bTab):?>
        <?if(!isset($bShow_docs)):?>
            <?$bShow_docs = true;?>
        <?else:?>
            <div class="tab-pane <?=(!($iTab++) ? 'active' : '')?>" id="docs">
                <div class="ordered-block__title switcher-title font_22"><?=$arParams["T_DOCS"]?></div>
                <?$APPLICATION->ShowViewContent('STAFF_FILES_INFO')?>
            </div>
        <?endif;?>
    <?else:?>
        <div class="detail-block ordered-block docs">
            <div class="ordered-block__title switcher-title font_22"><?=$arParams["T_DOCS"]?></div>
            <?$APPLICATION->ShowViewContent('STAFF_FILES_INFO')?>
        </div>
    <?endif;?>
<?endif;?>