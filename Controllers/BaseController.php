<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    protected $session;
    protected $viewData = [];
    protected $userModel;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        $this->session = \Config\Services::session();    
        $this->viewData['session'] = $this->session->get();
        if(!isset($this->viewData['session']['logged_in'])) $this->viewData['session']['logged_in']=false;
        $this->viewData['errors'] = [];

        $this->userModel = model('User');

    }

    protected function load_view($view_list){

        $views =         
            view('head',$this->viewData) .
            view('navbar',$this->viewData) ;

        if (is_array($view_list)){
            foreach($view_list as $v) $views .= view($v);
        }else{
            $views .= view($view_list);
        }
		
		$views .= view('foot');
        return $views;

	}
    
    protected function die_message($severity, $text){

        $this->viewData['message']=compact('severity','text');

        echo $this->load_view(['message']); 

        exit();
    }

    protected function login_check(){
        if (false == $this->session->get('logged_in')) {
            $this->die_message('Access denied',  'Not logged in.');
        }
    }

	

}
