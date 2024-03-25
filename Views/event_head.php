<h2><?= $title ?>&nbsp;
<A class='w3-button' TITLE='Rider Status' HREF=<?= site_url("roster_info/$event_code") ?>><i class="fa-solid fa-users"></i></A>
<A class='w3-button' TITLE='Check Ins' HREF=<?= site_url("checkin_status/$event_code") ?>><i class="fa-solid fa-list-check"></i></A>
</h2>
<?php if (!empty($status_text)) echo "<h2 class='w3-text-orange'>$status_text</h2>"; ?>
<div class='w3-panel w3-margin'><?= $event_description ?></div>