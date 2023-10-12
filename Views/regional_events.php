<?php if ($underway_events_table!==null):?>
<div class='w3-card w3-padding w3-margin' style='clear: right;'>
    <h2>Events Underway</h2>
    <div class='w3-container w3-padding'>
            <?= $underway_events_table ?>
    </div>
</div>
<?php endif;?>
<div class='w3-card w3-padding w3-margin' style='clear: right;'>
    <h2>Upcoming Events</h2>
    <div class='w3-container w3-padding'>
            <?= ($future_events_table!==null)?$future_events_table:"<P>None.</P>" ?>
    </div>
</div>
<div class='w3-card w3-padding w3-margin' style='clear: right;'>
    <h2>Past Events</h2>
    <div class='w3-container w3-padding'>
    <?= ($past_events_table!==null)?$past_events_table:"<P>None.</P>" ?>
    </div>
</div>