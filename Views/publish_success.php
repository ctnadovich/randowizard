<H1>Route Published</H1>
<p>Route Data and Cue Sheets successfully published.
    <i>Don't refresh or reverse or you might accidentally publish again. 
        Just press one of the buttons below.</i></p>
<table class='w3-table-all'>
    <tr>
        <th>Event</th>
        <td><?= $event_name_dist ?></td>
    </tr>
    <tr>
        <th>Published Route Version</th>
        <td><?= $cue_version ?></td>
    </tr>
</table>
<ul>
    <li><A HREF="<?= $cue_url['L'] ?>">PDF File Landscape</A>
    <li><A HREF="<?= $cue_url['P'] ?>">PDF File Portrait</A>
    <li><A HREF="<?= $cue_url['C'] ?>">Unformatted CSV File</A>
</ul>

<div class="w3-bar">
    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF='<?= $route_manager_url ?>'>Return to Wizard for this Event</A>
    <A class="w3-bar-item w3-button w3-margin w3-teal" HREF='<?= $event_info_url ?>'>Go to Published Event Page</A>
</div>