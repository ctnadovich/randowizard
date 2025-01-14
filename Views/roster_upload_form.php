<h1>Event Roster Upload</h1>
<h2><?= $event_name_dist ?></h2>

<p>Please upload a CSV roster file for your event. An uploaded CSV clears any riders 
    previously listed. The format is compatible 
    with <a href=https://jkassen.org/cards>Card-O-Matic</a> and other systems. The 
    CSV file must have a first-row header giving column names. Column RIDERID is required. Coulumn LAST 
    is required if your region has membership vetting (eg Randonneurs USA / RUSA).  
    Valid Columns (may be in any order) are:</p>
<div class='w3-card w3-small w3-padding-small w3-light-gray' style="width: 50%;">
    <ul>
        <li>"RIDERID" or variations ("rider id", "rusa", "rusa number", "member", "acp", etc)</li>
        <li>"FIRST" or variations ("firstname", "first name", etc)</li>
        <li>"LAST" or variations</li>
        <li>"ADDRESS" or "STREET" (multiples combined, eg address1, address2...)</li>
        <li>"CITY"</li>
        <li>"STATE"</li>
        <li>"ZIP"</li>
    </ul>
</div>

<div class='w3-container w3-padding w3-margin'>

    <h3>Select CSV Roster File to Upload</h3>

    <?php if (!empty($errors)) : ?>
        <div class="w3-panel w3-purple" style="width: 75%;">
            <h3>Please correct the following errors in the uploaded file</h3>
            <ul>
                <?php foreach ($errors as $error) : ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?= form_open_multipart("roster_upload/$event_code") ?>

    <style>
        input[type=file]::file-selector-button {
            margin: 16px;
            border: none;
            background: #084cdf;
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            cursor: pointer;
            transition: background .2s ease-in-out;
        }

        input[type=file]::file-selector-button:hover {
            background: #0d45a5;
        }
    </style>

    <input type="file" name="userfile">


    <button class="w3-btn w3-black w3-hover-green w3-margin"><I CLASS='fas fa-upload'></i>Upload</button>



    </form>

</div>