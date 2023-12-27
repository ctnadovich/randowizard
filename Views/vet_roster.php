<div class='w3-container w3-padding'>
    <h2>RUSA Membership Check for Event</h2>
    <h3><?= $n_riders ?> Riders</h3>
    <TABLE class='w3-table-all'>
        <?= $table_body ?>
    </TABLE>

<?php if($bad_riders>0): ?>
    <div class='w3-card w3-margin w3-padding w3-leftbar w3-border-red w3-large w3-center'>
        <?= $bad_riders?> rider<?=$bad_riders>1?'s':''?> failed the membership check. 
    </div>
    <?php else: ?>
        <p>All riders are valid RUSA members through the event date.</p>
    <?php endif; ?>

</div>
