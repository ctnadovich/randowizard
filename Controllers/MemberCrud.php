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

class MemberCrud extends BaseController
{

    protected $not_a_password = "not_a_password";


    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
    }

    public function profile()
    {
        $this->login_check();

        $subject = 'Organizer/RBA';
        $crud = new GroceryCrud();


        $crud->setSubject($subject);
        $crud->setTable('user');
        $crud->setRead();

        // $crud->setRelationNtoN('RBA_of','rba','region','user_id','region_id','{club_name}');

        // restrictions for unprivileged users
        if (false == $this->isSuperuser()) {
            // $crud->unsetAdd();
            $crud->unsetDelete();
            $crud->unsetEditFields(['privilege','region']);
            $crud->unsetAddFields(['privilege']);
            $crud->unsetReadFields(['privilege', 'password_hash']);
            $crud->unsetColumns(['privilege']);
            $crud->where('id', $this->getMemberID());
        } else {
            $crud->setActionButton('SU', 'fas fa-user', function ($row) {
                return site_url("su/$row");
            }, true);
        }

        $crud->callbackColumn('Region', function ($value, $row) {
            if ($this->isSuperuser()) return "All";
            $rbaModel = model('Rba');
            $user_id = $this->getMemberID();
            $authorized_regions = $rbaModel->getAuthorizedRegionObjects($user_id);
            $ar_list = [];
            foreach ($authorized_regions as $r) {
                extract($r);
                $ar_list[] = "$state_code: $region_name";
            }
            return implode(',', $ar_list);
        });


        $crud->columns(['first', 'last', 'email', 'Region']);
        $crud->addFields(['first', 'last', 'email', 'password_hash','region']); // ,'rba_of']);

        $crud->setRule('email', 'Email Address', 'trim|required|valid_email|is_unique[user.email]');
        $crud->setRule('first', 'First Name', 'trim|required|alpha_space');
        $crud->setRule('last', 'Last Name', 'trim|required|alpha_space');
        $crud->setRule('password_hash', 'Password', 'trim|required|min_length[8]');

        $crud->callbackAddField(
            'region',

            function ($field_type, $field_name) {
                if ($this->isSuperuser()) {
                    $aro = $this->regionModel->getRegions();
                } else {
                    $rbaModel = model('Rba');
                    $member_id = $this->getMemberID();
                    $aro = $rbaModel->getAuthorizedRegionObjects($member_id);
                }

                $field_text  = "<select name='rba_of' id='rba_of'>";
                foreach ($aro as $r) {
                    extract($r);
                    $field_text .= "<option value=$club_acp_code>$state_code: $region_name</option>";
                }
                $field_text .= "</select>";

                return $field_text;
            }
        );

        /*         $crud->callbackAfterInsert(function ($stateParameters)  {
                $rbaModel = model('Rba');
                $user_id = $stateParameters->insertId;
                $region_id = $stateParameters->data['region_id];
                $rbaModel->insertRBAforRegion($user_id, $region_id);
        
            return $stateParameters;
        });
 */
        $crud->fieldType('password_hash', 'password');
        $crud->displayAs('password_hash', 'Password');
        $crud->callbackEditField('password_hash', [$this, 'clear_password_field']);
        // $crud->callbackAddField('password_hash', [$this, 'clear_password_field']);
        $crud->callbackBeforeUpdate([$this, 'update_password']);
        $crud->callbackBeforeInsert([$this, 'hash_password']);
        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);
        return $this->load_view(['echo_output']);
    }

    public function clear_password_field($fieldValue, $primaryKeyValue, $rowData)
    {
        return "<input type='password' name='password_hash' value='{$this->not_a_password}' />";
    }

    public function update_password($stateParameters)
    {
        $password = $stateParameters->data['password_hash'];
        if (!empty($password) && $password != $this->not_a_password) {
            $stateParameters->data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        } else {
            unset($stateParameters->data['password_hash']);
        }

        return $stateParameters;
    }

    public function hash_password($stateParameters)
    {
        $password = $stateParameters->data['password_hash'];
        if (!empty($password)) {
            $stateParameters->data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $stateParameters->data['password_hash'] = password_hash(date("U"), PASSWORD_DEFAULT);  // :-)
        }

        return $stateParameters;
    }

    public function su($member_id)
    {
        $user = $this->userModel->find($member_id);
        if (!empty($user) && $this->isSuperuser()) {

            // $this->die_message(__METHOD__, "Becoming ID $member_id " . print_r($user,true));
            $this->becomeUser($user);
            return redirect()->route('home');
        } else {
            throw new \Exception("Can't become user ID=$member_id");
        }
    }
}
