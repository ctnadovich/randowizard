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
        $crud->fieldType('password', 'password');
        $crud->callbackEditField('password', array($this, 'set_password_input_to_empty'));
        $crud->callbackBeforeUpdate(array($this, 'encrypt_password_callback'));
        $output = $crud->render();
        $this->viewData = array_merge((array)$output, $this->viewData);
        return $this->load_view(['profile']);
    }

    public function encrypt_password_callback($post_array, $primary_key = null)
    {
        if (!empty($post_array['password'])) {
            $post_array['password'] = password_hash($post_array['password'], PASSWORD_DEFAULT);
        } else {
            unset($post_array['password']);
        }

        //	  $this->die_error(__METHOD__,print_r($post_array,true));


        return $post_array;
    }

    public function set_password_input_to_empty()
    {
        return "<input type='password' name='password' value='' />";
    }
}
