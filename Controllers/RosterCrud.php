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

class RosterCrud extends BaseController
{

    protected $rosterModel;
    protected $eventModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->rosterModel =  model('Roster');
        $this->eventModel =  model('Event');
        // $this->regionModel =  model('Event');
    }

    private $no_rusa_check = false; 

    public function roster_nocheck($event_code){
       $this->no_rusa_check = true;
       return $this->roster($event_code);
    }

    public function roster($event_code)
    {

        $no_rusa_check = false;

        $this->login_check();

        extract($this->eventModel->parseEventCode($event_code));

        $this->die_not_admin($club_acp_code);

        $event = $this->eventModel->getEvent($club_acp_code, $local_event_id);
        if (empty($event)) {
            throw new \Exception("NO SUCH EVENT $event_code");
        }

        $name_dist = $this->eventModel->nameDist($event);
        $is_rusa = $this->regionModel->hasOption($club_acp_code, 'rusa') && !$this->no_rusa_check;

        $crud = new GroceryCrud();
        $crud->setSubject("$name_dist Rider", "$name_dist Riders");
        $crud->setTable('roster');
        $crud->where('event_id', $local_event_id);

        if ($is_rusa) {
            $crud->setPrimaryKey('rusa_id', 'rusa');
            $crud->setRelation('rider_id', 'rusa', '{last_name}, {first_name}  #{rusa_id}');
            $crud->columns(['rider_id', 'result', 'elapsed_time', 'comment']);
        } else {
            $crud->columns(['rider_id', 'first_name', 'last_name', 'result', 'elapsed_time', 'comment']);
        }

        $crud->unsetEditFields(['rider_id', 'event_id', 'created', 'last_change']);
        $crud->unsetAddFields(['created', 'last_change']);
        $crud->callbackAddField('event_id', function ($fieldType, $fieldName) use ($event) {
            $local_event_id = $event['id'];
            $name_dist = $event['name'] . ' ' . $event['distance'];
            return "$name_dist<input name='$fieldName' type='hidden' value='$local_event_id'>";
        });

        // $crud->displayAs('rider_id', 'Rider');
        $crud->displayAs('elapsed_time', 'Elapsed Time (HH:MM:SS)');

        $crud->setRule('elapsed_time', 'ElapsedTime', 'permit_empty|regex_match[/\d{1,2}:\d{2}(|:\d{2})/]');


        $crud->callbackBeforeInsert(function ($stateParameters) use ($local_event_id, $crud) {

            $rider_id = $stateParameters->data['rider_id'];

            if (empty($rider_id)) {
                return "false";
            }


            $stateParameters->data['event_id'] = $local_event_id;
            return $stateParameters;
        });

        $crud->setAdd();
        $crud->setRead();


        if ($is_rusa) {

            $nocheck_url = site_url("roster_nocheck/$event_code");

            $this->viewData['navbar_suffix'] = <<<EOT
        <div class="w3-panel w3-pale-yellow">
        <p><i>
        NB: Only riders picked from the RUSA membership may be selected. If you want to enter numeric
        Rider ID numbers manually without the RUSA check, <A HREF='$nocheck_url'>click here</a>. 
        </i></p></div>
    EOT;
        }


        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);

        return $this->load_view(['echo_output']);
    }
}
