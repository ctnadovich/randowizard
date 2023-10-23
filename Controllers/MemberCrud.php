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

        $rbaModel = model('Rba');
        $user_id = $this->getMemberID();
        $authorized_regions = $rbaModel->getAuthorizedRegionObjects($user_id);

        // all the users authorized for all the regions THIS user is authorized for
        $au_hash = [];
        foreach ($authorized_regions as $r) {
            extract($r);
            $au = $rbaModel->getAuthorizedUsers($club_acp_code);
            foreach ($au as $u) {
                $au_hash[$u] = true;
            }
        }
        $authorized_users = array_keys($au_hash);


        // $this->die_info(__METHOD__,print_r($authorized_regions,true));

        $subject = 'Organizer/RBA';
        $crud = new GroceryCrud();

        $crud->setSubject($subject);
        $crud->setTable('user');
        $crud->setRead();

        // NtoN is largely broken in GC
        // $crud->setRelationNtoN('RBA_of','rba','region','user_id','region_id','{club_name}');

        if (false == $this->isSuperuser()) {
            // restrictions for unprivileged users
            if(count($authorized_users)==1) $crud->unsetDelete();
            $crud->unsetEditFields(['privilege', 'region']);
            $crud->unsetAddFields(['privilege']);
            $crud->unsetReadFields(['privilege', 'password_hash']);
            $crud->unsetColumns(['privilege']);
            if (empty($authorized_regions) || empty($authorized_users)) {
                $crud->where('id', $this->getMemberID());
            } else {
                $where_list=[];
                foreach ($authorized_users as $u) {
                    $where_list[] = "id = $u";
                }
                $crud->where('(' . implode(' OR ', $where_list) . ')');
            }
        } else {
            // Extras for superuser
            $crud->setActionButton('SU', 'fas fa-user', function ($row) {
                return site_url("su/$row");
            }, true);
        }

        $crud->columns(['first', 'last', 'email', 'Region']);

        // Comma separated list of authorized regions to display in datagrid
        $crud->callbackColumn('Region', function ($value, $row) use ($authorized_regions) {
            if ($this->isSuperuser()) return "All";
            if (empty($authorized_regions)) return "None";
            $ar_list = [];
            foreach ($authorized_regions as $r) {
                extract($r);
                $ar_list[] = "$state_code: $region_name";
            }
            return implode(',', $ar_list);
        });


        $crud->addFields(['first', 'last', 'email', 'password_hash', 'region']);
        $crud->fieldType('password_hash', 'password');
        $crud->displayAs('password_hash', 'Password');

        $crud->setRule('email', 'Email Address', 'trim|required|valid_email|is_unique[user.email]');
        $crud->setRule('first', 'First Name', 'trim|required|alpha_space');
        $crud->setRule('last', 'Last Name', 'trim|required|alpha_space');
        $crud->setRule('password_hash', 'Password', 'trim|required|min_length[8]');

        // We replace the actual password with not_a_password, which consequently, 
        // is never allowed to be the password. What are the chances? 
        $crud->callbackEditField('password_hash', [$this, 'clear_password_field']);

        // Generate select/option HTML field for adding RBA to one of the authorized regions
        $crud->callbackAddField(
            'region',

            function ($field_type, $field_name)  use ($authorized_regions) {
                if ($this->isSuperuser()) {
                    $aro = $this->regionModel->getRegions();
                } else {
                    $aro = $authorized_regions;
                }

                $field_text  = "<select name='region' id='region'>";
                foreach ($aro as $r) {
                    extract($r);
                    $field_text .= "<option value=$club_acp_code>$state_code: $region_name</option>";
                }
                $field_text .= "</select>";

                return $field_text;
            }
        );

        // Convert plaintext password to hash
        // For some reason this doesn't get called when we use callbackInsert
         // $crud->callbackBeforeInsert([$this, 'hash_password']);

        // Since region isn't a field in the user table, and we can't rely on NtoN functions
        // we need to roll our own insert that deals with the region

        $crud->callbackInsert([$this, 'insert_with_region']);

        // Convert plaintext password to hash and save only if the password was changed
        $crud->callbackBeforeUpdate([$this, 'update_password']);

        $crud->callbackAfterDelete(function ($stateParameters) use($rbaModel) {
            $rbaModel->deleteRBAUser($stateParameters->primaryKeyValue);
            return $stateParameters;
        });



        $output = $crud->render();
        $this->viewData = array_merge((array)$output, $this->viewData);
        return $this->load_view(['echo_output']);
    }

    public function clear_password_field($fieldValue, $primaryKeyValue, $rowData)
    {
        return "<input type='password' name='password_hash' value='{$this->not_a_password}' />";
    }

    public function insert_with_region($stateParameters)
    {

        $region_id = $stateParameters->data['region'];
        unset($stateParameters->data['region']);

        $stateParameters = $this->hash_password($stateParameters);

        $user_id = $this->userModel->insert($stateParameters->data);
        $stateParameters->insertId = $user_id; // $insertId;

        $rbaModel = model('Rba');
        $rbaModel->insertRBAforRegion($user_id, $region_id);
        return $stateParameters;
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
            trigger_error('Password not set', E_USER_ERROR);
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
