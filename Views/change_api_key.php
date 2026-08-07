<div class="w3-container">

    <p>
        External registration systems may create waiver signing
        sessions by authenticating with the Waiver API.
    </p>

    <?php if ($has_api_key): ?>

        <div class="w3-panel w3-pale-yellow w3-leftbar w3-border-yellow">
            <p>
                <b>An API key has already been generated for  <?= $region_name ?>.</b>
            </p>

            <p>
                Generating a new key will immediately invalidate the
                existing key. Any external registration systems using
                the old key will stop working until updated.
            </p>
        </div>

        <button
            class="w3-button w3-red"
            onclick="generateWaiverApiKey()">
            <i class="fa-solid fa-key"></i>
            Generate New API Key
        </button>

    <?php else: ?>

        <div class="w3-panel w3-pale-blue w3-leftbar w3-border-blue">
            <p>
                No Waiver API key has been created for <?= $region_name ?>.
            </p>

            <p>
                Generate a key if you wish to allow an external
                registration system to initiate waiver sessions on
                behalf of  <?= $region_name ?>.
            </p>
        </div>

        <button
            class="w3-button w3-blue"
            onclick="generateWaiverApiKey()">
            <i class="fa-solid fa-key"></i>
            Generate API Key
        </button>

    <?php endif; ?>

    <div
        id="api-key-panel"
        class="w3-panel w3-pale-green w3-border w3-margin-top"
        style="display:none">

        <h4>Your New API Key</h4>

        <p>
            Copy this key now and store it securely. For security
            reasons it cannot be displayed again after this dialog
            is closed.
        </p>

        <input
            id="api-key"
            class="w3-input w3-border w3-monospace"
            type="text"
            readonly>

        <div class="w3-margin-top" style="padding-bottom:16px">
            <button
                class="w3-button w3-green"
                onclick="copyApiKey()">
                <i class="fa-solid fa-copy"></i>
                Copy to Clipboard
            </button>
        </div>

    </div>

</div>

<script>
    async function generateWaiverApiKey() {
        const confirmed = window.confirm(
            'Generate a new API key? Any existing key will immediately stop working.'
        );

        if (!confirmed) {
            return;
        }

        const buttons = document.querySelectorAll(
            '[onclick="generateWaiverApiKey()"]'
        );

        buttons.forEach(button => {
            button.disabled = true;
        });

        try {
            const form_data = new FormData();

            form_data.append(
                'club_acp_code',
                '<?= esc($club_acp_code, 'js') ?>'
            );

            <?php if (config('Security')->csrfProtection !== false): ?>
                form_data.append(
                    '<?= csrf_token() ?>',
                    '<?= csrf_hash() ?>'
                );
            <?php endif; ?>

            const response = await fetch(
                '<?= site_url('generateWaiverApiKey') ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: form_data
                }
            );

            const response_text = await response.text();

            let result = null;

            try {
                result = JSON.parse(response_text);
            } catch (error) {
                /*
                 * The server returned HTML, plain text, or an empty body
                 * instead of the expected JSON.
                 */
            }

            if (!response.ok) {
                const error_message =
                    result?.error ||
                    response_text.trim() ||
                    `Request failed with HTTP status ${response.status}.`;

                throw new Error(error_message);
            }

            if (
                result === null ||
                typeof result.api_key !== 'string' ||
                result.api_key === ''
            ) {
                throw new Error(
                    'The server reported success but did not return an API key.'
                );
            }

            document.getElementById('api-key').value =
                result.api_key;

            document.getElementById('api-key-panel').style.display =
                'block';

        } catch (error) {
            buttons.forEach(button => {
                button.disabled = false;
            });

            console.error(error);

            window.alert(
                error instanceof Error ?
                error.message :
                String(error)
            );
        }
    }


    async function copyApiKey() {
        const api_key_input =
            document.getElementById('api-key');

        const api_key = api_key_input.value;

        if (api_key === '') {
            return;
        }

        try {
            await navigator.clipboard.writeText(api_key);
            window.alert('API key copied to the clipboard.');
        } catch (error) {
            api_key_input.focus();
            api_key_input.select();
            api_key_input.setSelectionRange(
                0,
                api_key_input.value.length
            );

            window.alert(
                'The key has been selected. Press Ctrl+C or Command+C to copy it.'
            );
        }
    }
</script>
