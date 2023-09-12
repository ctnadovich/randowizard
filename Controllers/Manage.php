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

class Manage extends EventProcessor
{

    protected $not_a_password = "not_a_password";


    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
    }

    public function manage_users()
    {
        $this->login_check();

        $subject = 'User';
        $crud = new GroceryCrud();
        $crud->setSubject($subject);
        $crud->setTable('user');
        $crud->fieldType('password_hash', 'password');
        $crud->displayAs('password_hash', 'Password');
        $crud->callbackEditField('password_hash', [$this, 'clear_password_field']);
        $crud->callbackBeforeUpdate([$this, 'update_callback']);
        $output = $crud->render();

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
