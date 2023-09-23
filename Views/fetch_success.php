<H1>Success</H1>
<p>Route data successfully fetched to local database </p>

<table class='w3-table-all'>
    <tr><th>Event</th><td><?= $event_name_dist ?></td></tr>
    <tr><th>Route</th><td><A HREF='<?= $route_url ?>'><?= $route_url ?></A></td></tr>
    <tr><th>Fetched At</th><td><?= $last_download ?></td></tr>
    <tr><th>Route Last Changed</th><td><?= $last_update ?></td></tr>
</table>

<div class="w3-bar">
    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF='<?=$route_manager_url?>'>Return to Wizard for this Event</A>
    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF='<?=$event_info_url?>'>Go to Published Event Page</A>
</div>