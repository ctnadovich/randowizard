<div id="<?= $tab_id ?? 'other-tab' ?>" 
    class='tab-container w3-card w3-margin w3-padding w3-leftbar' 
    style='display: <?= empty($default_tab) ? 'none' : 'block' ?>'>
    <H3><?= $panel_title ?></h3>
    <div class='w3-panel'>
        <?= $panel_data ?>
    </div>
</div>