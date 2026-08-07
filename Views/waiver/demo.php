<div class="w3-content w3-padding-32" style="max-width:700px">

    <div class="w3-card w3-white w3-round-large w3-padding">
        <h1 class="w3-margin-top">Waiver API Demo</h1>

        <p>
            This demonstration will create a waiver-signing session
            for the following randomly generated test event and
            participant.
        </p>

        <?php if (!empty($error)): ?>
            <div class="w3-panel w3-pale-red w3-leftbar w3-border-red">
                <h3>Unable to Start Demo</h3>
                <p><?= esc($error) ?></p>
            </div>
        <?php endif; ?>

        <table class="w3-table w3-bordered w3-margin-bottom">
            <tr>
                <th style="width:40%">Event</th>
                <td><?= esc($event_name) ?></td>
            </tr>
            <tr>
                <th>Event ID</th>
                <td><code><?= esc($event_id) ?></code></td>
            </tr>
            <tr>
                <th>Participant</th>
                <td><?= esc($participant_name) ?></td>
            </tr>
            <tr>
                <th>Participant ID</th>
                <td><code><?= esc($participant_id) ?></code></td>
            </tr>
        </table>

        <form method="post" action="<?= site_url('waiver/demo') ?>">
            <?= csrf_field() ?>

            <input
                type="hidden"
                name="event_id"
                value="<?= esc($event_id, 'attr') ?>">

            <input
                type="hidden"
                name="participant_id"
                value="<?= esc($participant_id, 'attr') ?>">

            <div class="w3-center w3-padding-16">
                <button
                    type="submit"
                    class="w3-button w3-green w3-round w3-large">
                    Go
                </button>
            </div>
        </form>
    </div>

    <div class="w3-panel w3-pale-blue w3-leftbar w3-border-blue">
        <h3>For More Information</h3>
        <p>
            Read the
            <a
                href="https://github.com/ctnadovich/randowizard/blob/main/README_WAIVER_API.md"
                target="_blank"
                rel="noopener noreferrer">
                Waiver API documentation
            </a>
            for details about integrating an external registration
            system.
        </p>
    </div>

</div>
