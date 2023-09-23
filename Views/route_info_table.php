<TABLE class='w3-table-all'>
    <TR>
        <TD>RWGPS Route Name</TD>
        <TD><?= $route_name ?></TD>
    </TR>
    <TR>
        <TD>RWGPS Link URL</TD>
        <TD><A HREF=<?= $rwgps_url ?>><?= $rwgps_url ?></a></TD>
    </TR>
    <TR>
        <TD>Last Modified</TD>
        <TD><?= $last_update ?></TD>
    </TR>
    <TR>
        <TD>Last Fetched</TD>
        <TD><?= $last_download ?></TD>
    </TR>
    <TR>
        <TD>Last Published</TD>
        <TD><?= $published_at_str ?></TD>
    </TR>
</TABLE>