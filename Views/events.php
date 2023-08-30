    <h2>Events</h2>
    <div class='w3-container'>
        <table class='w3-table-all'>
            <?php

            foreach ($event_table as $event) {
                extract($event);
                $sd = (new \DateTime($start_datetime, new \DateTimeZone($event_timezone_name)))->format("M j @ H:i T");
                $event_code = "$region_id-$event_id";
                $infolink = "<A HREF='" . site_url("ebrevet/event_info/$event_code") . "'>info</a>";
                $resultslink = "<A HREF='" . site_url("ebrevet/checkin_status/$event_code") . "'>results</a>";
                $row = ["$region_state: $region_name", "$name $distance K", $sd,  $infolink,  $resultslink];
                echo "<TR><TD>" . implode('</TD><TD>', $row) . "</TD></TR>";
            }

            ?>
        </table>
    </div>