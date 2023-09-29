<!-- Register Prompt -->
<div class="w3-container">

    <div class="w3-panel w3-center w3-border w3-margin" >
        <h3>RBAs and Organizers</h3>
        <p><em>Read the <A HREF='https://randonneuring.org/about/quick_start'>Quick Start Guide</a> or 
        the <A HREF='https://randonneuring.org/about'>documentation</a>, then register to get started.</em></p>
        <p><button onclick="document.getElementById('register-card').style.display='block'" class="w3-button w3-black">Register
                &raquo;</button></p>
    </div>
</div>
<!-- Register Modal Form -->

<div id="register-card" class="w3-modal" style=<?= empty($errors) ? '"display: none;"' : '"display: block;"'; ?>>
    <div class="w3-modal-content w3-card-4">
        <header class="w3-container w3-deep-purple">
            <span onclick="document.getElementById('register-card').style.display='none'" class="w3-button w3-display-topright">&times;</span>
            <form method="POST" action="/register">
                <div class="w3-container w3-padding w3-text-white" id="register-card">
                    <h2>Organizer Registration</h2>

                    <div class="w3-panel w3-purple" style=<?= empty($errors) ? '"display: none;"' : '"width:90%; display: block;"'; ?>>
                        <h3>Please correct the following errors</h3>
                        <?= validation_list_errors() ?>
                    </div>
                    <p>Event organizers and RBAs must register here
                        to manage events for their randonneuring region. Please provide all the information required
                        below.
                        Specify a randonneuring region, and please
                        provide basic contact information for the person who will use these tools to manage
                        the region's events. Choose a password to keep access secure.
                    </p>

                    <h3>Region</h3>

                    <select required class="w3-select w3-padding" name="region" style="width:90%">
                        <option value="">Choose your region</option>
                        <?php
                        foreach ($region as $r) {
                            extract($r);
                            $selected = (empty($errors['region']) && $id == set_value('region')) ? 'selected' : '';
                            echo "<option $selected value=$id>$state_code:$region_name</option>";
                        }
                        ?>
                    </select>

                    <hr>

                    <h3>Organizer Contact Info</h3>

                    <?= input_field('first', 'First Name', $errors) ?>
                    <?= input_field('last', 'Last Name', $errors) ?>
                    <?= input_field('email', 'Email Address', $errors) ?>
                    <?= input_field('password', 'Set Access Password', $errors, 'password') ?>

                    <div class='w3-container w3-center'>
                        <?php foreach ($vehicle_icon as $i => $v) : ?>
                            <div class='w3-button w3-white w3-margin'>
                                <input type='checkbox' name='v[]' value='v<?= $i ?>'><span style='margin-left: 8px;'><i class='<?= $v ?> w3-purple w3-padding'></span></i>
                        </div>
                        <?php endforeach; ?>
                             <div>Select all bicycles</div>
                             <input type='hidden' name='is_bike' value='<?=implode(',',$is_bike)?>'>
           </div>

                    <hr>

                    <div class="w3-container w3-center"><button class="w3-btn w3-black w3-hover-green">Register</button>
                    </div>
                </div>
            </form>
    </div>
</div>