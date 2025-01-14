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

class CheckinCrud extends BaseController
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
    }

    public function checkin_manage($event_code)
    {

        $this->login_check();

        extract($this->eventModel->parseEventCode($event_code));

        $this->die_not_admin($club_acp_code);

        $event = $this->eventModel->getEvent($club_acp_code, $local_event_id);
        if (empty($event)) {
            throw new \Exception("NO SUCH EVENT $event_code");
        }

        $club_acp_code = $event['region_id'];
        $is_rusa = $this->regionModel->hasOption($club_acp_code, 'rusa');

        $name_dist = $this->eventModel->nameDist($event);

        $crud = new GroceryCrud();
        $crud->setSubject("$name_dist Checkin", "$name_dist Checkins");
        $crud->setTable('checkin');
        if ($is_rusa) {
            $crud->setPrimaryKey('rusa_id', 'rusa');
            $crud->setRelation('rider_id', 'rusa', '{last_name}, {first_name}  #{rusa_id}');
        } 
            
        $crud->columns(['rider_id', 'control_number', 'time', 'created', 'preride', 'comment']);

        $crud->displayAs('time', 'Time (UTC)');
        $crud->displayAs('created', 'Created (UTC)');

        $crud->where('event_id', $local_event_id);

        $crud->setRead();
        $crud->setClone();
        $crud->callbackColumn('preride', fn($val, $row) => ($val ? 'PRERIDE' : ''));
        $crud->unsetEditFields(['rider_id', 'event_id', 'created']);
        $crud->unsetAddFields(['created']);
        $crud->callbackAddField('event_id', function ($fieldType, $fieldName) use ($event) {
            $local_event_id = $event['event_id'];
            $name_dist = $this->eventModel->nameDist($event);
            return "$name_dist<input name='event_id' type='hidden' value='$local_event_id'>";
        });

        // $crud->displayAs('rider_id', 'Rider');
        $checkin_status_url = site_url("checkin_status/$event_code");
        $this->viewData['navbar_suffix'] = <<<EOT
    <div class="w3-panel w3-pale-yellow">
    <p><i>
    NB: If you want to view/display rider checkins, please use the 
    public <A HREF='$checkin_status_url'>checkin_status page</a>, which
    may be linked on your club web page for general use. This 
    private page here is only for RBA editing/inspection of the raw checkin database. 
    </i></p></div>
EOT;
        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);

        return $this->load_view(['echo_output']);
    }
}
