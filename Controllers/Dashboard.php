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

        if (false == $this->session->get('logged_in')) {
            return $this->show_message('Access denied',  'Not logged in.');
         }

        $crud = new GroceryCrud();
        $crud->setTable('event');
        $crud->setAdd();
        $crud->setSubject('Event', 'Events');
        $crud->columns(['name','distance','start_ontime']);
        $crud->displayAs('start_ontime', 'Start Date/Time');
        $crud->displayAs('distance', 'Official Dist (km)');

        $output = $crud->render();

        $this->viewData = array_merge ((array)$output, $this->viewData);

        return $this->load_view(['dashboard']); 
        // return view('head', $this->viewData) . view('dashboard') . view('foot');
    }

    
}