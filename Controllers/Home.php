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

    protected $helpers = ['form', 'rando'];
    protected $regionModel;
    protected $eventModel;


    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->regionModel = model('Region');
        $this->eventModel = model('Event');
        $this->viewData['errors'] = [];
        $this->viewData['region'] = $this->regionModel->getRegions();
        $this->viewData['event_table'] = $this->eventModel->getEventTable();
    }

    public function index()
    {


        if ($this->session->get('logged_in')) {
            return $this->load_view(['hero', 'home', 'events', 'apps']);
        } else {

            $captcha = $this->bike_captcha();
            $this->session->set('is_bike', $captcha['is_bike']);
            $this->viewData = array_merge($this->viewData, $captcha);
            return $this->load_view(['hero', 'home', 'events', 'apps', 'register']);
        }
    }


    public function register()
    {

        // Registration Form submitted
        if ($this->request->is('post')) {

            $validation = \Config\Services::validation();

            if ($this->session->get('logged_in')) {
                $this->die_message("Logged In", "Please log out before registering as a new user.");
            }


            $rules = [
                'region' => [
                    "required",
                    "is_not_unique[region.id]",  // the region exists (redundant test below)
                    // This database design allows only one person to manage a region. 
                    static function ($value, $data, &$error, $field) {
                        $regionModel = model('Region');
                        $r = $regionModel->find($value);
                        if (empty($r))
                            throw new \Exception("Unknown region ID ($value). Registration failed.");
                        if (empty($r['rba_user_id'])) {
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
                'email'  => 'trim|required|valid_email|is_unique[user.email]',
                'v' => [fn ($value) => $this->bicycle_check($value)]
            ];

            $errorMessages = [
                'email' => [
                    'is_unique' => 'A user with this email already exists. Use a different email.'
                ],
                'v' => [0 =>    'You did not click all the bicycles, or you clicked things that were not bicycles.']

            ];



            $validation->setRules($rules, $errorMessages);

            $requestData = $this->request->getVar(
                array_keys($rules),
                FILTER_SANITIZE_FULL_SPECIAL_CHARS
            );

            if (!$validation->run($requestData)) {
                $this->viewData['errors'] = $validation->getErrors();
            } else {
                $this->register_new_user($requestData);
                return $this->load_view(['register_success']);
            }
        }

        $captcha = $this->bike_captcha();
        $this->session->set('is_bike', $captcha['is_bike']);
        $this->viewData = array_merge($this->viewData, $captcha);
        return $this->load_view(['hero', 'home', 'events', 'apps', 'register']);


        // throw new \Exception("This function only handles POST requests.");
    }

    protected function register_new_user($requestData)
    {
        extract($requestData);
        $password_hash =
            password_hash($password, PASSWORD_DEFAULT);

        //$userModel = model('User');
        $userData = compact(['first', 'last', 'email', 'password_hash']);
        $this->userModel->insert($userData);

        $u = $this->userModel->where('email', $email)->first();

        if (empty($u)) trigger_error("Could not find user_id of user just inserted!?", E_USER_ERROR);

        $userID = $u['id'];

        // $regionModel = model('Region');
        $this->regionModel->update($region, ['rba_user_id' => $userID]);
    }


    public function bike_captcha($nChecks = 8)
    {

        $is_bike = [];
        $vehicle_icon = [];
        $bikes = ['fas fa-bicycle', 'fa-solid fa-person-biking'];
        $not_bikes = ['fas fa-car', 'fas fa-truck', 'fas fa-plane', 'fa-solid fa-car-side',  'fa-solid fa-bus', 'fa-solid fa-van-shuttle',
        'fas fa-rocket', 'fas fa-taxi', 'fas fa-space-shuttle', 'fa-solid fa-truck-monster'];
        $vehicle_checkboxes = "";
        $one_bike = mt_rand(0, $nChecks - 1);
        for ($i = 0; $i < $nChecks; $i++) {
            if ($i == $one_bike || mt_rand(0, 1) == 1) {
                $is_bike[$i] = true;
                $vehicle_icon[$i] = $bikes[mt_rand(1, count($bikes)) - 1];
            } else {
                $is_bike[$i] = false;
                $vehicle_icon[$i] = $not_bikes[mt_rand(1, count($not_bikes)) - 1];
            }
            $vehicle_checkboxes .= "<input type='checkbox' name='v[]' value='v$i'><i class='{$vehicle_icon[$i]}'></i> ";
        }

        return compact('vehicle_checkboxes', 'vehicle_icon', 'is_bike');
    }

    public function bicycle_check($str, $nBikes = 8)
    {
        $bike_error = false;
        for ($i = 0; $i < $nBikes; $i++) {
            $vi = $this->request->getVar("v");
            $isChecked = (isset($vi) && false !== array_search("v$i", $vi)) ? 'true' : 'false';
            $v[$i] = $isChecked;
            $isBike = $this->session->get('is_bike');
            if (empty($isBike)) return false;
            if ($isBike[$i] != $isChecked) {
                $bike_error = true;
            }
        }

        return !$bike_error;
    }
}
