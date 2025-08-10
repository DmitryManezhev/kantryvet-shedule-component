<?
use \Bitrix\Main\Localization\Loc;

$bTab = isset($tabCode) && $tabCode === 'desc';
?>
<?//show desc block?>
<?if($templateData['DETAIL_TEXT']):?>
    <?if($bTab):?>
        <?if(!isset($bShow_desc)):?>
            <?$bShow_desc = true;?>
        <?else:?>
            <div class="tab-pane <?=(!($iTab++) ? 'active' : '')?>" id="desc">
                <?$APPLICATION->ShowViewContent('STAFF_DETAIL_TEXT_INFO')?>
            </div>
        <?endif;?>
    <?else:?>
        <div class="detail-block ordered-block desc">
            <?$APPLICATION->ShowViewContent('STAFF_DETAIL_TEXT_INFO')?>
        </div>
    <?endif;?>
<?endif;?>