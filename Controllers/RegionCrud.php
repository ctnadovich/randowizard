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

class RegionCrud extends BaseController
{

    protected $helpers = ['form'];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
    }


    public function region()
    {

        $this->login_check();

        $crud = new GroceryCrud();
        $crud->setTable('region');
        $crud->setRelation('event_timezone_id', 'tz', '{name}');
        $crud->setRelation('state_id', 'state', '{fullname}');
        $crud->setRelation('country_id', 'country', '{fullname}');
        $crud->setRelation('rba_user_id', 'user', '{first} {last}');
        if (false == $this->isSuperuser()) {
            $crud->unsetAdd();
            $crud->unsetDelete();
            $crud->unsetEditFields(['rba_user_id', 'id', 'state_code', 'state_id', 'country_id', 'region_name', 'club_name']);
            $crud->where('rba_user_id', $this->getMemberID());
        }
        $crud->setRead();
        $crud->setSubject('Region', 'Regions');
        $crud->columns(['state_id', 'region_name', 'club_name', 'rba_user_id', 'event_timezone_id']);
        $crud->displayAs('rba_user_id', 'RBA');
        $crud->displayAs('event_timezone_id', 'Time Zone');
        $crud->displayAs('state_id', "State");
        $crud->fieldType('website_url', 'url');
        $crud->displayAs('id', 'ACP Code');

        $crud->fieldType('epp_secret', 'password');
        $crud->displayAs('epp_secret', 'EPP Secret');
        $crud->unsetReadFields(['epp_secret']);
        $crud->callbackEditField('epp_secret', [$this, 'clear_epp_secret_field']);
        $crud->callbackBeforeUpdate([$this, 'update_callback']);


        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);

        return $this->load_view(['dashboard']);
    }

    protected $not_a_password = "not_a_password";

    public function clear_epp_secret_field($fieldValue, $primaryKeyValue, $rowData)
    {
        return "<input type='password' name='epp_secret' value='{$this->not_a_password}' />";
    }

    public function update_callback($stateParameters)
    {
        $password = $stateParameters->data['epp_secret'];
        if (!empty($password) && $password != $this->not_a_password) {
            $stateParameters->data['epp_secret'] = $password;
        } else {
            unset($stateParameters->data['epp_secret']);
        }

        return $stateParameters;
    }
}
