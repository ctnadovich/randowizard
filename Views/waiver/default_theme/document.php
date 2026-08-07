<?php
/*
 * Expected variables:
 *
 * $render_mode              'html' or 'pdf'
 * $waiver_logo_url
 * $header
 * $title
 * $initial
 * $clause
 * $waiver_timestamp
 * $session_id
 * $footer
 * $participant_name
 * $event_name
 * $event_date
 * $event_time
 * $esc
 *
 * For signed/PDF rendering:
 * $signature_png
 * $initials_png
 * $age_acknowledged
 * $acknowledged
 */

$render_mode = $render_mode ?? 'html';
$is_pdf = $render_mode === 'pdf';

$signature_png = $signature_png ?? '';
$initials_png = $initials_png ?? '';
$age_acknowledged = $age_acknowledged ?? false;
$acknowledged = $acknowledged ?? false;
?>

<div id="waiver-document"
    class="waiver-document <?= $is_pdf
                                ? 'waiver-document-pdf'
                                : 'waiver-document-html w3-container w3-padding-32'
                            ?>">

    <div class="waiver-card waiver-header-card <?= !$is_pdf
                                                    ? 'w3-card w3-round-large w3-white w3-padding w3-margin-bottom'
                                                    : ''
                                                ?>">

        <div class="logo-card">

            <!-- Left column -->
            <div class="logo-column">
                <img src="<?= $waiver_logo_url ?>"
                    alt="Logo"
                    class="waiver-logo w3-image">
            </div>


            <div class="title-column">
                <p class="centered">
                    <?= lm($header) ?>
                </p>

                <p class="centered">
                    <strong><?= lm($title) ?></strong>
                </p>
            </div>
        </div>
    </div>

    <div class="waiver-card <?= !$is_pdf
                                ? 'w3-card w3-round-large w3-white w3-padding w3-margin-bottom'
                                : ''
                            ?>">

        <p class="initial-heading">
            <strong><?= lm($initial) ?></strong>
        </p>

        <div class="initials-panel">
            <?php if ($initials_png !== ''): ?>
                <img
                    src="<?= esc($initials_png, 'attr') ?>"
                    alt="Participant initials"
                    class="initials-image">
            <?php elseif (!$is_pdf): ?>
                <div
                    id="initials-placeholder"
                    class="w3-text-grey w3-italic">
                    Not yet initialed
                </div>

                <div class="w3-padding-small">
                    <img
                        id="initials-display"
                        alt="Participant initials"
                        class="initials-image"
                        style="display:none;">
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$is_pdf): ?>
            <button
                type="button"
                id="initial-document-button"
                class="w3-button w3-small w3-blue">
                Initial
            </button>
        <?php endif; ?>

        <?php foreach ($clause as $c): ?>
            <p><?= lm($c) ?></p>
        <?php endforeach; ?>

        <p>
            Date: <?= esc($waiver_timestamp) ?><br>
            Session ID: <?= esc($session_id) ?>
        </p>

        <p class="centered">
            <strong><?= lm($footer) ?></strong>
        </p>
    </div>

    <div class="waiver-card <?= !$is_pdf
                                ? 'w3-card w3-round-large w3-white w3-padding w3-margin-bottom'
                                : ''
                            ?>">

        <h2>Participant Name</h2>

        <div class="participant-name">
            <strong><?= esc($participant_name) ?></strong>
        </div>

        <h2>Participant Age Acknowledgement</h2>

        <?php if ($is_pdf): ?>
            <p>
                <span class="pdf-checkbox">
                    <?= $age_acknowledged ? '&#9745;' : '&#9744;' ?>
                </span>
                I certify that I am 18 years of age or older.
            </p>
        <?php else: ?>
            <p class="w3-large">
                <input
                    class="w3-check"
                    id="age-acknowledgement-checkbox"
                    name="age-acknowledged"
                    type="checkbox"
                    value="1">
                I certify that I am 18 years of age or older.
            </p>
        <?php endif; ?>

        <h2>Event Information</h2>

        <p>
            Event: <?= esc($event_name) ?><br>
            Start Date: <?= esc($event_date) ?><br>
            Start Time: <?= esc($event_time) ?>
        </p>

        <h2>Rider Signature</h2>

        <div class="signature-panel">
            <?php if ($signature_png !== ''): ?>
                <img
                    src="<?= esc($signature_png, 'attr') ?>"
                    alt="Participant signature"
                    class="signature-image">
            <?php elseif (!$is_pdf): ?>
                <div
                    id="signature-placeholder"
                    class="w3-text-grey w3-italic">
                    Not yet signed
                </div>

                <div class="w3-padding-small">
                    <img
                        id="signature-display"
                        alt="Participant signature"
                        class="signature-image"
                        style="display:none;">
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$is_pdf): ?>
            <button
                type="button"
                id="sign-document-button"
                class="w3-button w3-small w3-blue">
                Sign Document
            </button>
        <?php endif; ?>
    </div>

    <div class="waiver-card <?= !$is_pdf
                                ? 'w3-card w3-round-large w3-white w3-padding w3-margin-bottom'
                                : ''
                            ?>">

        <h2>Electronic Signature Consent</h2>

        <?php if ($is_pdf): ?>
            <div class="consent-row">
                <div class="consent-checkbox">
                    <?= $acknowledged ? '&#9745;' : '&#9744;' ?>
                </div>

                <div class="consent-text">
                    <?= lm($esc) ?>
                </div>
            </div>
        <?php else: ?>
            <div class="interactive-consent-row">
                <div class="interactive-consent-checkbox">
                    <input
                        class="w3-check"
                        id="acknowledgement-checkbox"
                        name="acknowledged"
                        type="checkbox"
                        value="1">
                </div>

                <div class="interactive-consent-text">
                    <p>
                        <?= lm($esc) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>