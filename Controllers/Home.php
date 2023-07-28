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

class Home extends BaseController
{

    protected $helpers = ['form','rando'];
    protected $regionModel;



    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->regionModel = model('Region');
    }
   
 
    public function index(){
        // $regionModel = model('Region');

 
        $this->viewData['errors'] = [];
        $this->viewData['region'] = $this->regionModel->findAll();

        // Registration Form submitted
        if ($this->request->is('post')) {

            $validation = \Config\Services::validation();


            $rules = [
                'region' => [
                    "required",
                    "is_not_unique[region.id]",  // the region exists (redundant test below)
                    // This database design allows only one person to manage a region. 
                    static function ($value, $data, &$error, $field) {
                        $regionModel = model('Region');
                        $r = $regionModel->find($value);
                        if(empty($r)) 
                          throw new Exception("Unknown region ID ($value). Registration failed.");
                        if (empty($r['user_id'])) {
                            return true;
                        }
                        $region_text = $r['state_code'] . ':' . $r['region_name'];            
                        $error = "The region selected ($region_text) already has an organizer/rba assigned.";            
                        return false;
                    },
                ],
                'first'  => 'trim|required|alpha_space',
                'last'  => 'trim|required|alpha_space',
                'password'  => 'trim|required|min_length[8]',
                'email'  => 'trim|required|valid_email|is_unique[user.email]'
            ];

            $errorMessages = [
                'email' => [
                    'is_unique' => 'A user with this email already exists. Use a different email.'
                ]
            ];

            $validation->setRules($rules,$errorMessages);

            $requestData = $this->request->getVar(array_keys($rules),
                FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if (! $validation->run($requestData)) {
                $this->view_data['errors']=$validation->getErrors();
            }else{
                 $this->register_new_user($requestData);
                 return $this->load_view(['register_success']);
            }
        }     

        return $this->load_view(['hero','home']);
        
    }

    protected function register_new_user($requestData){
        extract($requestData);
        $password_hash = 
            password_hash($password, PASSWORD_DEFAULT);

        //$userModel = model('User');
        $userData = compact(['first','last','email','password_hash']);
        $this->userModel->insert($userData);

        $u = $this->userModel->where('email', $email)->first();
        $userID = $u['id'];

        // $regionModel = model('Region');
        $this->regionModel->update($region,['user_id'=>$userID]);
    }

    

}
