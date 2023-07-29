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

class Profile extends BaseController
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

        $subject = 'Profile';
        $crud = new GroceryCrud();
        $state = $crud->getState();
        $state_info = $crud->getStateInfo();
        switch ($state) {
            case 'edit':
            case 'update_validation':
            case 'update':
            case 'success':
                $primary_key = $state_info->primary_key;
                $member_id = $this->session->get('user_id');
                if ($primary_key != $member_id) {
                    trigger_error("ID Mismatch: pk=$primary_key; mi=$member_id", E_USER_ERROR);
                }
                break;
            default:
                trigger_error("This function can only be called in states edit/update_validation/success. State=$state", E_USER_ERROR);
                break;
        }
        $crud->setSubject($subject);
        $crud->setTable('user');
        $crud->unsetAdd();
        $crud->fieldType('password_hash', 'password');
        $crud->displayAs('password_hash', 'Password');
        $crud->callbackEditField('password_hash', [$this, 'clear_password_field']);
        $crud->callbackBeforeUpdate([$this, 'update_callback']);
        $output = $crud->render();


        // Horrid hack required to work around the removal of unset_list and unset_back_to_list functions
        // in v2.x of Grocery Crud.  This str_replace relabels the buttons. The Routes system in CodeIgniter
        // is used to redirect update_success back to the home page rather than the list. This isolation
        // is somewhat dicey. Note the if ($primary_key != $member_id) test above that attempts to maintain
        // the isolation. 

        $output->output = str_replace("value='Update changes'", "value='Save'", $output->output);
        $output->output = str_replace("value='Update and go back to list'", "value='Save and Return'", $output->output);

        $this->viewData = array_merge((array)$output, $this->viewData);
        return $this->load_view(['profile']);
    }

    public function clear_password_field($fieldValue, $primaryKeyValue, $rowData)
    {
        return "<input type='password' name='password_hash' value='{$this->not_a_password}' />";
    }

    public function update_callback($stateParameters)
    {
        $password = $stateParameters->data['password_hash'];
        if (!empty($password) && $password != $this->not_a_password) {
            $stateParameters->data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        } else {
            unset($stateParameters->data['password_hash']);
        }

        return $stateParameters;
    }
}
