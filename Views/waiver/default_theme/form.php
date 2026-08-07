<?= view('waiver/default_theme/document_styles') ?>

<form
    id="waiver-form"
    method="post"
    action="<?= site_url('waiver/finalize') ?>">

    <?= csrf_field() ?>

    <input
        type="hidden"
        name="session_id"
        value="<?= esc($session_id, 'attr') ?>">

    <input
        type="hidden"
        name="signature_png"
        id="signature-png">

    <input
        type="hidden"
        name="initials_png"
        id="initials-png">

    <?= view(
        'waiver/default_theme/document',
        $documentData
    ) ?>

    <div class="w3-center w3-padding-32">
        <button
            type="submit"
            id="submit-document-button"
            class="w3-button w3-green"
            disabled>
            Agree To This Document
        </button>

        <div
            id="completion-message"
            class="w3-small w3-text-grey w3-margin-top">
            Signature, initials, and acknowledgements are required.
        </div>
    </div>
</form>
<div id="signature-dialog" class="w3-modal">
    <div class="w3-modal-content w3-card-4" style="max-width:650px;">
        <header class="w3-container w3-blue">
            <button type="button"
                id="close-signature-dialog"
                class="w3-button w3-display-topright">
                &times;
            </button>

            <h3>Sign Document</h3>
        </header>

        <div class="w3-container w3-padding">
            <p>Sign inside the box.</p>

            <div style="border:1px solid #999; height:220px;">
                <canvas id="signature-canvas"
                    style="
                            width:100%;
                            height:100%;
                            display:block;
                            touch-action:none;
                        ">
                </canvas>
            </div>
        </div>

        <footer class="w3-container w3-padding">
            <button type="button"
                id="clear-signature-button"
                class="w3-button w3-light-grey">
                Clear
            </button>

            <button type="button"
                id="cancel-signature-button"
                class="w3-button w3-grey">
                Cancel
            </button>

            <button type="button"
                id="accept-signature-button"
                class="w3-button w3-green">
                Accept Signature
            </button>
        </footer>
    </div>
</div>

<div id="initials-dialog" class="w3-modal">
    <div class="w3-modal-content w3-card-4" style="max-width:450px;">
        <header class="w3-container w3-blue">
            <button type="button"
                id="close-initials-dialog"
                class="w3-button w3-display-topright">
                &times;
            </button>

            <h3>Initial Document</h3>
        </header>

        <div class="w3-container w3-padding">
            <p>Enter your initials inside the box.</p>

            <div style="border:1px solid #999; height:140px;">
                <canvas id="initials-canvas"
                    style="
                            width:100%;
                            height:100%;
                            display:block;
                            touch-action:none;
                        ">
                </canvas>
            </div>
        </div>

        <footer class="w3-container w3-padding">
            <button type="button"
                id="clear-initials-button"
                class="w3-button w3-light-grey">
                Clear
            </button>

            <button type="button"
                id="cancel-initials-button"
                class="w3-button w3-grey">
                Cancel
            </button>

            <button type="button"
                id="accept-initials-button"
                class="w3-button w3-green">
                Accept Initials
            </button>
        </footer>
    </div>
</div>

