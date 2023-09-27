<div class="w3-container">

        <div class="w3-card w3-center w3-margin" style="width:15%; float: right;">
        <A class='w3-container' HREF='<?=$website_url?>'><img style="width:100%; height: auto; max-height:128px;" src='<?= $icon_url ?>'></a>
        </div>
    <h1><?= $club_name ?></h1>
    <h2><?= $title ?> <A class='w3-button' 
    HREF=<?=site_url("event_info/$event_code")?>><i class="fa-solid fa-circle-info"></i></A></h2>
    <div class="w3-container w3-padding w3-responsive" style="clear: right;">

        <TABLE CLASS='w3-table-all w3-centered'><?= $checkin_table ?></TABLE>
    </div>
</div>