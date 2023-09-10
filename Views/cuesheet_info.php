<?php if ($has_cuesheet === true) : ?>

    <div class='w3-container'>
        <p>A Cue Sheet is available for this event.</p>
        <TABLE class='w3-table-all'>
            <TR>
                <TD>Cuesheet Generated</TD>
                <TD><?= $cue_gentime_str ?></TD>
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
        <p>At this time no cue sheet is available for this event.</p>
    </div>

<?php endif; ?>