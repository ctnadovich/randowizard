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
use Psr\Log\LoggerInterface;

use App\Libraries\GroceryCrud;
use stdClass;
use CodeIgniter\Files\File;

class EventsCrud extends BaseController
{

    protected $helpers = ['form'];
    protected $rosterModel;
    protected $eventModel;

    protected $user_id;
    protected $authorized_regions;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->rosterModel =  model('Roster');
        $this->eventModel =  model('Event');

        $this->user_id = $this->session->get('user_id');
        $this->authorized_regions = $this->session->get('authorized_regions');
    }

    public function events($timerange = 'all')
    {

        $this->login_check();

        $rbaModel = model('Rba');
        $member_id = $this->getMemberID();

        if ($this->isSuperuser()) {
            $region_underscore_where_clause = null;
            $region_dot_where_clause = null;
        } else {
            $authorized_regions = $rbaModel->getAuthorizedRegions($member_id);
            if (empty($authorized_regions))
                $this->die_info('Access Denied', "No regions authorized for this user.");


            // If GroceryCrud had orWhere this wouldn't be needed
            // Not sure if both _id and .id are really needed, but this works

            $where_list = array_map(fn($r) => "region_id = $r", $authorized_regions);
            $region_underscore_where_clause = '(' . implode(' OR ', $where_list) . ')';

            $where_list = array_map(fn($r) => "region.id = $r", $authorized_regions);
            $region_dot_where_clause = '(' . implode(' OR ', $where_list) . ')';
        }

        $crud = new GroceryCrud();
        $crud->setTable('event');

        $crud->setRelation(
            'region_id',
            'region',
            '{club_name}',
            $this->isSuperuser() ? null : $region_dot_where_clause
        );
        $crud->setRelation('start_state_id', 'state', '{fullname}');
        $crud->setRelation('start_country_id', 'country', '{fullname}', null, null, 236);  // US Default


        $dt = new \DateTime();
        $now = $dt->format('Y-m-d');
        switch ($timerange) {
            case 'future':
                $crud->where('start_datetime >=', $now);
                // $crud->orderBy('date','asc');
                $crud->defaultOrdering('start_datetime', 'asc');
                $title = "Future Events";
                $crud->setSubject('Future Event', 'Future Events');
                break;
            case 'past':
                $crud->where('start_datetime <', $now);
                // $crud->order_by('date','desc');
                $crud->defaultOrdering('start_datetime', 'desc');
                $title = "Past Events";
                $crud->setSubject('Past Event', 'Past Events');
                break;
            default:
            case 'all':
                $title = "All Events";
                $crud->setSubject('Event', 'Events');
                break;
        }

        if (false == $this->isSuperuser()) {
            $crud->where($region_underscore_where_clause);
        }

        $crud->setAdd();
        $crud->unsetAddFields(['status', 'cue_version', 'created', 'last_changed']);
        $crud->unsetCloneFields(['status', 'cue_version', 'created', 'last_changed']);

        $crud->setRule('region', 'Region', 'required');
        $crud->setRule('country', 'Country', 'required');
        $crud->setRule('sanction', 'Sanction', 'required');
        $crud->setRule('name', 'Event Name', 'required');
        $crud->setRule('type', 'Event Type', 'required');
        # $crud->setRule('description', 'Description of Event', 'required');
        $crud->setRule('info_url', 'Route URL', 'permit_empty|valid_url_strict');
        $crud->setRule('route_url', 'Route URL', 'permit_empty|valid_url_strict');
        $crud->setRule('distance', 'Official Distance', 'required|is_natural_no_zero');
        $crud->setRule('gravel_distance', 'Gravel Distance', 'permit_empty|is_natural');
        $crud->setRule('start_datetime', 'Start Date and Time', 'required');
        $crud->setRule('start_city', 'Start City', 'required|alpha_space');
        $crud->setRule('start_state_id', 'Start State', 'required');
        $crud->setRule('emergency_contact', 'Emergency Contact', 'required');
        $crud->setRule('emergency_phone', 'Emergency Phone', 'required');

        $crud->setRead();
        $crud->setClone();
        $crud->callbackColumn('event_code', array($this, '_event_code'));
        $crud->callbackColumn('status', array($this, '_status_icons'));
        $crud->callbackColumn('generate', array($this, '_paperwork'));
        $crud->callbackColumn('event_info', array($this, '_event_info'));
        $crud->callbackColumn('riders', array($this, '_roster_manage'));
        $crud->callbackColumn('route', array($this, '_route'));

        $crud->setTexteditor(['description']);


        // $crud->setActionButton('', "fas fa-hat-wizard", function ($x)
        // {
        //     // $event_id = $x->id;
        //     // $region_id = $x->region_id;
        //     // $event_code = "$region_id-$event_id";
        //     return ("route_manager/$x");}, false);


        $crud->columns(['event_code', 'region_id', 'name', 'distance', 'start_datetime', 'status', 'event_info', 'riders', 'route', 'cue_version', 'generate']);
        $crud->unsetEditFields(['created', 'last_changed']);
        $crud->displayAs('start_datetime', 'Start Date/Time');
        $crud->displayAs('start_state_id', 'State');
        $crud->displayAs('start_country_id', 'Country');
        $crud->displayAs('distance', 'Official Dist (km)');
        $crud->displayAs('region_id', 'Region');
        $crud->displayAs('gravel_distance', 'Official Gravel (km)');
        $crud->displayAs('event_info', 'Info Pages');
        $crud->displayAs('cue_version', 'Version');

        $crud->callbackAfterUpdate([$this, '_set_last_change_time']);

        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);

        return $this->load_view(['crud_output']);
    }


    // CALLBACK COLUMNS


    public function _event_code($value, $row)
    {

        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";

        return $event_code;
    }

    public function _status_icons($value, $row)
    {
        $attribs = explode(',', $value);

        $d = "";

        foreach ($attribs as $a) {
            $icon = ((!empty($this->status_icon[$a])) ? $this->status_icon[$a] : "$a ");
            $d .= "<span title='$a'>$icon</span>";
        }

        return $d;
    }


    public function _paperwork($value, $row)
    {
        if (empty($row->route_url)) return "No Route";

        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        // $wizard_url = site_url("route_manager/$event_code");
        $dropdown = <<<EOT
<div class="w3-dropdown-hover">
<button class="w3-button w3-blue">Download&nbsp;<i class="fa-solid fa-download"></i></button>
<div class="w3-dropdown-content w3-bar-block w3-border">
EOT;

        $drop_items = [
            ["generate/$event_code/signin_sheet", "Sign-In Sheet (for all rider)"],
            ["generate/$event_code/card_outside_roster", "Brevet Card Outsides (cards for all riders)"],
            ["generate/$event_code/card_outside_blank", "Brevet Card Outside (blank rider name)"],
            ["generate/$event_code/card_inside", "Brevet Card Inside"],
            // ["roster_upload/$event_code", "Upload a Roster (CoM CSV Format)"],
            ["generate/$event_code/rusacsv", "Download Results (RUSA CSV Format)"],
        ];

        foreach ($drop_items as $i) {
            list($url, $desc) = $i;
            $url = site_url($url);
            $dropdown .=  "<A class='w3-bar-item w3-button' HREF='$url'>$desc</A>";
        }
        $dropdown .= "</div></div>";


        return $dropdown;

        // "<A class='w3-button w3-light-gray w3-round' HREF='$wizard_url'>
        // Cue Wizard</A>";
    }

    public function _event_info($value, $row)
    {
        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";

        $dropdown = <<<EOT
<div class="w3-dropdown-hover">
<button class="w3-button w3-blue">Info&nbsp;<i class="fa-solid fa-info-circle"></i></button>
<div class="w3-dropdown-content w3-bar-block w3-border">
EOT;

        $drop_items = [
            ["event_info/$event_code", "<i class='fa-solid fa-info-circle' style='color: blue;'></i> Event Info"],
            ["roster_info/$event_code", "<i class='fas fa-users' style='color: blue;'></i> Roster"],
            ["checkin_status/$event_code", "<i class='fas fa-list-check' style='color: blue;'></i> Check-ins"],
        ];

        foreach ($drop_items as $i) {
            list($url, $desc) = $i;
            $url = site_url($url);
            $dropdown .=  "<A class='w3-bar-item w3-button' HREF='$url'>$desc</A>";
        }
        $dropdown .= "</div></div>";


        return $dropdown;
    }


    public function _roster_manage($value, $row)
    {

        if (empty($row->route_url)) return "No Route";

        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        $n_riders = $this->rosterModel->n_riders($event_id); // =$value; //$this->truncate($value,30);



        $dropdown = <<<EOT
<div class="w3-dropdown-hover">
<button class="w3-button w3-blue">$n_riders Riders&nbsp;<i class="fa-solid fa-users"></i></button>
<div class="w3-dropdown-content w3-bar-block w3-border">
EOT;

        $drop_items = [
            ["roster/$event_code", "<i class='fas fa-users' style='color: blue;'></i> Manage Roster"],
            ["vet_roster/$event_code", "<i class='fas fa-users' style='color: blue;'></i> Check Rider Membership"],
            ["roster_upload/$event_code", "<i class='fas fa-upload' style='color: blue;'></i> Upload Roster"],
            ["checkin_manage/$event_code", "<i class='fas fa-check' style='color: blue;'></i> Raw Checkin Data"],

        ];

        foreach ($drop_items as $i) {
            list($url, $desc) = $i;
            $url = site_url($url);
            $dropdown .=  "<A class='w3-bar-item w3-button' HREF='$url'>$desc</A>";
        }
        $dropdown .= "</div></div>";


        return $dropdown;

        // "<A class='w3-button w3-light-gray w3-round' HREF='$wizard_url'>
        // Cue Wizard</A>";
    }

    public function _route($value, $row)
    {

        if (empty($row->route_url)) return "No Route";

        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        $wizard_url = site_url("route_manager/$event_code");


        return <<<EOT
        <div class='w3-container w3-center' style="background-color:rgb(206,206,206);">
<A HREF='$wizard_url' class='w3-button w3-blue'>Wizard&nbsp;<i class='fa-solid fa-hat-wizard'></i></A>
</div>
EOT;
    }



    // SUPPORT FUNCTIONS


    public function update_nine()
    {
        $stateParameters = new stdClass;
        $stateParameters->primaryKeyValue = 9;
        $stateParameters = $this->_set_last_change_time($stateParameters);
        $this->die_info(__METHOD__, print_r($stateParameters, true));
    }

    public function _set_last_change_time($stateParameters)
    {
        $eventModel = model('Event');
        $now = (new \DateTime('now', (new \DateTimeZone('UTC'))))->format('Y-m-d H:i:s');
        $id = $stateParameters->primaryKeyValue;
        $eventModel->update($id, ['last_changed' => "$now"]);
        return $stateParameters;
    }

    public function _roster_url($value, $row)
    {

        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        $roster_url = site_url("roster/$event_code");
        $roster_upload_url = site_url("roster_upload/$event_code");
        $n_riders = $this->rosterModel->n_riders($event_id); // =$value; //$this->truncate($value,30);


        return <<<EOT
<span style="width:100%;text-align:center;display:block;">
<A HREF='$roster_url' TITLE='Roster'><i class='fas fa-users' style='color: blue;'></i>($n_riders)</A> 
<A HREF='$roster_upload_url' TITLE='Upload'><i class='fas fa-upload' style='color: blue;'></i></A>
</span>
EOT;
    }


    public function vet_roster($event_code)
    {
        $event = $this->eventModel->eventByCode($event_code);

        $club_acp_code = $event['region_id'];
        $is_rusa = $this->regionModel->hasOption($club_acp_code, 'rusa');

        if (!$is_rusa) $this->die_message_notrace(__METHOD__, 
           "I don't know how to check the membership of riders in region ACP $club_acp_code");

        $cutoff_datetime = $this->eventModel->getCutoffDatetime($event);

        extract($this->eventModel->parseEventCode($event_code));
        $roster_in = $this->rosterModel->registered_riders($local_event_id, true);
        $n_riders = count($roster_in);
        $rusaModel = model('Rusa');
        $bad_riders = 0;

        $table_body = '';
        foreach ($roster_in as $r) {
            $rusa_id = $r['rider_id'];
            $first_name = $r['first_name'];
            $last_name = $r['last_name'];
            $status = $rusaModel->rusa_status_at_date($rusa_id, $last_name, $cutoff_datetime);
            if (is_string($status)) { // Vetting failed
                $table_body .= "<TR class='w3-red'><TD style='width: 25%'>$first_name $last_name</TD>";
                $table_body .= "<TD style='word-wrap: break-word;'> $status</TD>";
                $bad_riders++;
            } else {
                $table_body .= "<TR><TD>$first_name $last_name</TD>";
                $table_body .= "<TD>" . $status['rusa_expires_datetime']->format('Y-m-d') . "</TD>";
            }
            $table_body .= "</TR>";
        }

        // $this -> die_message(__METHOD__, $table_body);
        $this->viewData =  array_merge($this->viewData, ['bad_riders' => $bad_riders, 'n_riders' => $n_riders, 'table_body' => $table_body]);


        $output = $this->load_view('vet_roster');
        return $output;
    }




    public function roster_upload($event_code)
    {

        $event = $this->eventModel->eventByCode($event_code);

        $this->viewData['errors'] = [];
        $this->viewData['event_code'] = $event_code;
        $this->viewData['event_name_dist'] = $this->eventModel->nameDist($event);

        if ($this->request->is('get'))
            return $this->load_view('roster_upload_form');

        $validationRule = [
            'userfile' => [
                'label' => 'Roster File',
                'rules' => [
                    'uploaded[userfile]',
                    'ext_in[userfile,csv]',
                    'max_size[userfile,100]',
                ],
            ],
        ];
        if (!$this->validate($validationRule)) {
            $this->viewData['errors'] = $this->validator->getErrors();
            return $this->load_view('roster_upload_form');
        }

        $img = $this->request->getFile('userfile');

        if (!$img->isValid()) {
            throw new \Exception($img->getErrorString() . '(' . $img->getError() . ')');
        }

        $filepath = WRITEPATH . 'uploads/' . $img->store();
        $uploadedFile = new File($filepath);
        $this->viewData['uploadedFile'] = $uploadedFile;

        $processingResult = $this->processRosterFile($uploadedFile, $event);
        $this->viewData = array_merge($this->viewData, $processingResult);

        if (!empty($processingResult['errors'])) {
            return $this->load_view('roster_upload_form');
        }

        $output = $this->load_view('roster_upload_success');
        unlink($filepath);
        return $output;
    }

    private function processRosterFile($uploadedFile, $event)
    {
        $errors = [];
        $n_riders = 0;

        $rusaModel =  model('Rusa');

        $event_cutoff_datetime = $this->eventModel->getCutoffDatetime($event);
        $local_event_id = $event['event_id'];

        $club_acp_code = $event['region_id'];
        $is_rusa = $this->regionModel->hasOption($club_acp_code, 'rusa');

        try {
            $f = $uploadedFile->openFile();
            $roster_data = [];
            while (!$f->eof()) {
                $roster_data[] = $f->fgetcsv();
            }
            $header = array_shift($roster_data);
            if (empty($header)) throw new \Exception("No header.");
            if (empty($roster_data)) throw new \Exception("No riders.");

            // Card O Matic CSV Format Spec
            //
            // "RUSA" or variations ("rusa number", etc)  -- REQUIRED
            // "FIRST" or variations ("firstname", "first name", etc)
            // "LAST" or variations  -- REQUIRED
            // "ADDRESS" or "STREET"
            // "CITY"
            // "STATE"
            // "ZIP" 

            $fnp = [
                'rider_id' => '/.*(ID|RUSA|RIDER|MEMBER|NUMBER|ACP).*/i',
                'first' => '/.*first.*/i',
                'last' => '/.*last.*/i',
                'address' => '/.*(address|street).*/i',
                'city' => '/.*city.*/i',
                'state' => '/.*state.*/i',
                'zip' => '/.*(zip|code).*/i'
            ];

            // Standardize the header and remove unrecognized columns
            $header = preg_filter(array_values($fnp), array_keys($fnp), $header);
            if (empty($header)) throw new \Exception("No recognized header fields.");


            // Find all unique column names
            $field_j = array_flip($header);


            // Verify that required columns are present
            if (false == array_key_exists('rider_id', $field_j)) $errors[] = "No Rider ID (RIDERID) column.";
            if ($is_rusa) {
                if (false == array_key_exists('last', $field_j)) $errors[] = "No Last Name (LAST) column.";
            }
            if (!empty($errors)) throw new \Exception("Can't continue.");


            if ($is_rusa) {
                // RUSA Vetting will add the expiration field
                $header[] = 'expires';
                $header[] = 'checked_by';
            }


            // Process each data row
            $roster = [];
            $line_number = 0;

            foreach ($roster_data as $r) {
                $rider = [];
                $line_number++;
                foreach ($header as $i => $field_name) {
                    if (!isset($rider[$field_name])) {
                        $rider[$field_name] = trim($r[$i] ?? '');
                    } else {
                        if ($field_name == 'address')
                            $rider['address'] .= "; " . trim($r[$i] ?? '');
                        // else silently ignore duplicate columns
                    }
                }

                extract($rider);

                if (empty($rider_id) || !is_numeric($rider_id)) {
                    $errors[] = "Rider ID '$rider_id' (record: $line_number) is invalid.";
                    continue;
                }

                if (!empty($roster[$rider_id])) {
                    $errors[] = "Duplicate Rider ID '$rider_id' (record: $line_number).";
                    continue;
                }

                if ($is_rusa) {

                    $rusa_result = $rusaModel->rusa_status_at_date($rider_id, $last, $event_cutoff_datetime);

                    if (is_string($rusa_result)) {
                        $errors[] = "Rider ID '$rider_id' (record: $line_number): $rusa_result";
                        continue;
                    }

                    // Rider is known good, add expires date, and save to roster

                    $rider['expires'] = $rusa_result['rusa_expires_datetime']->format('Y-m-d');
                    $rider['checked_by'] = $rusa_result['checked_by'];
                }

                $roster[$rider_id] = $rider;
            }

            $n_riders = count($roster_data);
        } catch (\Exception $e) {
            $message = $e->GetMessage();
            $errors[] = "Roster CSV Processing Exception: $message";
        }

        // $errors[]= "Rider data: " . print_r($roster, true);

        if (empty($errors)) {

            // clear old roster
            $this->rosterModel->where('event_id', $local_event_id)->delete();

            // write new roster
            foreach ($roster as $rider_id => $rider_data) {
                $rc = $this->rosterModel->insert([
                    'rider_id' => $rider_id,
                    'event_id' => $local_event_id,
                    'first_name' => $rider_data['first'],
                    'last_name' => $rider_data['last']
                ], false);
                if ($rc === false) {
                    $errors[] = "Failed to save rider_id = $rider_id to roster. File upload incomplete.";
                    break;
                }
            }
        } else {
            $errors[] = "Errors in CSV, roster not saved.";
        }


        return compact('errors', 'n_riders', 'header', 'roster');
    }




    private $status_icon = [
        'hidden' => "<i class='w3-text-red w3-large fas fa-mask'></i>",
        'canceled' => "<i class='w3-text-red w3-large fas fa-thumbs-down'></i>",
        'locked' => "<i class='w3-text-red w3-large fas fa-lock'></i>",
        'suspended' => "<i class='w3-text-red w3-large fas fa-question-circle'></i>"
    ];
}
