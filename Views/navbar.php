<!-- Navbar -->
<div class="w3-bar w3-white w3-wide w3-padding">
    <div class="w3-dropdown-hover">
        <button class="w3-bar-item w3-button w3-white"><i class="fa fa-bars w3-xlarge"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-border">
            <?php if ($session['logged_in']==1): ?>
            <a href="/events" class="w3-bar-item w3-button">My Events</a>
            <a href="/region" class="w3-bar-item w3-button">My Region</a>
            <a href="/profile" class="w3-bar-item w3-button">My Profile</a>
            <a href="/logout" class="w3-bar-item w3-button">Log Out</a>
            <?php else: ?>
            <a href="/login" class="w3-bar-item w3-button">Log In</a>
            <?php endif; ?>
            <a href="/about/quick_start" class="w3-bar-item w3-button">Quick Start Guide</a>
            <a href="/about" class="w3-bar-item w3-button">Documentation</a>
            <a href="https://randonneuring.org/phpmyadmin/index.php" class="w3-bar-item w3-button">phpMyAdmin</a>
        </div>
    </div>


    <a href="/" class="w3-bar-item w3-button"><b>Randonneuring</b>.org</a>
    <!-- Float links to the right. -->
    <div class="w3-right">

        <?php if ($session['logged_in']==1): ?>
        <span style="font-style: italic;"
            class="w3-bar-item w3-hide-small w3-medium "><?= $session['first_last'] ?></span>
        <a href="/profile" title="Profile" class="w3-bar-item w3-button"><i class="fa fa-user"></i></a>
        <a href="/region" title="Region" class="w3-bar-item w3-button"><i class="fas fa-map"></i></a>
        <a href="/events" title="Events" class="w3-bar-item w3-button"><i class="fas fa-biking"></i></a>
        <a href="/logout" title="Log Out" class="w3-bar-item w3-hide-small">Log Out</a>
        <?php else: ?>
        <a href="/login" title="Log In" class="w3-bar-item w3-hide-small">Log In</a>
        <?php endif; ?>

        <!-- <?= print_r($session,true); ?>  -->

    </div>
</div>