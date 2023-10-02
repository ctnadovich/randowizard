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

require_once(APPPATH . 'Libraries/Secret/Secrets.php');  // for Bike Captcha

use Secrets;

class Home extends BaseController
{

    protected $helpers = ['form', 'rando'];
    protected $eventModel;


    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->eventModel = model('Event');
        $this->viewData['errors'] = [];
        $this->viewData['region'] = $this->regionModel->getRegions();
    }

    public function home()
    {

        // $this->viewData['eventful_regions'] = $this->regionModel->hasEvents();

        if ($this->session->get('logged_in')) {
            return $this->load_view(['hero']); // , 'eventful_regions']);
        } else {
            $captcha = $this->bike_captcha();
            $this->viewData = array_merge($this->viewData, $captcha);
            return $this->load_view(['hero', 'register']); // , 'eventful_regions']);
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
                    static function ($club_acp_code, $data, &$error, $field) {
                        $regionModel = model('Region');
                        $rbaModel = model('Rba');
                        $r = $regionModel->getClub($club_acp_code);
                        if (empty($r))
                            throw new \Exception("Unknown region ID ($club_acp_code). Registration failed.");
                        // Only the first person to sign up to manage a region can do so unauthenticated. 
                        if (false == $rbaModel->hasRBA($club_acp_code)) return true;
                        // if (empty($r['rba_user_id'])) {
                        //     return true;
                        // }
                        $region_text = $r['region_state_code'] . ':' . $r['region_name'];
                        $error = "The region selected ($region_text) already has an organizer/rba 
                        assigned. Please ask this previous organizer/rba to give you an invitation link so
                        you can be added as an additional organizer for this region";
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
                    'is_unique' => 'A user with this email already exists. Please use a different email.'
                ],
                'v' => [0 =>    'You did not click all the bicycles, or you clicked some vehicles that were not bicycles.']

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

        return $this->home();
    }

    protected function register_new_user($requestData)
    {
        $rbaModel = model('Rba');

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
        // $this->regionModel->update($region, ['rba_user_id' => $userID]);
        $rbaModel->insert(['user_id' => $userID, 'region_id' => $region], false) or
            $this->die_message(__METHOD__, "Couldn't add RBA to region.");
    }


    public function bike_captcha($nChecks = 8)
    {

        $is_bike = [];
        $vehicle_icon = [];
        $bikes = ['fas fa-bicycle', 'fa-solid fa-person-biking'];
        $not_bikes = [
            'fas fa-car', 'fas fa-truck', 'fas fa-plane', 'fa-solid fa-car-side',  'fa-solid fa-bus', 'fa-solid fa-van-shuttle',
            'fas fa-rocket', 'fas fa-taxi', 'fas fa-space-shuttle', 'fa-solid fa-truck-monster'
        ];
        $vehicle_checkboxes = "";
        $one_bike = mt_rand(0, $nChecks - 1);
        for ($i = 0; $i < $nChecks; $i++) {
            if ($i == $one_bike || mt_rand(0, 1) == 1) {
                $is_bike[$i] = $this->obfuscate_boolean(true);
                $vehicle_icon[$i] = $bikes[mt_rand(1, count($bikes)) - 1];
            } else {
                $is_bike[$i] = $this->obfuscate_boolean(false);
                $vehicle_icon[$i] = $not_bikes[mt_rand(1, count($not_bikes)) - 1];
            }
            $vehicle_checkboxes .= "<input type='checkbox' name='v[]' value='v$i'><i class='{$vehicle_icon[$i]}'></i> ";
        }

        return compact('vehicle_checkboxes', 'vehicle_icon', 'is_bike');
    }

    public function obfuscate_boolean($b)
    {
        $tf = $b ? 'T' : 'F';
        $r = bin2hex(random_bytes(8));
        $secret = Secrets::bicycle_captcha;
        $plaintext = "$r-$tf-$secret";
        $ciphertext = hash('sha256', $plaintext);
        $hash = strtoupper(substr($ciphertext, 0, 8));
        return "$r-$hash";
    }

    public function deobfuscate_boolean($h)
    {
        list($r, $hash) = explode('-', $h);
        $secret = Secrets::bicycle_captcha;
        $plaintext_true = "$r-T-$secret";
        $plaintext_false = "$r-F-$secret";
        $ciphertext = hash('sha256', $plaintext_true);
        $hash_true = strtoupper(substr($ciphertext, 0, 8));
        $ciphertext = hash('sha256', $plaintext_false);
        $hash_false = strtoupper(substr($ciphertext, 0, 8));
        return $hash == $hash_true ? true : ($hash == $hash_false ? false : null);
    }

    public function bicycle_check($str, $nBikes = 8)
    {
        $bike_error = false;


        $isBike = $this->request->getVar("is_bike");
        if (empty($isBike)) throw new \Exception("This can't happen. I forgot which were the bikes! Tell the developer. Thanks Sean for finding this bug.");
        $isBike = explode(',', $isBike);
        if (empty($isBike)) throw new \Exception("This can't happen. No bikes! Tell the developer. Thanks Sean for finding this bug.");

        for ($i = 0; $i < $nBikes; $i++) {
            $vi = $this->request->getVar("v");
            $isChecked = (isset($vi) && false !== array_search("v$i", $vi)) ? true : false;
            $v[$i] = $isChecked;
            $isReallyBike = $this->deobfuscate_boolean($isBike[$i]);
            if ($isReallyBike !== $isChecked) {
                $bike_error = true;
            }
        }

        return !$bike_error;
    }
}
