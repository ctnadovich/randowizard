 <TABLE class='w3-table-all'>
     <TR>
         <TD>Start Location</TD>
         <TD><?= $event_location ?> 
         <A TITLE='Weather Info' HREF='https://forecast.weather.gov/MapClick.php?CityName=<?=urlencode($start_city)?>&state=<?=$start_state?>'><I class='fas fa-cloud-sun'></i></a>
        </TD>
     </TR>
     <TR>
         <TD>Start Date</TD>
         <TD><?= $event_date_str ?></TD>
     </TR>
     <TR>
         <TD>Start Time</TD>
         <TD><?= $event_time_str ?></TD>
     </TR>
     <TR>
         <TD>Cutoff Time</TD>
         <TD><?="$cutoff_datetime_str ($cutoff_interval_str)"?></TD>
     </TR>
     <TR>
         <TD>Sunrise/Sunset</TD>
         <TD>Rise <?= $sunrise_str ?>, Set <?= $sunset_str ?><?=$riding_at_night ? ", <SPAN class='w3-indigo w3-padding-small'>POSSIBLE NIGHT RIDING</SPAN>" : "" ?></TD>
     </TR>

     <TR>
         <TD>Club Event Website</TD>
         <TD><A HREF='<?= $club_event_info_url ?>'><?= $club_event_info_url ?></A></TD>
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
     <?= (!empty($pavement_type)) ?
            "<TR><TD>Surface</TD><TD>" . $pavement_type . "</TD></TR>" : "" ?>
     <TR>
         <TD>Percent Unpaved</TD>
         <TD><?= $unpaved_pct ?></TD>
     </TR>
 </TABLE>