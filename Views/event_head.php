
<h2><?= $title ?> <A class='w3-button' TITLE='Rider Status'
HREF=<?=site_url("roster_info/$event_code")?>><i class="fa-solid fa-users"></i></A></h2>
<?php if(!empty($status_text)) echo "<h2 class='w3-text-orange'>$status_text</h2>";?>
<div class='w3-panel w3-margin'><?= $event_description ?></div>

