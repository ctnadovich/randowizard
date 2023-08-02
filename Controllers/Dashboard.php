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

class Dashboard extends BaseController
{

    protected $helpers = ['form'];
    protected $regionModel;
    protected $userModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->userModel = model('User');
    }
   
    public function events(){

        $this->login_check();

        $crud = new GroceryCrud();
        $crud->setTable('event');
        $crud->setRelation('region_id', 'region', '{club_name}',['rba_user_id' => $this->session->get('user_id')]);
        $crud->setRelation('start_state_id', 'state', '{fullname}');
        $crud->setRelation('start_country_id', 'country', '{fullname}');
        $crud->where('rba_user_id',$this->session->get('user_id'));
        $crud->setAdd();
        $crud->setRead();
        $crud->setSubject('Event', 'Events');
        $crud->callbackColumn('status', array($this,'_status_icons'));
        $crud->columns(['region_id','name','distance','start_datetime','status']);
        // $crud->unsetEditFields(['region_id']);
        $crud->displayAs('start_datetime', 'Start Date/Time');
        $crud->displayAs('start_state_id', 'State');
        $crud->displayAs('start_country_id', 'Country');
        $crud->displayAs('distance', 'Official Dist (km)');
        $crud->displayAs('region_id', 'Region');
        $crud->displayAs('gravel_distance', 'Official Gravel (km)');

        $output = $crud->render();

        $this->viewData = array_merge ((array)$output, $this->viewData);

        return $this->load_view(['dashboard']); 
    }

    private $status_icon=[
		'hidden'=>"<i class='fas fa-mask'  style='color: blue;'></i>",
		'canceled'=>"<i class='fas fa-thumbs-down'  style='color: blue;'></i>",
		'locked'=>"<i class='fas fa-lock'  style='color: blue;'></i>",
		'suspended'=>"<i class='fas fa-question-circle'  style='color: blue;'></i>"];


	public function _status_icons($value,$row){
		$attribs=explode(',',$value);

		$d="";

		foreach ($attribs as $a){
			$icon = ((!empty($this->status_icon[$a]))?$this->status_icon[$a]:"$a ");
			$d .= "<span title='$a'>$icon</span>";
		}

		return $d;
	}




   public function region(){

        $this->login_check();

        $crud = new GroceryCrud();
        $crud->setTable('region');
        $crud->setRelation('event_timezone_id', 'tz', '{name}');
        $crud->setRelation('state_id', 'state', '{fullname}');
        $crud->setRelation('rba_user_id', 'user', '{first} {last}');
        $crud->where('rba_user_id',$this->session->get('user_id'));
        $crud->unsetAdd();
        $crud->setRead();
        $crud->unsetDelete();
        $crud->setSubject('Region', 'Regions');
        $crud->columns(['state_id','region_name','club_name', 'rba_user_id', 'event_timezone_id']);
        $crud->unsetEditFields(['rba_user_id','id','state_code','state_name','region_name','club_name']);
        $crud->displayAs('rba_user_id', 'RBA');
        $crud->displayAs('event_timezone_id', 'Time Zone');
        $crud->displayAs('state_id',"State");
        $crud->fieldType('website_url','url');
        $crud->displayAs('id', 'ACP Code');

        $output = $crud->render();

        $this->viewData = array_merge ((array)$output, $this->viewData);

        return $this->load_view(['dashboard']); 
    }

    
    
    
}