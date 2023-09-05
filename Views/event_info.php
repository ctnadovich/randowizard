<img align=right src='<?= $icon_url ?>'>
<h1><?= $club_name ?></h1>
<h2><?= $title ?></h2>
<p><?= $event_description ?></p>

<div class='w3-container'>
    <div class='w3-card w3-padding'>
        <H4>GENERAL INFO</h4>
        <div class='narrower'>
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
        </DIV>
    </DIV>

    <div class=w3-padding></div>

    <div class='w3-card w3-padding'>
        <H4>GPS ROUTE DATA</h4>
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
                <TD>Last Download</TD>
                <TD><?= $last_download ?></TD>
            </TR>
            <TR>
                <TD>Other GPS Files</TD>
                <TD><?= $df_links_txt ?></TD>
            </TR>
        </TABLE>
    </DIV>

    <?= $body ?>
</div>