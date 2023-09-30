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
    protected $regionModel;

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
        if (!isset($this->viewData['session']['logged_in'])) $this->viewData['session']['logged_in'] = false;
        $this->viewData['errors'] = [];

        $this->userModel = model('User');
        $this->regionModel = model('Region');
    }

    protected function load_view($view_list,$navbar=true)
    {

        $views =  view('head', $this->viewData);
        
        if($navbar)  $views .= view('navbar', $this->viewData);

        if (is_array($view_list)) {
            foreach ($view_list as $v) {
                if (is_array($v)) {
                    $v[] = ['saveData' => false];
                    $views .= view(...$v);
                } else {
                    $views .= view($v, $this->viewData);
                }
            }
        } else {
            $views .= view($view_list, $this->viewData);
        }

        $views .= view('foot');
        return $views;
    }

    protected function die_info($severity, $text)
    {
        $this->die_message($severity, $text, ['backtrace' => false]);
    }

    protected function die_message($severity, $text, $options = [])
    {

        $text = is_string($text) ? $text : print_r($text, true);

        $backtrace = ($options['backtrace'] ?? true) ? $this->formatted_backtrace() : '';
        $file_line = ($options['file_line'] ?? '');

        $viewData = compact('severity', 'text', 'backtrace', 'file_line');

        echo $this->load_view([['message', $viewData]]);

        exit();
    }

    protected function die_message_notrace($severity, $text){
        $this->die_message($severity,$text,['backtrace'=>false]);
    }


    protected function die_exception($e)
    {
        $file_line = $e->getFile() . '(' . $e->getLine() . ')';
        $file_line = str_replace(APPPATH, '', $file_line);
        $status = $e->GetMessage();
        $this->die_message("Exception", $status, ['file_line' => $file_line]);
    }

    function formatted_backtrace()
    {
        $result = '';

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {
            if ($trace['function'] == __FUNCTION__)
                continue;

            $parameters = isset($trace['args']) && is_array($trace['args']) ? print_r($trace['args'], true) : "";

            if (array_key_exists('class', $trace))
                $result .= sprintf(
                    "%s:%s %s::%s(%s)<br>",
                    $trace['file'] ?? 'File?',
                    $trace['line'] ?? 'Line?',
                    $trace['class'] ?? 'Class?',
                    $trace['function'] ?? 'Function?',
                    $parameters
                );
            else
                $result .= sprintf(
                    "%s:%s %s(%s)<br>",
                    $trace['file'] ?? 'File?',
                    $trace['line'] ?? 'Line?',
                    $trace['function'] ?? 'Function?',
                    $parameters
                );
        }

        return $result;
    }

    protected function isLoggedIn()
    {
        return (false == $this->session->get('logged_in')) ? false : true;
    }

    protected function isSuperuser()
    {
        return $this->session->get('is_superuser') ? true : false;
    }

    protected function getMemberID()
    {
        return $this->session->get('user_id');
    }

     protected function login_check()
    {
        if (false == $this->isLoggedIn()) {
            $login_url = site_url("login");
            $this->die_message_notrace('Access denied',  
            "Please <A HREF=$login_url>log in</A> before using this function.");
        }
    }

    protected function isRBAforClub($acp_club_code){
        $region_list = $this->session->get('authorized_regions');
        return (false===array_search($acp_club_code,$region_list))?false:true;
    }


	protected function isAdmin($club_acp_code = null)
	{
		if (false == $this->isLoggedIn()) return false;
		if ($this->isSuperuser()) return true;
		if (empty($club_acp_code)) return false;
		return $this->isRBAforClub($club_acp_code);
	}

	protected function die_not_admin($club_acp_code = null)
	{

		if (false == $this->isAdmin($club_acp_code)) {
			$this->die_message_notrace(
				'Access Denied',
				"Must be RBA/Organizer for Club (ACP ID $club_acp_code) to use this function."
			);
		}
	}

    
    protected function becomeUser($user)
    {
        if (is_numeric($user)) {
            $user = $this->userModel->where(['id' => $user])->first();
        }
        if (!empty($user)) {
            $this->session->set('logged_in', TRUE);
            $this->session->set('user_id', $user['id']);
            $this->session->set('first_name', $user['first']);
            $this->session->set('last_name', $user['last']);
            $this->session->set('first_last', $user['first'] . ' ' . $user['last']);
            $this->session->set('authorized_regions', $this->regionModel->getAuthorizedRegions($user['id']));
            $this->session->set('is_superuser', str_contains($user['privilege'], 'superuser'));
            return $user;
        } else {
            return false;
        }
    }

    protected function clearSession(){
        $this->session->remove('logged_in');
        $this->session->remove('user_id');
        $this->session->remove('first_name');
        $this->session->remove('last_name');
        $this->session->remove('first_last');
        $this->session->remove('authorized_regions');
        $this->session->remove('is_superuser');
    }
}