<script src="https://randonneuring.org/assets/local/js/signature_pad.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('waiver-form');

        /*
         * Stored form state.
         */
        const signatureInput = document.getElementById('signature-png');
        const initialsInput = document.getElementById('initials-png');
        const acknowledgementCheckbox =
            document.getElementById('acknowledgement-checkbox');
        const ageAcknowledgementCheckbox =
            document.getElementById('age-acknowledgement-checkbox');

        /*
         * Main document displays.
         */
        const signatureDisplay =
            document.getElementById('signature-display');

        const signaturePlaceholder =
            document.getElementById('signature-placeholder');

        const initialsDisplay =
            document.getElementById('initials-display');

        const initialsPlaceholder =
            document.getElementById('initials-placeholder');

        const submitButton =
            document.getElementById('submit-document-button');

        const completionMessage =
            document.getElementById('completion-message');

        /*
         * Signature dialog.
         */
        const signatureDialog =
            document.getElementById('signature-dialog');

        const signatureCanvas =
            document.getElementById('signature-canvas');

        const signaturePad = new SignaturePad(signatureCanvas, {
            backgroundColor: 'rgb(255, 255, 255)'
        });

        /*
         * Initials dialog.
         */
        const initialsDialog =
            document.getElementById('initials-dialog');

        const initialsCanvas =
            document.getElementById('initials-canvas');

        const initialsPad = new SignaturePad(initialsCanvas, {
            backgroundColor: 'rgb(255, 255, 255)'
        });

        /*
         * Resize a canvas after its modal becomes visible.
         *
         * preserveDataUrl is optional. When supplied, an existing accepted
         * signature or set of initials is restored into the drawing pad.
         */
        function resizePadCanvas(canvas, pad, preserveDataUrl = '') {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();

            canvas.width = Math.floor(rect.width * ratio);
            canvas.height = Math.floor(rect.height * ratio);

            const context = canvas.getContext('2d');
            context.setTransform(ratio, 0, 0, ratio, 0, 0);

            pad.clear();

            if (preserveDataUrl !== '') {
                pad.fromDataURL(preserveDataUrl, {
                    ratio: 1
                });
            }
        }

        /*
         * Determine whether the document is ready for submission.
         */

        const requirements = [{
                label: 'Signature',
                isComplete: () => signatureInput.value !== ''
            },
            {
                label: 'Initials',
                isComplete: () => initialsInput.value !== ''
            },
            {
                label: 'Age acknowledgement',
                isComplete: () => ageAcknowledgementCheckbox.checked,
                watch: ageAcknowledgementCheckbox
            },
            {
                label: 'Electronic signature acknowledgement',
                isComplete: () => acknowledgementCheckbox.checked,
                watch: acknowledgementCheckbox
            }
        ];

        function getMissingRequirements() {
            return requirements.filter(
                requirement => !requirement.isComplete()
            );
        }

        function updateCompletionState() {
            const missing = getMissingRequirements();

            submitButton.disabled = missing.length !== 0;

            completionMessage.textContent =
                missing.length === 0 ?
                'The document is ready to submit.' :
                'Still required: ' +
                missing
                .map(requirement => requirement.label)
                .join(', ') +
                '.';
        }

        /*
         * Checkbox behavior.
         */
        requirements.forEach(requirement => {
            requirement.watch?.addEventListener(
                'change',
                updateCompletionState
            );
        });


        /*
         * Signature dialog behavior.
         */
        document
            .getElementById('sign-document-button')
            .addEventListener('click', () => {
                signatureDialog.style.display = 'block';

                requestAnimationFrame(() => {
                    resizePadCanvas(
                        signatureCanvas,
                        signaturePad,
                        signatureInput.value
                    );
                });
            });

        function closeSignatureDialog() {
            signatureDialog.style.display = 'none';
        }

        document
            .getElementById('clear-signature-button')
            .addEventListener('click', () => {
                signaturePad.clear();
            });

        document
            .getElementById('cancel-signature-button')
            .addEventListener('click', closeSignatureDialog);

        document
            .getElementById('close-signature-dialog')
            .addEventListener('click', closeSignatureDialog);

        document
            .getElementById('accept-signature-button')
            .addEventListener('click', () => {
                if (signaturePad.isEmpty()) {
                    alert('Please provide a signature.');
                    return;
                }

                const dataUrl =
                    signaturePad.toDataURL('image/png');

                signatureInput.value = dataUrl;
                signatureDisplay.src = dataUrl;
                signatureDisplay.style.display = 'block';
                signaturePlaceholder.style.display = 'none';

                document
                    .getElementById('sign-document-button')
                    .textContent = 'Replace Signature';

                closeSignatureDialog();
                updateCompletionState();
            });

        /*
         * Initials dialog behavior.
         */
        document
            .getElementById('initial-document-button')
            .addEventListener('click', () => {
                initialsDialog.style.display = 'block';

                requestAnimationFrame(() => {
                    resizePadCanvas(
                        initialsCanvas,
                        initialsPad,
                        initialsInput.value
                    );
                });
            });

        function closeInitialsDialog() {
            initialsDialog.style.display = 'none';
        }

        document
            .getElementById('clear-initials-button')
            .addEventListener('click', () => {
                initialsPad.clear();
            });

        document
            .getElementById('cancel-initials-button')
            .addEventListener('click', closeInitialsDialog);

        document
            .getElementById('close-initials-dialog')
            .addEventListener('click', closeInitialsDialog);

        document
            .getElementById('accept-initials-button')
            .addEventListener('click', () => {
                if (initialsPad.isEmpty()) {
                    alert('Please provide your initials.');
                    return;
                }

                const dataUrl =
                    initialsPad.toDataURL('image/png');

                initialsInput.value = dataUrl;
                initialsDisplay.src = dataUrl;
                initialsDisplay.style.display = 'inline-block';
                initialsPlaceholder.style.display = 'none';

                document
                    .getElementById('initial-document-button')
                    .textContent = 'Replace Initials';

                closeInitialsDialog();
                updateCompletionState();
            });



        /*
         * Final browser-side validation.
         */

        form.addEventListener('submit', event => {
            const missing = getMissingRequirements();

            if (missing.length !== 0) {
                event.preventDefault();

                alert(
                    'Still required: ' +
                    missing
                    .map(requirement => requirement.label)
                    .join(', ') +
                    '.'
                );

                updateCompletionState();
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Submitting…';
        });





        updateCompletionState();
    });
</script>