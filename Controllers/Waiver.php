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
use DateTimeZone;
use DateTimeImmutable;
// use RuntimeException;
use Throwable;

use Dompdf\Dompdf;
use Dompdf\Options;

use Psr\Log\LoggerInterface;
use CodeIgniter\HTTP\RedirectResponse;
use App\Models\WaiverSessionModel;
use App\Libraries\WaiverStorage;

class Waiver extends EventProcessor
{

    private const MAX_SIGNATURE_BYTES = 2_000_000;

    private WaiverSessionModel $waiverSessionModel;
    private WaiverStorage $waiverStorage;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->waiverSessionModel = model('WaiverSession');
        $this->waiverStorage =
            new WaiverStorage();
    }

    public function start(
        string $event_code,
        string $participant_id
    ) {
        try {
            helper('waiver_helper');

            $waiverData = $this->buildWaiverData(
                $event_code,
                $participant_id
            );

            $replacementMap =
                $waiverData['replacementMap'];

            $waiver_view =
                $waiverData['waiverView'];

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
                . view($waiver_view, $this->viewData)
                . view('foot', $this->viewData);
        } catch (\Throwable $e) {
            return $this->die_exception($e);
        }
    }

    /**
     * Build and validate all authoritative waiver data.
     *
     * When $waiverSession is null, a session is created or reused.
     * When supplied, the session's event, participant, template and revision
     * are treated as authoritative and verified.
     *
     * @return array<string, mixed>
     */
    private function buildWaiverData(
        string $event_code,
        string $participant_id,
        ?array $waiverSession = null
    ): array {
        // Validate event.
        $event = $this->eventModel->eventByCode($event_code);

        if (empty($event)) {
            throw new \RuntimeException(
                "Event $event_code was not found."
            );
        }

        $edata = $this->get_event_data($event);

        if (empty($edata) || empty($edata['event_code'])) {
            throw new \RuntimeException(
                "Event data missing for $event_code."
            );
        }

        $event_name = $edata['event_name_dist'];
        $event_date = $edata['event_date_str'];
        $event_time = $edata['event_time_str'];
        $event_tagname = $edata['event_tagname'];
        $event_is_rusa = $edata['is_rusa'];

        // Validate organizing club.
        $club_acp_code = $edata['club_acp_code'];
        $club = $this->regionModel->getClub($club_acp_code);

        if (empty($club)) {
            throw new \RuntimeException(
                "Organizing club $club_acp_code was not found."
            );
        }

        if (empty($club['club_name'])) {
            throw new \RuntimeException(
                "Club name missing for $club_acp_code."
            );
        }

        if (empty($club['event_timezone_name'])) {
            throw new \RuntimeException(
                "Event timezone missing for club $club_acp_code."
            );
        }

        $organizing_club = $club['club_name'];
        $event_timezone_name = $club['event_timezone_name'];
        $event_tz = new \DateTimeZone($event_timezone_name);

        // Validate participant.
        if ($participant_id === '') {
            throw new \RuntimeException(
                'Rider ID not specified.'
            );
        }

        $participant = [];

        foreach ($edata['roster'] as $r) {
            if ((string) $r['rider_id'] === $participant_id) {
                $participant = $r;
                break;
            }
        }

        if (empty($participant)) {
            throw new \RuntimeException(
                "Participant ID $participant_id not found "
                    . "in roster for event $event_code."
            );
        }

        $participant_name =
            $participant['first_name']
            . ' '
            . $participant['last_name'];

        // Select template and view.
        if ($event_is_rusa) {
            $template_name = 'rusa_waiver_template.txt';
            $waiver_view = 'waiver/rusa_waiver_form';
        } else {
            throw new \RuntimeException(
                'Waiver for non-RUSA event is not defined.'
            );
        }

        /*
     * If finalizing an existing session, its stored template name must
     * agree with the template selected for this event.
     */
        if (
            $waiverSession !== null
            && $waiverSession['template_name'] !== $template_name
        ) {
            throw new \RuntimeException(
                'Waiver template does not match the stored session.'
            );
        }

        $waiverTemplate =
            new \App\Libraries\WaiverTemplate($template_name);

        // Template-derived values.
        $waiver_logo_url = trim(
            $waiverTemplate->data['LOGO'][0] ?? ''
        );

        if ($waiver_logo_url === '') {
            $waiver_logo_url =
                'https://randonneuring.org/assets/'
                . 'local/images/rusa-logo.png';
        }

        if (
            filter_var(
                $waiver_logo_url,
                FILTER_VALIDATE_URL
            ) === false
        ) {
            throw new \RuntimeException(
                "Invalid Waiver LOGO URL: $waiver_logo_url"
            );
        }

        $revision = trim(
            $waiverTemplate->data['REVISION'][0] ?? ''
        );

        if ($revision === '') {
            throw new \RuntimeException(
                "REVISION not specified in template: $template_name"
            );
        }

        /*
     * Either create/reuse a session or verify the existing one.
     */
        if ($waiverSession === null) {
            $waiverSession =
                $this->waiverSessionModel->createOrReuseSession(
                    eventCode: $event_code,
                    participantId: $participant_id,
                    templateName: $template_name,
                    revision: $revision,
                );
        } else {
            if (
                $waiverSession['event_code'] !== $event_code
                || $waiverSession['participant_id'] !== $participant_id
            ) {
                throw new \RuntimeException(
                    'Waiver session context does not match.'
                );
            }

            if ($waiverSession['revision'] !== $revision) {
                throw new \RuntimeException(
                    'The waiver template revision has changed '
                        . 'since this session was created.'
                );
            }
        }

        $session_id = $waiverSession['session_id'];
        $created_at = $waiverSession['created_at'];
        $expires_at = $waiverSession['expires_at'];

        $created_utc = new \DateTimeImmutable(
            $created_at,
            new \DateTimeZone('UTC')
        );

        $created_local =
            $created_utc->setTimezone($event_tz);

        $waiver_timestamp = $created_local->format(
            'F j, Y \a\t g:i A T \(P\)'
        );

        $this_waiver_url = site_url(
            "waiver/start/$event_code/$participant_id"
        );

        $sessionData = compact([
            'event_code',
            'participant_id',
            'template_name',
            'revision',
            'session_id',
            'waiver_timestamp',
            'created_at',
            'expires_at',
        ]);

        $waiverReplacements = compact([
            'waiver_logo_url',
            'this_waiver_url',
        ]);

        $eventClubRiderReplacements = compact([
            'event_name',
            'event_date',
            'event_time',
            'event_timezone_name',
            'organizing_club',
            'club_acp_code',
            'participant_name',
            'event_is_rusa',
            'template_name',
            'waiver_view',
        ]);

        $interpolated_template =
            $waiverTemplate->interpolate_template(
                array_merge(
                    $sessionData,
                    $waiverReplacements,
                    $eventClubRiderReplacements
                )
            );

        $title =
            $interpolated_template['TITLE'][0] ?? '';

        $header =
            $interpolated_template['HEADER'][0] ?? '';

        $initial =
            $interpolated_template['INITIAL'][0] ?? '';

        $preamble =
            $interpolated_template['PREAMBLE'][0] ?? '';

        $footer =
            $interpolated_template['FOOTER'][0] ?? '';

        $signature =
            $interpolated_template['SIGNATURE'][0] ?? '';

        $esc =
            $interpolated_template['ESC'][0] ?? '';

        $clause =
            $interpolated_template['CLAUSE'] ?? [];

        $sectionMap = compact([
            'title',
            'header',
            'initial',
            'preamble',
            'footer',
            'revision',
            'clause',
            'esc',
            'signature',
        ]);

        $replacementMap = array_merge(
            $sessionData,
            $waiverReplacements,
            $eventClubRiderReplacements,
            $sectionMap
        );

        /*
     * Return both the flattened replacement map and a few useful
     * authoritative objects.
     */
        return [
            'replacementMap' => $replacementMap,
            'waiverSession' => $waiverSession,
            'waiverTemplate' => $waiverTemplate,
            'eventData' => $edata,
            'club' => $club,
            'participant' => $participant,
            'eventTimezone' => $event_tz,
            'waiverView' => $waiver_view,
        ];
    }

    public function finalize()
    {
        try {

            helper('waiver_helper');
            $requirements = [
                'waiver_session_id' => [
                    'label' => 'Waiver session ID',
                    'value' => trim((string) $this->request->getPost(
                        'waiver_session_id'
                    )),
                    'valid' => static fn(string $value): bool =>
                    preg_match('/\A[0-9a-f]{32}\z/', $value) === 1,
                ],

                'signature_png' => [
                    'label' => 'Participant signature',
                    'value' => trim((string) $this->request->getPost(
                        'signature_png'
                    )),
                    'valid' => static fn(string $value): bool =>
                    $value !== '',
                ],

                'initials_png' => [
                    'label' => 'Participant initials',
                    'value' => trim((string) $this->request->getPost(
                        'initials_png'
                    )),
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
            * Validate the required fields.
            *
            * Client-side validation is useful for the user interface,
            * but all requirements must be independently enforced here.
            */

            foreach ($requirements as $requirement) {
                if (!$requirement['valid']($requirement['value'])) {
                    throw new \RuntimeException(
                        $requirement['label'] . ' is missing or invalid.'
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
         * Validate that both image fields are PNG data URLs and
         * decode them into binary PNG data.
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
            * Load the authoritative waiver session.
            *
            * Do not trust event code, participant ID, template name,
            * or revision from browser input. None of those fields are
            * posted by the form anyway.
            */

            $waiverSession =
                $this->waiverSessionModel
                ->getActiveSession($session_id);

            if (empty($waiverSession)) {
                throw new \RuntimeException(
                    "This waiver session is invalid, expired, "
                        . "or already completed. ID=$session_id"
                );
            }

            $event_code =
                (string) $waiverSession['event_code'];

            $participant_id =
                (string) $waiverSession['participant_id'];

            /*
            * Reconstruct and validate the same waiver content that
            * was used by waiver().
            *
            * This assumes buildWaiverData() accepts the existing
            * session as its third argument and does not create or
            * reset a session when one is supplied.
            */

            $waiverData = $this->buildWaiverData(
                $event_code,
                $participant_id,
                $waiverSession
            );

            $replacementMap =
                $waiverData['replacementMap'];

            /*
            * Add the submitted marks to the document-generation data.
            *
            * These are binary PNG strings. Whether the PDF generator
            * accepts bytes, temporary filenames, or data URLs will
            * determine the eventual representation used here.
            */

            $replacementMap['signature_png_bytes'] =
                $signature_bytes;

            $replacementMap['initials_png_bytes'] =
                $initials_bytes;

            $replacementMap['age_acknowledged'] = $requirements['age-acknowledged']['value'];
            $replacementMap['acknowledged'] = $requirements['acknowledged']['value'];


            /*
         * Render the signed PDF using the shared document partial.
         */
            $pdf_bytes = $this->renderWaiverPdf([
                'replacementMap' => $replacementMap,
                'signature_png' => $signature_png,
                'initials_png' => $initials_png,
            ]);

            $document_sha256 = hash(
                'sha256',
                $pdf_bytes
            );

            /*
         * Construct a stable storage key.
         *
         */
            $document_key = sprintf(
                '%s/%s/%s.pdf',
                $event_code,
                $participant_id,
                $session_id
            );
            /*
         * Store first. Do not mark the session completed unless
         * storage succeeds.
         */
            $this->waiverStorage->storeImmutable(
                documentKey: $document_key,
                contents: $pdf_bytes,
                contentType: 'application/pdf',
                metadata: [
                    'session_id' => $session_id,
                    'event_code' => $event_code,
                    'participant_id' => $participant_id,
                    'template_name' =>
                    $waiverSession['template_name'],
                    'revision' =>
                    $waiverSession['revision'],
                    'sha256' => $document_sha256,
                ]
            );

            /*
         * Complete the session only after durable storage.
         *
         * Ideally this model update should require status='active'
         * so duplicate submissions cannot both complete it.
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

            /*
         * Clear large variables as soon as practical.
         */
            unset(
                $signature_bytes,
                $initials_bytes,
                $pdf_bytes
            );

            return redirect()->to(
                site_url("waiver/completed/$session_id")
            );
        } catch (\Throwable $e) {
            return $this->die_exception($e);
        }
    }

    public function completed(string $session_id)
    {
        $session = $this->waiverSessionModel->getCompletedSession($session_id);

        if ($session === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($session['status'] !== WaiverSessionModel::STATUS_COMPLETED) {
            return redirect()->to(site_url("waiver/start/$session_id"));
        }

        $viewData = [
            'session' => $session,
        ];


        return
            view('head', $viewData)
            . view('waiver/completed', $viewData)
            . view('foot', $viewData);
    }

    public function document(string $sessionId)
    {
        $session = $this->waiverSessionModel
            ->getCompletedSession($sessionId);

        if ($session === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'The completed waiver could not be found.'
            );
        }

        if (empty($session['document_key'])) {
            throw new \RuntimeException(
                'The completed waiver has no stored document key.'
            );
        }

        $pdf = $this->waiverStorage->retrieve(
            $session['document_key']
        );

        $filename = sprintf(
            'waiver-%s-%s.pdf',
            $this->safeFilenamePart($session['event_code']),
            $this->safeFilenamePart($session['participant_id'])
        );
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader(
                'Content-Disposition',
                'inline; filename="' . $filename . '"'
            )
            ->setHeader('Content-Length', (string) strlen($pdf))
            ->setBody($pdf);
    }

    private function safeFilenamePart(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value);

        return trim($value ?? '', '-');
    }

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
        $maximum_encoded_size = 2 * 1024 * 1024;

        if (strlen($encoded) > $maximum_encoded_size) {
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
        $html = view('waiver/rusa_waiver_pdf', $pdfData);

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
}
