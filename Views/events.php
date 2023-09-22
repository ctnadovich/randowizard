    <h2>Upcoming Events</h2>
    <div class='w3-container'>
        <table class='w3-table-all'>
            <?php

            foreach ($event_table as $event) {
                extract($event);
                $startDatetime = (new \DateTime($start_datetime, new \DateTimeZone($timezone_name)));
                $now = new \DateTime();
                if($startDatetime < $now) continue;
                $sdtxt = $startDatetime->format("M j @ H:i T");
                $event_code = "$region_id-$event_id";
                $infolink = "<A class='w3-button' TITLE='Info' HREF='" . site_url("event_info/$event_code") . "'><i class='fa fa-circle-info'></i></a>";
                $resultslink = "<A class='w3-button' TITLE='Riders/Results' HREF='" . site_url("checkin_status/$event_code") . "'><i class='fa fa-users'></a>";
                $row = ["$region_state: $region_name", "$name $distance K", $sdtxt,  $infolink,  $resultslink];
                echo "<TR><TD>" . implode('</TD><TD>', $row) . "</TD></TR>";
            }

            ?>
        </table>
    </div>