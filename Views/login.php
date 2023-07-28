<div id="login-card" class="w3-modal" style="display: block;">
    <div class="w3-modal-content w3-card-4">
        <header class="w3-container w3-deep-purple">
            <form method="POST">
                <div class="w3-container w3-padding w3-text-white" id="login-card">
                    <h2>Log In</h2>

                    <div class="w3-panel w3-purple"
                        style=<?= empty($errors)?'"display: none;"':'"width:90%; display: block;"'; ?>>
                        <h3>Please correct the following errors</h3>
                        <?= validation_list_errors() ?>
                    </div>

                    <div class="w3-panel w3-purple w3-padding"
                        style=<?= empty($login_error)?'"display: none;"':'"width:90%; display: block;"'; ?>>
                        <h3>Please correct the following errors</h3>
                        <ul>
                            <li><?= $login_error ?? '' ?></li>
                        </ul>
                    </div>

                    <?=input_field('email','Enter Email Address',$errors,'text', !empty($login_error))?>
                    <?=input_field('password','Enter Access Password',$errors,'password', !empty($login_error))?>

                    <div class="w3-bar">
                        <button name="submit" value="cancel" class="w3-btn w3-black w3-hover-green">Cancel</button>
                        <button name="submit" value="login" class="w3-btn w3-black w3-hover-green">Log In</button>
                    </div>

                </div>
            </form>
        </header>
    </div>
</div>