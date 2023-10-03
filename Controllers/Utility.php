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

require_once(APPPATH . 'Libraries/Secret/Secrets.php');  // for token

use Secrets;

class Utility extends BaseController
{

    protected $helpers = ['form'];
    protected $rusaModel;
    protected $rusaLibrary;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->rusaModel = model('Rusa');
    }

    public function rusa_update($token=null)
    {

        if(false == $this->isSuperuser()){
            if($token == null || $token != Secrets::utility) $this->die_info('Access Denied', "You are not authorized to execute this function");
        }
        try {
            $n = $this->rusaModel->cache_update();
        } catch (\Exception $e) {
            $this->die_exception($e);
        }
        $this->die_message(__METHOD__, "Loaded $n RUSA members on " . date('c'), ['backtrace'=>false]);
    }

    public function show_session()
    {
        $this->die_message_notrace('Session', print_r($this->session->get(),true));
    }
}
