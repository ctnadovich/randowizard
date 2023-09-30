<div class="w3-card w3-center w3-margin" style="float: right;">
    <A class='w3-container' HREF='<?= $website_url ?>'><img style="width:100%; height: auto; max-height:64px;" src='<?= $icon_url ?>'></a>
</div>
<h1><?=$club_name?></h1>
<h2><?="Region: $region_state_code: $region_name"?></h2>
<div class='w3-container w3-padding w3-margin'><?=$region_description?></div>
<div class='w3-card w3-padding w3-margin' style='clear: right;'>
    <h2>Events Underway</h2>
    <div class='w3-container w3-padding'>
            <?= $underway_events_table ?>
    </div>
</div>
<div class='w3-card w3-padding w3-margin' style='clear: right;'>
    <h2>Upcoming Events</h2>
    <div class='w3-container w3-padding'>
            <?= $future_events_table ?>
    </div>
</div>
<div class='w3-card w3-padding w3-margin' style='clear: right;'>
    <h2>Past Events</h2>
    <div class='w3-container w3-padding'>
            <?= $past_events_table ?>
    </div>
</div>