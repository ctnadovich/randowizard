<div class="w3-content" style="max-width:700px">

    <div class="w3-panel w3-pale-green w3-leftbar w3-border-green">
        <h2>
            <span style="color:green">&#10004;</span>
            Waiver Successfully Completed
        </h2>

        <p>
            Thank you. Your waiver has been signed and permanently
            recorded.
        </p>

        <p>
            A copy of the signed waiver is available below for your
            records.
        </p>
    </div>

    <div class="w3-card w3-padding w3-margin-top">

        <table class="w3-table">
            <tr>
                <td><b>Completed</b></td>
                <td><?= esc($session['completed_at']) ?> UTC</td>
            </tr>

            <tr>
                <td><b>Document SHA-256</b></td>
                <td>
                    <code style="font-size:90%">
                        <?= esc($session['document_sha256']) ?>
                    </code>
                </td>
            </tr>
        </table>

    </div>

    <div class="w3-margin-top">

        <a href="<?= site_url('waiver/document/' . $session['session_id']) ?>"
           class="w3-button w3-blue w3-margin-right">
            View Signed PDF
        </a>

        <a href="<?= site_url('event/' . $session['event_code']) ?>"
           class="w3-button w3-light-grey">
            Return to Event
        </a>

    </div>

</div>