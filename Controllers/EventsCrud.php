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

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->rosterModel =  model('Roster');
    }

    public function events($timerange = 'all')
    {

        $this->login_check();

        $crud = new GroceryCrud();
        $crud->setTable('event');
        $crud->setRelation('region_id', 'region', '{club_name}', ['rba_user_id' => $this->session->get('user_id')]);
        $crud->setRelation('start_state_id', 'state', '{fullname}');
        $crud->setRelation('start_country_id', 'country', '{fullname}');

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
            $crud->where('rba_user_id', $this->getMemberID());
        }

        $crud->setAdd();
        $crud->setRead();
        $crud->callbackColumn('status', array($this, '_status_icons'));
        $crud->callbackColumn('admin', array($this, '_paperwork'));
        $crud->callbackColumn('roster', array($this, '_roster_url'));


        // $crud->setActionButton('', "fas fa-hat-wizard", function ($x)
        // {
        //     // $event_id = $x->id;
        //     // $region_id = $x->region_id;
        //     // $event_code = "$region_id-$event_id";
        //     return ("route_manager/$x");}, false);


        $crud->columns(['region_id', 'name', 'distance', 'start_datetime', 'status', 'roster', 'admin']);
        // $crud->unsetEditFields(['region_id']);
        $crud->displayAs('start_datetime', 'Start Date/Time');
        $crud->displayAs('start_state_id', 'State');
        $crud->displayAs('start_country_id', 'Country');
        $crud->displayAs('distance', 'Official Dist (km)');
        $crud->displayAs('region_id', 'Region');
        $crud->displayAs('gravel_distance', 'Official Gravel (km)');

        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);

        return $this->load_view(['dashboard']);
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


    public function _paperwork($value, $row)
    {
        $event_id = $row->id;
        $region_id = $row->region_id;
        $event_code = "$region_id-$event_id";
        // $wizard_url = site_url("route_manager/$event_code");


        $dropdown = <<<EOT
<div class="w3-dropdown-hover">
<button class="w3-button w3-black">Paperwork</button>
<div class="w3-dropdown-content w3-bar-block w3-border">
EOT;

        $drop_items = [
            ["route_manager/$event_code", "Route Manager<i class='fas fa-hat-wizard'></i>&nbsp;Cue Wizard"],
            ["preview/$event_code/signin_sheet", "Sign-In Sheet (for all rider)"],
            ["preview/$event_code/card_outside_roster", "Brevet Card Outsides (cards for all riders)"],
            ["preview/$event_code/card_outside_blank", "Brevet Card Outside (blank rider name)"],
            ["preview/$event_code/card_inside", "Brevet Card Inside"],
            ["roster_upload/$event_code", "Upload a Roster (CoM CSV Format)"],
            ["preview/$event_code/rusacsv", "Download Results (RUSA CSV Format)"],
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
        'hidden' => "<i class='fas fa-mask'  style='color: blue;'></i>",
        'canceled' => "<i class='fas fa-thumbs-down'  style='color: blue;'></i>",
        'locked' => "<i class='fas fa-lock'  style='color: blue;'></i>",
        'suspended' => "<i class='fas fa-question-circle'  style='color: blue;'></i>"
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
