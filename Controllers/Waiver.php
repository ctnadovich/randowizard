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
use Psr\Log\LoggerInterface;

class Waiver extends EventProcessor
{
    private $waiverSessionModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->waiverSessionModel = model('WaiverSession');
    }

    public function waiver(string $event_code, string $participant_id)
    {

        try {

            // Validate Event
            $event = $this->eventModel->eventByCode($event_code);
            $edata = $this->get_event_data($event);

            if (empty($edata) || empty($edata['event_code'])) {
                throw new \RuntimeException("Event data missing");
            }

            $event_name = $edata['event_name_dist'];
            $event_date = $edata['event_date_str'];
            $event_time = $edata['event_time_str'];

            $event_tagname = $edata['event_tagname'];
            $event_is_rusa = $edata['is_rusa'];

            // Validate Organizing Club
            $club_acp_code = $edata['club_acp_code'];
            $club = $this->regionModel->getClub($club_acp_code);
            $organizing_club = $club['club_name'];


            // Time
            $event_timezone_name = $club['event_timezone_name'];  // For now, events can't have individual TZ
            $event_tz = new \DateTimeZone($event_timezone_name);
            $now = new \DateTime('now', $event_tz);


            // Validate Participant
            if (empty($participant_id)) {
                throw new \RuntimeException("Rider ID not specified.");
            }
            $rider = [];
            foreach ($edata['roster'] as $r) {
                if ($r['rider_id'] == $participant_id) {
                    $participant = $r;
                    break;
                }
            }
            if (empty($participant)) {
                throw new \RuntimeException("Participant ID $participant_id not found in roster for event $event_code.");
            }
            $participant_name = $participant['first_name'] . ' ' . $participant['last_name'];

            // Create waiver instance
            $session_id = bin2hex(random_bytes(16));
            $waiver_date = $now->format('Y-m-d');
            $waiver_time = $now->format('H:i:s T');
            $ex = clone $now;
            $ex->add(new \DateInterval('PT1H'));
            $expires_at = $ex->format('c');
            $this_waiver_url = site_url("waiver/$event_code/$participant_id");

            // Fetch and Validate Waiver Template 
            if ($event_is_rusa) {
                $template_name = 'rusa_waiver_template.txt'; // TODO This shouldn't be hardcoded -- TODO should be full URL?? 
                $waiver_view = 'rusa_waiver'; // TODO This shouldn't be hardcoded. 
            } else {
                throw new \RuntimeException("Waiver for non-RUSA event is not defined.");
            }
            $waiverTemplate =  new \App\Libraries\WaiverTemplate($template_name);

            // Replacements derived from Template
            $logo_url = trim($waiverTemplate->data['LOGO'][0] ?? '');
            if (empty($waiver_logo_url)) $waiver_logo_url = "https://randonneuring.org/assets/local/images/rusa-logo.png"; // 
            if (filter_var($waiver_logo_url, FILTER_VALIDATE_URL) === false) {
                throw new \RuntimeException("Invalid Waiver LOGO URL: $waiver_logo_url");
            }

            $revision = trim($waiverTemplate->data['REVISION'][0] ?? '');
            if (empty($revision)) {
                throw new \RuntimeException("REVISION not specified in template: $template_name");
            }

            // Create Waiver Session

            $waiverSession = $this->waiverSessionModel->createOrReuseSession(
                eventCode: $event_code,
                participantId: $participant_id,
                templateName: $template_name,
                revision: $revision,
            );

            $session_id = $waiverSession['session_id'];
            $created_at = $waiverSession['created_at'];
            $expires_at = $waiverSession['expires_at'];

            $created_utc = new \DateTimeImmutable(
                $created_at,
                new \DateTimeZone('UTC')
            );

            $created_local = $created_utc->setTimezone($event_tz);

            $waiver_timestamp =  $created_local->format('F j, Y \a\t g:i A T \(P\)');

            // Instance variables (and replacements) related to the waiver instance

            $sessionData = compact([
                'event_code',
                'participant_id',
                'template_name',
                'revision',
                'session_id',
                'waiver_timestamp',
                'created_at',  // UTC
                'expires_at'  // UTC
            ]);

            $waiverReplacements = compact([
                'logo_url',
                'this_waiver_url'
            ]);


            // Event / Rider / Club / Waiver context replacements
            // This list of replacements can be derived entirely from 
            // the event code and the rider ID when the randonneuring.org
            // event processor is available, and the event is defined 
            // in that database. OTOH, for a non-randonneuring.org user, 
            // all these replacements need to be provided. 

            $eventClubRiderReplacements = compact([

                // Event
                'event_name', // typically includes distance
                'event_date',
                'event_time',
                'event_timezone_name',

                // Club
                'organizing_club',
                'club_acp_code',

                // Rider
                'participant_name',

                // Waiver
                'event_is_rusa',
                'template_name',
                'waiver_view'

            ]);


            // Interpolate the template
            $interpolated_template =
                $waiverTemplate->interpolate_template(array_merge(
                    $sessionData,
                    $waiverReplacements,
                    $eventClubRiderReplacements
                ));

            // Create content map of template sections

            // keep only the first of these
            $title = $interpolated_template['TITLE'][0] ?? '';
            $header = $interpolated_template['HEADER'][0] ?? '';
            $initial = $interpolated_template['INITIAL'][0] ?? '';
            $preamble = $interpolated_template['PREAMBLE'][0] ?? '';
            $footer = $interpolated_template['FOOTER'][0] ?? '';
            $signature = $interpolated_template['SIGNATURE'][0] ?? '';
            $esc = $interpolated_template['ESC'][0] ?? '';

            // but use all of the clauses
            $clause = $interpolated_template['CLAUSE'] ?? [];

            $sectionMap = compact(['title', 'header', 'initial', 'preamble', 'footer', 'revision', 'clause', 'esc', 'signature']);

            // Overall replacement map
            $replacementMap = array_merge($sessionData, $waiverReplacements, $eventClubRiderReplacements, $sectionMap);

            // And render the waiver as HTML

            $this->viewData = array_merge($this->viewData, $replacementMap);
            $this->viewData['style_head'] = view('default_style_head', $this->viewData);
            $this->viewData['body_style'] = 'class="w3-light-grey"';

            $views =  view('head', $this->viewData);
            $views .=  view($waiver_view, $this->viewData);
            $views .=  view('foot', $this->viewData);

            return $views;
        } catch (\Exception $e) {
            $this->die_exception($e);
        }
    }
}
