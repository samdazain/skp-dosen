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
     * @var list<string>
     */
    protected $helpers = ['form', 'url', 'navigation', 'user', 'table'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Load role helper globally
        helper(['role']);

        // Ensure session service is available
        $this->session = \Config\Services::session();

        // Debug session data with more detail
        if ($this->session->has('isLoggedIn')) {
            $sessionData = [
                'user_id' => $this->session->get('user_id'),
                'user_role' => $this->session->get('user_role'),
                'user_name' => $this->session->get('user_name'),
                'isLoggedIn' => $this->session->get('isLoggedIn')
            ];
            log_message('debug', 'BaseController - Session data: ' . json_encode($sessionData));

            // Specifically log the user role for debugging
            $userRole = $this->session->get('user_role');
            log_message('debug', 'BaseController - User role from session: ' . ($userRole ?? 'NULL') . ' (type: ' . gettype($userRole) . ')');
        } else {
            log_message('debug', 'BaseController - No active session found');
        }

        // If user is logged in, ensure semester is loaded
        if (session()->get('isLoggedIn')) {
            $semesterModel = new \App\Models\SemesterModel();
            $activeSemesterId = session()->get('activeSemesterId');

            if (!$activeSemesterId) {
                $semester = $semesterModel->getCurrentSemester();
                if ($semester) {
                    session()->set('activeSemesterId', $semester['id']);
                    session()->set('activeSemesterText', $semesterModel->formatSemester($semester));
                }
            }
        }

        // Preload any models, libraries, etc, here.
        // Load helpers
        helper($this->helpers);

        // E.g.: $this->session = service('session');
    }
}
