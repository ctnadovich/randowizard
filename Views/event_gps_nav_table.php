<h4> Official Route Data </h4>
<?php if ($has_cuesheet === true) : ?>

    <div class='w3-container'>
        <p>Official published route data in the form of a Cue Sheet, is available for this event.</p>
        <TABLE class='w3-table-all'>
            <TR>
                <TD>Route Published</TD>
                <TD><?= $published_at_str ?></TD>
            </TR>
            <TR>
                <TD>Cuesheet Version</TD>
                <TD><?= $cue_version ?></TD>
            </TR>
        </TABLE>
    </DIV>
    <div class='w3-bar'>
        <A HREF='<?= $cue_url['P'] ?>' CLASS='w3-button w3-bar-item w3-margin w3-teal'>Cue Sheet (Portrait)</A>
        <A HREF='<?= $cue_url['L'] ?>' CLASS='w3-button w3-bar-item w3-margin w3-teal'>Cue Sheet (Landscape)</A>
        <A HREF='<?= $cue_url['C'] ?>' CLASS='w3-button w3-bar-item w3-margin w3-teal'> Cue Sheet (CSV)</A>
    </div>

<?php else : ?>

    <div class='w3-container w3-pale-red'>
        <p>At this time no cue sheet has been published for this event. Any GPS route information is PRELIMINARY.</p>
    </div>

<?php endif; ?>

<div class='w3-panel w3-sand' style='font-size: .5em;'>
    <p>
        Unlike other bicycling events,
        randonneuring routes are not marked with painted or posted
            arrows or other signage beyond pre-existing road and traffic signs.
        Instead, the route is defined by a map accompanied by distances, turns, and detailed cue
        notes. This information comprises the official directions for the route. We have
        done our best to embed these notes and directions with an accurate GPS map. This map, combined with a
        GPS navigation unit or compatible Smart Phone device, may prove useful as a navigation assist.
        It&#39;s important that the cue notes are displayed in plain view of the rider so they may be referred to
        frequenly. <em>Items such as Control locations, Hazard Warnings and other useful information are displayed on the
            cue notes only! </em>Cue notes rendered on cue sheets may be abbreviated with common shorthand given in a
        glossary at the top of the cue sheet (eg TFL=Traffic Light, etc...) Please respect that spotting cues, notes,
        and warnings were placed there based on the experience of past riders. Ignore their notes at your
        peril.&nbsp; Keep alert for other route changes and hazards that are not documented.
    </p>
</div>

<?php if ($has_cuesheet === true) : ?>
    <h4> GPS Route Information </h4>
<?php else : ?>
    <h4> PRELIMINARY GPS Route Information </h4>
    <p>This GPS info is preliminary and should not be used for navigating the route.</p>
<?php endif; ?>



<TABLE class='w3-table-all'>
    <TR>
        <TD>Route Name</TD>
        <TD><?= $route_name ?></TD>
    </TR>
    <TR>
        <TD>Route Editor Link URL</TD>
        <TD><A HREF=<?= $rwgps_url ?>><?= $rwgps_url ?></a></TD>
    </TR>
    <TR>
        <TD>Last Modified</TD>
        <TD><?= $last_update ?></TD>
    </TR>
     <TR>
        <TD>Raw Route Files</TD>
        <TD><?= $df_links_txt ?></TD>
    </TR>
</TABLE>