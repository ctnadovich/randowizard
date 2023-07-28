<!-- Navbar -->
<div class="w3-bar w3-white w3-wide w3-padding">
    <div class="w3-dropdown-hover">
        <button class="w3-bar-item w3-button w3-white"><i class="fa fa-bars w3-xlarge"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-border">
            <?php if ($session['logged_in']==1): ?>
            <a href="/events" class="w3-bar-item w3-button">Events</a>
            <a href="/logout" class="w3-bar-item w3-button">Log Out</a>
            <?php else: ?>
            <a href="/login" class="w3-bar-item w3-button">Log In</a>
            <?php endif; ?>
            <a href="/contact" class="w3-bar-item w3-button">Contact</a>
            <a href="https://randonneuring.org/phpmyadmin/index.php" class="w3-bar-item w3-button">phpMyAdmin</a>
        </div>
    </div>


    <a href="/" class="w3-bar-item w3-button"><b>Randonneuring</b>.org</a>
    <!-- Float links to the right. -->
    <div class="w3-right">

        <?php if ($session['logged_in']==1): ?>
        <a href="/profile" class="w3-bar-item w3-button  w3-hide-small">Profile
            (<?= $session['first_last'] ?? ''; ?>)</a>
        <a href="/logout" class="w3-bar-item w3-button  w3-hide-small">Log Out</a>
        <?php else: ?>
        <a href="/login" class="w3-bar-item w3-button  w3-hide-small">Log In</a>
        <?php endif; ?>
        <a href="/contact" class="w3-bar-item w3-button w3-hide-small">Contact</a>

        <!-- <?= print_r($session,true); ?>  -->

    </div>
</div>