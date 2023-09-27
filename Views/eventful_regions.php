    <h2>Regions with Events</h2>
<div class="w3-card w3-padding w3-margin">
    <?php 
    if(count($eventful_regions)==0):
        echo "<P>None</P>"; 
     else :
     ?>

    <table class="w3-table-all">
        <?php
        foreach ($eventful_regions as $r) :
            extract($r);
        ?>
            <TR>
                <TD><?= "$state_code: $region_name" ?></TD>
                <TD><?= "$club_name" ?></TD>
                <TD><?= "$event_count Event" . (($event_count > 1) ? 's' : '') ?></TD>
                <TD><a title="Regional Event Info" class='w3-button w3-teal' href='<?= site_url("regional_events/$region_id") ?>' class="w3-button"><i class='w3-xlarge fa fa-info-circle'></i></a></TD>
            <?php
        endforeach;
            ?>
    </table>

    <?php endif;?>
</div>