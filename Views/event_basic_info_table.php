 <TABLE class='w3-table-all'>
    <TR>
        <TD>Start Date</TD>
        <TD><?= $event_date_str ?></TD>
    </TR>
    <TR>
        <TD>Start Time</TD>
        <TD><?= $event_time_str ?></TD>
    </TR>
    <TR>
        <TD>Start Location</TD>
        <TD><?= $event_location ?></TD>
    </TR>
    <TR>
        <TD>Event Website</TD>
        <TD><A HREF='<?= $event_info_url ?>'><?= $event_info_url ?></A></TD>
    </TR>
    <TR>
        <TD>Event Type</TD>
        <TD><?= $event_type_uc ?></TD>
    </TR>
    <TR>
        <TD>Official Distance</TD>
        <TD><?= $event_distance ?> km</TD>
    </TR>
    <TR>
        <TD>Route Distance</TD>
        <TD><?= $distance_mi ?> mi / <?= $distance_km ?> km</TD>
    </TR>
    <TR>
        <TD>Climbing</TD>
        <TD><?= $climbing_ft ?> ft</TD>
    </TR>
    <?= ($gravel_distance > 0) ?
        "<TR><TD>Official Gravel Distance</TD><TD>" . $gravel_distance . " km</TD></TR>" : "" ?>
    <TR>
        <TD>Surface</TD>
        <TD><?= $pavement_type ?></TD>
    </TR>
    <TR>
        <TD>Percent Unpaved</TD>
        <TD><?= $unpaved_pct ?></TD>
    </TR>
</TABLE>