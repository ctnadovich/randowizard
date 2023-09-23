<TABLE class='w3-table-all'>
    <TR>
        <TD>Route Link URL</TD>
        <TD><A HREF=<?= $route_url ?>><?= $route_url ?></a></TD>
    </TR>
    <?php if (empty($fatal_route_error)) : ?>
        <TR>
            <TD>Route Name</TD>
            <TD><?= $route_name ?></TD>
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
    <?php endif; ?>
</TABLE>