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
//use Config\Services;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Login extends BaseController
{

    protected $helpers = ['form','rando'];
    protected $userModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->userModel = model('User');

    }
   
    public function logout(){
        $this->session->set('logged_in', FALSE);
        return redirect()->route('home'); 
    }

    public function index(){return $this->login();}
 
    public function login(){

         if ($this->session->get('logged_in')) {
            return redirect()->to('/events'); // Was already logged in
         }

        if ($this->request->is('post')) {
            
            if($this->request->getVar('submit')=='cancel') {
                return redirect()->to('/home');
            }else{


           
            $validation = \Config\Services::validation();


            $rules = [
                'password'  => 'trim|required',
                'email'  => 'trim|required|valid_email'
            ];


            $validation->setRules($rules);

            $requestData = $this->request->getVar(array_keys($rules),
                FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if (! $validation->run($requestData)) {
                $this->viewData['errors']=$validation->getErrors();
            }else{
                $email = $this->request->getVar('email');
                $password = $this->request->getVar('password');
    
                $user = $this->userModel->where('email', $email)->first();
                if (!empty($user) && password_verify($password, $user['password_hash'])) {        
                    $this->session->set('logged_in', TRUE);
                    $this->session->set('user_id', $user['id']);
                    $this->session->set('first_name', $user['first']);
                    $this->session->set('last_name', $user['last']);
                    $this->session->set('first_last', $user['first'] . ' ' . $user['last']);
                    
                    return redirect()->route('events');
                                } else {
                    // If login fails, reload the login view with an error message
                    $this->viewData['login_error'] = 'Invalid username or password.';
                    return $this->load_view(['hero','login']);
                }

            }
        }     }



        return $this->load_view(['hero','login']);

    }   

    

}