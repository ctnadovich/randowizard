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

class EventsCrud extends BaseController
{

    protected $helpers = ['form'];
    protected $rosterModel;

    protected $user_id;
    protected $authorized_regions;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->rosterModel =  model('Roster');

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
            
            $where_list = array_map(fn ($r) => "region_id = $r", $authorized_regions);
            $region_underscore_where_clause = '(' . implode(' OR ', $where_list) . ')';

            $where_list = array_map(fn ($r) => "region.id = $r", $authorized_regions);
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
                $title = "Future Events";
                $crud->setSubject('Future Event', 'Future Events');
                break;
            case 'past':
                $crud->where('start_datetime <', $now);
                // $crud->order_by('date','desc');
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
        $crud->unsetAddFields(['status', 'cue_version']);

        $crud->setRule('region', 'Region', 'required');
        $crud->setRule('country', 'Country', 'required');
        $crud->setRule('sanction', 'Sanction', 'required');
        $crud->setRule('name', 'Event Name', 'required');
        $crud->setRule('type', 'Event Type', 'required');
        $crud->setRule('description', 'Description of Event', 'required');
        $crud->setRule('info_url', 'Route URL', 'permit_empty|valid_url_strict');
        $crud->setRule('route_url', 'Route URL', 'required|valid_url_strict');
        $crud->setRule('distance', 'Official Distance', 'required|is_natural_no_zero');
        $crud->setRule('gravel_distance', 'Gravel Distance', 'permit_empty|is_natural');
        $crud->setRule('start_datetime', 'Start Date and Time', 'required');
        $crud->setRule('start_city', 'Start City', 'required|alpha_space');
        $crud->setRule('start_state_id', 'Start State', 'required');
        $crud->setRule('emergency_contact', 'Emergency Contact', 'required');
        $crud->setRule('emergency_phone', 'Emergency Phone', 'required');

        $crud->setRead();
        $crud->callbackColumn('event_code', array($this, '_event_code'));
        $crud->callbackColumn('status', array($this, '_status_icons'));
        $crud->callbackColumn('generate', array($this, '_paperwork'));
        $crud->callbackColumn('event_info', array($this, '_event_info'));
        $crud->callbackColumn('roster', array($this, '_roster_url'));
        $crud->callbackColumn('route', array($this, '_route'));

        $crud->setTexteditor(['description']);


        // $crud->setActionButton('', "fas fa-hat-wizard", function ($x)
        // {
        //     // $event_id = $x->id;
        //     // $region_id = $x->region_id;
        //     // $event_code = "$region_id-$event_id";
        //     return ("route_manager/$x");}, false);


        $crud->columns(['event_code', 'region_id', 'name', 'distance', 'start_datetime', 'status', 'event_info', 'roster', 'route', 'generate']);
        // $crud->unsetEditFields(['region_id']);
        $crud->displayAs('start_datetime', 'Start Date/Time');
        $crud->displayAs('start_state_id', 'State');
        $crud->displayAs('start_country_id', 'Country');
        $crud->displayAs('distance', 'Official Dist (km)');
        $crud->displayAs('region_id', 'Region');
        $crud->displayAs('gravel_distance', 'Official Gravel (km)');
        $crud->displayAs('event_info', 'Info');

        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);

        return $this->load_view(['echo_output']);
    }

    public function _event_code($value, $row)
    {

        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";

        return $event_code;
    }

    public function _roster_url($value, $row)
    {

        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        $roster_url = site_url("roster/$event_code");


        $span_center = '<span style="width:100%;text-align:center;display:block;">';
        $n_riders = $this->rosterModel->n_riders($event_id); // =$value; //$this->truncate($value,30);
        return "$span_center<A HREF='{$roster_url}' TITLE='Roster'><i class='fas fa-users'  style='color: blue;'></i></A> ($n_riders)</span>";
    }


    public function _route($value, $row)
    {
        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        $wizard_url = site_url("route_manager/$event_code");

        return <<<EOT
        <div class='w3-container w3-center' style="background-color:rgb(206,206,206);">
<A HREF='$wizard_url' class='w3-button w3-blue'>Manage&nbsp;<i class='fa-solid fa-hat-wizard'></i></A>
</div>
EOT;
    }

    public function _event_info($value, $row)
    {
        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        $wizard_url = site_url("event_info/$event_code");

        return <<<EOT
        <div class='w3-container w3-center' >
<A HREF='$wizard_url' class='w3-button w3-blue'><i class='w3-large fa-solid fa-info-circle'></i></A>
</div>
EOT;
    }
    public function _paperwork($value, $row)
    {
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

    private $status_icon = [
        'hidden' => "<i class='w3-text-red w3-large fas fa-mask'></i>",
        'canceled' => "<i class='w3-text-red w3-large fas fa-thumbs-down'></i>",
        'locked' => "<i class='w3-text-red w3-large fas fa-lock'></i>",
        'suspended' => "<i class='w3-text-red w3-large fas fa-question-circle'></i>"
    ];


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
}
