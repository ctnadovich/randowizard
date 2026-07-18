<!-- Top bar -->
<div class="w3-dark-grey" style="padding:2px 8px;">
   <a href="<?= $this_waiver_url ?>" class="w3-text-white w3-button">
      Start Over
   </a>
</div>

<!-- Main content -->

<form id="waiver-form" method="post" action="/waiver/submit">
   <input type="hidden"
      name="waiver_session_id"
      value="<?= htmlspecialchars($waiver_session_id, ENT_QUOTES, 'UTF-8') ?>">

   <input type="hidden" name="signature_png" id="signature-png">
   <input type="hidden" name="initials_png" id="initials-png">

   <div id="waiver-document" class="w3-container w3-padding-32">


      <div class="w3-card w3-round-large w3-white w3-padding w3-margin-bottom">

         <div class="logo-card">

            <!-- Left column -->
            <div class="w3-center">
               <img src="<?= $indemnified_logo ?>"
                  alt="Logo"
                  class="w3-image"
                  style="max-width:180px;">
            </div>


            <!-- Right column -->
            <div>

               <p class="w3-center w3-text-red">
                  <?= $header ?>
               </p>

               <p class="w3-center">
                  <b>
                     <?= $title ?>
                  </b>
               </p>

            </div>

         </div>

      </div>


      <div class="w3-card w3-round-large w3-white w3-padding w3-margin-bottom">

         <p class="w3-large w3-text-red"><b><?= $initial ?></b></p>


         <!-- Initials location inside the document -->
         <div class="w3-margin-top">


            <div class="w3-panel w3-leftbar w3-border-blue w3-light-grey">

               <div id="initials-placeholder"
                  class="w3-text-grey w3-italic">
                  Not yet initialed
               </div>
               <div class="w3-padding-small">
                  <img id="initials-display"
                     alt="Participant initials"
                     style="
                     display:none;
                     max-width:140px;
                     max-height:75px;
                     vertical-align:middle;
                 ">
               </div>

            </div>

            <button type="button"
               id="initial-document-button"
               class="w3-button w3-small w3-blue">
               Initial
            </button>

         </div>

         <p><?= $preamble ?></p>


         <?php foreach ($clause as $c):  ?>
            <p><?= $c ?></p>
         <?php endforeach; ?>
         <p>Date: <?= $waiver_date ?>; Time: <?= $waiver_time ?></p>
         <p>Waiver ID: <?= $waiver_session_id ?></p>
         <p class="w3-center w3-text-red"><b><?= $footer ?></b></p>

      </div>

      <div class="w3-card w3-round-large w3-white w3-padding w3-margin-bottom">
         <h2>Rider Name</h2>


         <div class="w3-white w3-border w3-padding w3-xlarge">
            <strong><?= $rider_name ?></strong>
         </div>


         <h2>Rider Age Acknowledgement</h2>
         <p class="w3-large"><input class="w3-check"
               id="age-acknowledgement-checkbox"
               name="age-acknowledged"
               type="checkbox"
               value="1"> I certify that I am 18 years of age or older.</p>
         <h2>Event Information</h2>
         <h2>Rider Signature</h2>

         <!-- Signature location inside the document -->
         <div class="w3-margin-top">

            <div class="w3-panel w3-leftbar w3-border-blue w3-light-grey">

               <div id="signature-placeholder"
                  class="w3-text-grey w3-italic">
                  Not yet signed
               </div>

               <div class="w3-padding-small">

                  <img id="signature-display"
                     alt="Participant signature"
                     style="
                     display:none;
                     max-width:400px;
                     max-height:150px;
                 ">
               </div>

            </div>

            <button type="button"
               id="sign-document-button"
               class="w3-button w3-small w3-blue">
               Sign Document
            </button>

         </div>


      </div>

      <div class="w3-card w3-round-large w3-white w3-padding w3-margin-bottom">
         <h2>Electronic Signature Consent</h2>

         <div style="display:flex; align-items:flex-start; gap:12px;">

            <!-- Narrow checkbox column -->
            <div style="flex:0 0 auto; padding-top:4px;">
               <input class="w3-check"
                  id="acknowledgement-checkbox"
                  name="acknowledged"
                  type="checkbox"
                  value="1">
            </div>

            <!-- Text column -->
            <div style="flex:1;">
               <p style="margin-top:0;"><?= $esc ?></p>
            </div>

         </div>

      </div>


      <!-- Submit button -->
      <div class="w3-center w3-padding-32">
         <button type="submit"
            id="submit-document-button"
            class="w3-button w3-green"
            disabled>
            Agree To This Document
         </button>
         <div id="completion-message"
            class="w3-small w3-text-grey w3-margin-top">
            Signature, initials, and acknowledgements are required.
         </div>
      </div>

   </div>

</form>

<!-- Bottom bar -->
<div class="w3-dark-grey w3-center w3-text-white" style="padding:2px 8px;">
   Powered by <A HREF='https://randonneuring.org'>Randonneuring.Org</a>
</div>

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
      function updateCompletionState() {
         const hasSignature = signatureInput.value !== '';
         const hasInitials = initialsInput.value !== '';
         const acknowledged = acknowledgementCheckbox.checked;
         const ageAcknowledged = ageAcknowledgementCheckbox.checked;

         const complete =
            hasSignature &&
            hasInitials &&
            ageAcknowledged &&
            acknowledged;

         submitButton.disabled = !complete;

         const missing = [];


         if (!hasInitials) {
            missing.push('initials');
         }

         if (!ageAcknowledged) {
            missing.push('Age acknowledgement');
         }

         if (!acknowledged) {
            missing.push('Electronic signature acknowledgement');
         }

         if (!hasSignature) {
            missing.push('signature');
         }


         if (complete) {
            completionMessage.textContent =
               'The document is ready to submit.';
         } else {
            completionMessage.textContent =
               'Still required: ' + missing.join(', ') + '.';
         }
      }

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
       * Checkbox behavior.
       */
      acknowledgementCheckbox.addEventListener(
         'change',
         updateCompletionState
      );

      ageAcknowledgementCheckbox.addEventListener(
         'change',
         updateCompletionState
      );

      /*
       * Final browser-side validation.
       */
      form.addEventListener('submit', event => {
         const hasSignature = signatureInput.value !== '';
         const hasInitials = initialsInput.value !== '';
         const acknowledged = acknowledgementCheckbox.checked;

         if (!hasSignature || !hasInitials || !acknowledged) {
            event.preventDefault();

            alert(
               'Please provide your signature, initials, ' +
               'and acknowledgement before submitting.'
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