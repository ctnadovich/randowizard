<?php

//    Randonneuring.org Website Software
//    Copyright (C) 2023 Chris Nadovich
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    https://randonneuring.org/LICENSE.txt
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <https://www.gnu.org/licenses/>.

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

use Dompdf\Dompdf;
use Dompdf\Options;

use Psr\Log\LoggerInterface;
use App\Libraries\WaiverStorage;
use App\Libraries\WaiverContext;

use App\Models\EventWaiverContext;
use App\Models\WaiverSession;
use App\Models\WaiverAccessLog;


class Waiver extends EventProcessor
{

    private WaiverSession $waiverSessionModel;
    private WaiverStorage $waiverStorage;
    private WaiverContext $waiverContext;
    private EventWaiverContext $eventWaiverContextModel;
    private WaiverAccessLog $waiverAccessLogModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->waiverSessionModel = model('WaiverSession');
        $this->waiverStorage = new WaiverStorage();
        $this->eventWaiverContextModel = model('EventWaiverContext');
        $this->waiverAccessLogModel = model('WaiverAccessLog');

        $this->waiverContext = new WaiverContext(
            eventModel: $this->eventModel,
            regionModel: $this->regionModel,
            eventWaiverContextModel: $this->eventWaiverContextModel,
            waiverSessionModel: $this->waiverSessionModel,
            rosterModel: $this->rosterModel
        );
    }

    public function start(
        string $event_code,
        string $participant_id
    ) {
        try {
            helper('waiver_helper');

            $waiver_data = $this->waiverContext
                ->createFromLocalData(
                    $event_code,
                    $participant_id
                );

            $waiver_session =
                $waiver_data['waiverSession'];

            $this->recordWaiverAccess(
                $waiver_session,
                WaiverAccessLog::METHOD_LOCAL_START
            );


            return $this->renderWaiverForm(
                $waiver_data
            );
        } catch (\Throwable $e) {
            return $this->die_exception($e);
        }
    }

    public function startExternal()
    {
        try {
            $region = $this->authenticateExternalRequest();

            $submitted_data = $this->request->getJSON(true);

            if (!is_array($submitted_data)) {
                throw new \RuntimeException(
                    'Request body must contain a JSON object.'
                );
            }

            $contextData = $this->waiverContext
                ->normalizeExternalContext(
                    $submitted_data,
                    $region
                );

            $waiverData = $this->waiverContext
                ->createFromExternalData(
                    $contextData
                );

            $waiver_session =
                $waiverData['waiverSession'];

            $session_id =
                (string) $waiver_session['session_id'];

            $waiver_url = site_url(
                'waiver/session/'
                    . rawurlencode($session_id)
            );

            $document_url = site_url(
                'waiver/document/'
                    . rawurlencode($session_id)
            );

                       $reference_url = site_url(
                'waiver/reference/'
                    . rawurlencode($session_id)
            );

            return $this->response->setJSON([
                'waiver_session_id' => $session_id,
                'waiver_url'        => $waiver_url,
                'document_url'        => $document_url,
                'reference_url'        => $reference_url,
                'expires_at'        =>
                $waiver_session['expires_at'],
            ]);
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'error' => $e->getMessage(),
                ]);
        }
    }


    /**
     * Authenticate an external waiver-session request.
     *
     * @return array<string, mixed> Authenticated region row
     */
    private function authenticateExternalRequest(): array
    {
        $club_acp_code = trim(
            (string) $this->request->getServer(
                'PHP_AUTH_USER'
            )
        );

        $api_key = (string) $this->request->getServer(
            'PHP_AUTH_PW'
        );

        if ($club_acp_code === '' || $api_key === '') {
            throw new \RuntimeException(
                'External API credentials are required.'
            );
        }

        $region = $this->regionModel->authenticateWaiverApi(
            $club_acp_code,
            $api_key
        );

        if ($region === null) {
            throw new \RuntimeException(
                'Invalid external API credentials.'
            );
        }

        return $region;
    }

    public function session(string $session_id)
    {
        try {
            helper('waiver_helper');

            $waiver_session = $this->waiverSessionModel
                ->getActiveSession($session_id);

            if (empty($waiver_session)) {
                throw new \RuntimeException(
                    'This waiver session is invalid, expired, '
                        . 'or already completed.'
                );
            }

            $this->recordWaiverAccess(
                $waiver_session,
                WaiverAccessLog::METHOD_SIGNER_START
            );

            $waiver_data = $this->waiverContext
                ->buildFromSession($waiver_session);

            return $this->renderWaiverForm(
                $waiver_data
            );
        } catch (\Throwable $e) {
            return $this->die_exception($e);
        }
    }

    private const DEFAULT_THEME = 'default_theme';

    private static function themeView(string $viewName): string
    {
        return sprintf(
            'waiver/%s/%s',
            self::DEFAULT_THEME,
            $viewName
        );
    }

    private function renderWaiverForm(array $waiverData)
    {
        $replacementMap = $waiverData['replacementMap'];

        $documentData = array_merge(
            $replacementMap,
            [
                'render_mode' => 'html',
                'signature_png' => '',
                'initials_png' => '',
                'age_acknowledged' => false,
                'acknowledged' => false,
            ]
        );

        $this->viewData = array_merge(
            $this->viewData,
            $replacementMap,
            [
                'documentData' => $documentData,
            ]
        );

        $this->viewData['style_head'] = view(
            'default_style_head',
            $this->viewData
        );

        $this->viewData['body_style'] =
            'class="w3-light-grey"';

        return
            view('head', $this->viewData)
            . view(self::themeView('form'), $this->viewData)
            . view('foot', $this->viewData);
    }


    public function finalize()
    {
        try {
            helper('waiver_helper');

            $requirements = [
                'waiver_session_id' => [
                    'label' => 'Waiver session ID',
                    'value' => trim(
                        (string) $this->request->getPost(
                            'waiver_session_id'
                        )
                    ),
                    'valid' => static fn(string $value): bool =>
                    preg_match(
                        '/\A[0-9a-f]{32}\z/',
                        $value
                    ) === 1,
                ],

                'signature_png' => [
                    'label' => 'Participant signature',
                    'value' => trim(
                        (string) $this->request->getPost(
                            'signature_png'
                        )
                    ),
                    'valid' => static fn(string $value): bool =>
                    $value !== '',
                ],

                'initials_png' => [
                    'label' => 'Participant initials',
                    'value' => trim(
                        (string) $this->request->getPost(
                            'initials_png'
                        )
                    ),
                    'valid' => static fn(string $value): bool =>
                    $value !== '',
                ],

                'age-acknowledged' => [
                    'label' => 'Age acknowledgement',
                    'value' => (string) $this->request->getPost(
                        'age-acknowledged'
                    ),
                    'valid' => static fn(string $value): bool =>
                    $value === '1',
                ],

                'acknowledged' => [
                    'label' => 'Electronic signature consent',
                    'value' => (string) $this->request->getPost(
                        'acknowledged'
                    ),
                    'valid' => static fn(string $value): bool =>
                    $value === '1',
                ],
            ];

            /*
         * Client-side validation is useful for the user interface,
         * but all requirements must be independently enforced here.
         */
            foreach ($requirements as $requirement) {
                if (!$requirement['valid']($requirement['value'])) {
                    throw new \RuntimeException(
                        $requirement['label']
                            . ' is missing or invalid.'
                    );
                }
            }

            $session_id =
                $requirements['waiver_session_id']['value'];

            $signature_png =
                $requirements['signature_png']['value'];

            $initials_png =
                $requirements['initials_png']['value'];

            /*
         * Validate the submitted image fields and decode them into
         * binary PNG data.
         */
            $signature_bytes = $this->decodePngDataUrl(
                $signature_png,
                'signature'
            );

            $initials_bytes = $this->decodePngDataUrl(
                $initials_png,
                'initials'
            );

            /*
         * Load the authoritative pending waiver session.
         */
            $waiver_session =
                $this->waiverSessionModel->getActiveSession(
                    $session_id
                );

            if (empty($waiver_session)) {
                throw new \RuntimeException(
                    'This waiver session is invalid, expired, '
                        . 'or already completed. '
                        . "ID=$session_id"
                );
            }

            /*
         * Reconstruct the waiver using the immutable event context
         * and the participant/session fields stored in the waiver row.
         */
            $waiver_data = $this->waiverContext
                ->buildFromSession($waiver_session);

            $event_context = $waiver_data['eventContext'];

            $event_code =
                (string) $event_context['event_code'];

            $participant_id =
                (string) $waiver_session['participant_id'];

            $template_name =
                (string) $event_context['template_name'];

            $revision =
                (string) $event_context['revision'];

            $replacement_map =
                $waiver_data['replacementMap'];

            /*
         * Add the submitted marks and acknowledgements to the
         * document-generation data.
         */
            $replacement_map['signature_png_bytes'] =
                $signature_bytes;

            $replacement_map['initials_png_bytes'] =
                $initials_bytes;

            $replacement_map['age_acknowledged'] =
                $requirements['age-acknowledged']['value'];

            $replacement_map['acknowledged'] =
                $requirements['acknowledged']['value'];

            /*
         * Render the signed PDF.
         */
            $pdf_bytes = $this->renderWaiverPdf([
                'replacementMap' => $replacement_map,
                'signature_png'  => $signature_png,
                'initials_png'   => $initials_png,
            ]);

            $document_sha256 = hash(
                'sha256',
                $pdf_bytes
            );

            /*
         * Construct a stable immutable-storage key.
         */
            $document_key = sprintf(
                '%s/%s/%s.pdf',
                $event_code,
                $participant_id,
                $session_id
            );

            /*
         * Store the document before marking the session completed.
         */
            $this->waiverStorage->storeImmutable(
                documentKey: $document_key,
                contents: $pdf_bytes,
                contentType: 'application/pdf',
                metadata: [
                    'session_id'     => $session_id,
                    'event_code'     => $event_code,
                    'participant_id' => $participant_id,
                    'template_name'  => $template_name,
                    'revision'       => $revision,
                    'sha256'         => $document_sha256,
                ]
            );

            /*
         * Atomically mark the pending, unexpired session completed.
         * completeSession() also enforces the event-start deadline.
         */
            $completed = $this->waiverSessionModel
                ->completeSession(
                    $session_id,
                    $document_key,
                    $document_sha256
                );

            if (!$completed) {
                throw new \RuntimeException(
                    'The waiver was stored, but the session '
                        . 'could not be marked completed.'
                );
            }

            $this->recordWaiverAccess(
                $waiver_session,
                WaiverAccessLog::METHOD_SIGNER_COMPLETED
            );

            unset(
                $signature_bytes,
                $initials_bytes,
                $pdf_bytes
            );

            return redirect()->to(
                site_url(
                    "waiver/completed/$session_id"
                )
            );
        } catch (\Throwable $e) {
            return $this->die_exception($e);
        }
    }

    public function completed(string $session_id)
    {
        $waiver_session =
            $this->waiverSessionModel->getCompletedSession(
                $session_id
            );

        if ($waiver_session === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException
                ::forPageNotFound();
        }

        $this->recordWaiverAccess(
            $waiver_session,
            WaiverAccessLog::METHOD_COMPLETED_VIEW
        );

        $waiverData = $this->waiverContext->buildFromSession(
            $waiver_session
        );

        $replacementMap =
            $waiverData['replacementMap'];

        $callback_url_template = trim(
            (string) ($waiver_session['callback_url'] ?? '')
        );

        $callback_url = null;

        if ($callback_url_template !== '') {
            $callback_url = $this->interpolateCallbackUrl(
                $callback_url_template,
                $replacementMap
            );
        }

        $replacementMap['status'] =
            $waiver_session['status'];

        $replacementMap['completed_at'] =
            $waiver_session['completed_at'];

        $viewData = array_merge(
            $this->viewData,
            $replacementMap,
            [
                'session'      => $waiver_session,
                'callback_url' => $callback_url,
            ]
        );

        $viewData['style_head'] = view(
            'default_style_head',
            $viewData
        );

        $viewData['body_style'] =
            'class="w3-light-grey"';

        return
            view('head', $viewData)
            . view(
                self::themeView('completed'),
                $viewData
            )
            . view('foot', $viewData);
    }

    private function interpolateCallbackUrl(
        string $callback_url_template,
        array $replacementMap
    ): string {
        $callback_url = preg_replace_callback(
            '/\{\{([A-Za-z0-9_]+)\}\}/',
            static function (array $matches) use (
                $replacementMap
            ): string {
                $field = $matches[1];

                if (!array_key_exists($field, $replacementMap)) {
                    throw new \RuntimeException(
                        "Undefined callback URL field: $field"
                    );
                }

                return rawurlencode(
                    (string) $replacementMap[$field]
                );
            },
            $callback_url_template
        );

        if ($callback_url === null) {
            throw new \RuntimeException(
                'Unable to interpolate callback URL.'
            );
        }

        if (
            filter_var(
                $callback_url,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new \RuntimeException(
                'The interpolated callback URL is invalid.'
            );
        }

        return $callback_url;
    }


    public function document(string $session_id)
    {
        $waiver_session =
            $this->waiverSessionModel->getCompletedSession(
                $session_id
            );

        if ($waiver_session === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException
                ::forPageNotFound(
                    'The completed waiver could not be found.'
                );
        }

        $document_key =
            (string) ($waiver_session['document_key'] ?? '');

        if ($document_key === '') {
            throw new \RuntimeException(
                'The completed waiver has no stored document key.'
            );
        }

        $event_context_id = (int) (
            $waiver_session['event_waiver_context_id'] ?? 0
        );

        if ($event_context_id <= 0) {
            throw new \RuntimeException(
                'The completed waiver has no event waiver context.'
            );
        }

        $event_context =
            $this->eventWaiverContextModel->requireById(
                $event_context_id
            );

        $event_code =
            (string) $event_context['event_code'];

        $participant_id =
            (string) $waiver_session['participant_id'];

        $pdf_bytes = $this->waiverStorage->retrieve(
            $document_key
        );

        $this->recordWaiverAccess(
            $waiver_session,
            WaiverAccessLog::METHOD_DOCUMENT_VIEW
        );

        $filename = sprintf(
            'waiver-%s-%s.pdf',
            $this->safeFilenamePart($event_code),
            $this->safeFilenamePart($participant_id)
        );

        return $this->response
            ->setHeader(
                'Content-Type',
                'application/pdf'
            )
            ->setHeader(
                'Content-Disposition',
                'inline; filename="' . $filename . '"'
            )
            ->setHeader(
                'Content-Length',
                (string) strlen($pdf_bytes)
            )
            ->setBody($pdf_bytes);
    }

    /**
     * Return waiver metadata, immutable event context, and access history.
     */
    public function reference(string $session_id)
    {
        try {
            $waiver_session =
                $this->waiverSessionModel->findBySessionId(
                    $session_id
                );

            if ($waiver_session === null) {
                throw \CodeIgniter\Exceptions\PageNotFoundException
                    ::forPageNotFound(
                        'The waiver session could not be found.'
                    );
            }

            $waiver_id = (int) (
                $waiver_session['id'] ?? 0
            );

            if ($waiver_id <= 0) {
                throw new \RuntimeException(
                    'The waiver session has no valid waiver ID.'
                );
            }

            $event_waiver_context_id = (int) (
                $waiver_session['event_waiver_context_id'] ?? 0
            );

            if ($event_waiver_context_id <= 0) {
                throw new \RuntimeException(
                    'The waiver session has no event waiver context.'
                );
            }

            $event_context =
                $this->eventWaiverContextModel->requireById(
                    $event_waiver_context_id
                );

            /*
         * Record the lookup before retrieving the access history so
         * this request is included in the returned log.
         */
            $this->recordWaiverAccess(
                $waiver_session,
                WaiverAccessLog::METHOD_REFERENCE_VIEW
            );

            $access_log =
                $this->waiverAccessLogModel->findByWaiverId(
                    $waiver_id
                );

            /*
         * Build waiver metadata from the model's structural field lists.
         */
            $waiver_metadata = array_merge(
                $this->waiverContext->selectFields(
                    $waiver_session,
                    WaiverSession::SESSION_IDENTITY_FIELDS
                ),
                $this->waiverContext->selectFields(
                    $waiver_session,
                    WaiverSession::ACTIVE_SESSION_FIELDS
                ),
                $this->waiverContext->selectFields(
                    $waiver_session,
                    WaiverSession::COMPLETION_FIELDS
                )
            );

            /*
         * Remove internal relational and storage implementation fields.
         */
            unset(
                $waiver_metadata['event_waiver_context_id'],
                $waiver_metadata['document_key']
            );

            $waiver_metadata['document_url'] =
                $waiver_session['status']
                === WaiverSession::STATUS_COMPLETED
                ? site_url(
                    'waiver/document/'
                        . rawurlencode($session_id)
                )
                : null;

            /*
         * Select only the immutable event fields, excluding the event
         * context row's internal ID and creation metadata.
         */
            $event_metadata = $this->waiverContext->selectFields(
                $event_context,
                EventWaiverContext::EVENT_CONTEXT_FIELDS
            );

            /*
         * Select only public access-event fields from each log row.
         */
            $access_history = array_map(
                fn(array $access_entry): array =>
                $this->waiverContext->selectFields(
                    $access_entry,
                    WaiverAccessLog::ACCESS_FIELDS
                ),
                $access_log
            );

            $referenceData = [
                'waiver'        => $waiver_metadata,
                'event_context' => $event_metadata,
                'access_log'    => $access_history,
            ];

            return $this->response->setJSON(
                $referenceData
            );
        } catch (
            \CodeIgniter\Exceptions\PageNotFoundException $e
        ) {
            throw $e;
        } catch (\Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'error' => $e->getMessage(),
                ]);
        }
    }

    private function safeFilenamePart(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value);

        return trim($value ?? '', '-');
    }

    private const MAX_ENCODED_IMAGE_BYTES = 2_000_000;

    private function decodePngDataUrl(
        string $data_url,
        string $field_name
    ): string {
        $prefix = 'data:image/png;base64,';

        if (!str_starts_with($data_url, $prefix)) {
            throw new \RuntimeException(
                "Invalid $field_name image format."
            );
        }

        $encoded = substr(
            $data_url,
            strlen($prefix)
        );

        if ($encoded === '') {
            throw new \RuntimeException(
                "Empty $field_name image."
            );
        }

        /*
     * Limit the encoded input before decoding.
     *
     * Signature Pad images should normally be much smaller than
     * this. This mainly prevents an unexpectedly large POST from
     * consuming excessive memory.
     */
        if (
            strlen($encoded)
            > self::MAX_ENCODED_IMAGE_BYTES
        ) {
            throw new \RuntimeException(
                ucfirst($field_name)
                    . ' image is too large.'
            );
        }

        $bytes = base64_decode($encoded, true);

        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException(
                "Invalid Base64 data for $field_name."
            );
        }

        /*
     * Every PNG begins with this eight-byte signature.
     */
        $png_signature = "\x89PNG\r\n\x1a\n";

        if (
            !str_starts_with(
                $bytes,
                $png_signature
            )
        ) {
            throw new \RuntimeException(
                "Submitted $field_name is not a PNG image."
            );
        }

        return $bytes;
    }

    private function renderWaiverPdf(array $pdfData): string
    {
        $html = view(self::themeView('pdf'), $pdfData);

        $options = new Options();

        $options->setDefaultFont('DejaVu Sans');

        /*
     * Needed because the waiver logo currently uses an HTTPS URL.
     */
        $options->setIsRemoteEnabled(true);

        /*
     * Restrict remote image retrieval to your own host.
     * Available in recent Dompdf releases.
     */
        $options->setAllowedRemoteHosts([
            'randonneuring.org',
        ]);

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $pdf_bytes = $dompdf->output();

        if (
            !is_string($pdf_bytes)
            || $pdf_bytes === ''
            || !str_starts_with($pdf_bytes, '%PDF-')
        ) {
            throw new \RuntimeException(
                'PDF generation failed.'
            );
        }

        return $pdf_bytes;
    }

    private function recordWaiverAccess(
        array $waiver_session,
        string $method
    ): void {
        $waiver_id = (int) (
            $waiver_session['id'] ?? 0
        );

        if ($waiver_id <= 0) {
            throw new \RuntimeException(
                'Waiver session has no valid database ID.'
            );
        }

        $ip_address = $this->request->getIPAddress();

        $user_agent = trim(
            (string) $this->request
                ->getUserAgent()
                ->getAgentString()
        );

        $this->waiverAccessLogModel->record(
            $waiver_id,
            $method,
            $ip_address,
            $user_agent !== '' ? $user_agent : null
        );
    }
}
