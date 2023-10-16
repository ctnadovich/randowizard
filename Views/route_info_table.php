<TABLE class='w3-table-all'>
    <TR>
        <TD>Route Link URL</TD>
        <TD><A HREF=<?= $route_url ?>><?= $route_url ?></a></TD>
    </TR>
    <?php if (empty($fatal_route_error)) : ?>
        <TR>
            <TD>Event</TD>
            <TD><?="$event_name_dist"?></TD>
        </TR>
        <TR>
            <TD>Event Last Changed</TD>
            <TD><?= $last_event_change_str ?></TD>
        </TR>
        <TR>
            <TD>Route Last Modified</TD>
            <TD><?= $last_update ?></TD>
        </TR>
        <TR>
            <TD>Route Last Fetched</TD>
            <TD><?= $last_download ?></TD>
        </TR>
        <TR>
            <TD>Last Published</TD>
            <TD><?= $cue_version>0 ? "Version $cue_version at " : ""?><?= $published_at_str ?></TD>
        </TR>
    <?php endif; ?>
</TABLE>