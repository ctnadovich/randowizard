<div class="w3-content" style="max-width:700px">

    <div class="w3-panel w3-pale-green w3-leftbar w3-border-green">

        <div class="w3-row">

            <?php if (!empty($waiver_logo_url)): ?>
                <div class="w3-col" style="width:90px">
                    <img
                        src="<?= esc($waiver_logo_url, 'attr') ?>"
                        alt="Organization Logo"
                        style="
                            max-width:72px;
                            max-height:72px;
                            object-fit:contain;
                        ">
                </div>
            <?php endif; ?>

            <div class="w3-rest">

                <h2 class="w3-margin-top">
                    <span class="w3-text-green">&#10004;</span>
                    Waiver Successfully Completed
                </h2>

                <p>
                    Thank you. Your waiver has been signed and
                    permanently recorded.
                </p>

                <p>
                    A copy of the signed waiver is available for
                    your records.
                </p>

            </div>

        </div>

        <div class="w3-center w3-margin-top w3-margin-bottom">
            <a
                href="<?= site_url("waiver/document/$session_id") ?>"
                class="w3-button w3-blue w3-round w3-large">
                <i class="fa-solid fa-file-pdf"></i>
                Download Signed PDF
            </a>

        </div>

    </div>

    <div class="w3-card w3-white w3-padding w3-margin-top">

        <h3 class="w3-margin-top">
            <?= esc($event_name) ?>
        </h3>

        <p class="w3-large">
            <i class="fa-regular fa-calendar"></i>
            <?= esc($event_date) ?>

            <span class="w3-margin-left">
                <i class="fa-regular fa-clock"></i>
                <?= esc($event_time) ?>
            </span>
        </p>

        <hr>

        <table class="w3-table">

            <tr>
                <td style="width:35%">
                    <b>Participant</b>
                </td>
                <td>
                    <?= esc($participant_name) ?>
                </td>
            </tr>

            <tr>
                <td>
                    <b>Completed</b>
                </td>
                <td>
                    <?= esc($session['completed_at']) ?> UTC
                </td>
            </tr>

            <tr>
                <td>
                    <b>Document</b>
                </td>
                <td>

                    <?php
                    $document_sha256 =
                        (string) $session['document_sha256'];

                    $short_sha256 =
                        substr($document_sha256, 0, 12)
                        . '…';
                    ?>

                    <span
                        class="w3-small w3-text-grey"
                        title="<?= esc(
                                    $document_sha256,
                                    'attr'
                                ) ?>"
                        style="cursor:help">
                        SHA-256:
                        <code><?= esc($short_sha256) ?></code>
                    </span>

                </td>
            </tr>

        </table>

    </div>

    <?php if (!empty($callback_url)): ?>

        <div class="w3-center w3-padding-32">

            <a
                href="<?= esc($callback_url, 'attr') ?>"
                class="
                    w3-button
                    w3-green
                    w3-round-large
                    w3-card
                    w3-xxlarge
                "
                style="
                    min-width:300px;
                    padding:18px 36px;
                    font-weight:bold;
                ">
                CONTINUE
                <i class="fa-solid fa-arrow-right w3-margin-left"></i>
            </a>

        </div>

    <?php endif; ?>

</div>