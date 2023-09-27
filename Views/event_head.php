<div class="w3-card w3-center w3-margin" style="float: right;">
<A class='w3-container' HREF='<?=$website_url?>'><img style="width:100%; height: auto; max-height:64px;" src='<?= $icon_url ?>'></a>
</div>
<h1><?= $club_name ?></h1>
<h2><?= $title ?> <A class='w3-button' 
HREF=<?=site_url("roster_info/$event_code")?>><i class="fa-solid fa-users"></i></A></h2>
<?php if(!empty($status_text)) echo "<h2 class='w3-text-orange'>$status_text</h2>";?>
<p><?= $event_description ?></p>

