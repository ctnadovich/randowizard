<div class="w3-container">

    <div class="w3-card-4 w3-border">
        <header class="w3-container w3-blue">
            <h2><?= $severity ?></h2>
            <?php if(!empty($file_line)) echo "<h3>$file_line</h3>"; ?>
        </header>

        <div class="w3-container">
            <p><?= $text ?></p>
        </div>

        <?php if (!empty($backtrace)) : ?>
            <div class="w3-container w3-light-gray">
                <h3>Backtrace</h3>
                <p><?= $backtrace ?></p>
            </div>
        <?php endif; ?>

    </div>
</div>