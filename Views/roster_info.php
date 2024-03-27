<div class="w3-container">
    <h2><?= $title ?> <A class='w3-button' 
    HREF=<?=site_url("event_info/$event_code")?>><i class="fa-solid fa-circle-info"></i></A></h2>
    <div class="w3-container w3-padding w3-responsive" style="clear: right;">
        <?= $roster_table ?>
    </div>
</div>