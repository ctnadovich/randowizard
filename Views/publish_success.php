
<H3>Cue Sheet Published</H3>
<p>Cue Sheets (version <?=$cue_version?>) successfully published to event database 
for <?=$event_name_dist?>.  Don't refresh or reverse or you might accidentally publish again. Just press one of the buttons below.</p>
<ul>
<li><A HREF="<?=$cue_url['L']?>">PDF File Landscape</A>
<li><A HREF="<?=$cue_url['P']?>">PDF File Portrait</A>
<li><A HREF="<?=$cue_url['C']?>">Unformatted CSV File</A>
</ul>
<div class="w3-bar">
<A class="w3-bar-item w3-button w3-margin w3-teal" HREF=$wizard_url?>>Return to Wizard for this Event</A>
<A class="w3-bar-item w3-button w3-margin w3-teal" HREF=$info_url?>>View Published Route</A>
<A class="w3-bar-item w3-button w3-margin w3-teal" HREF=$event_url?>>Go to Published Event Page</A>
</div>