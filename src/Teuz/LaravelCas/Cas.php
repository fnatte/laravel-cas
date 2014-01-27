<?php namespace Teuz\LaravelCas;

class Cas
{
	protected static $xmlNamespace = "http://www.yale.edu/tp/cas";

	// Dependencies
	private $auth = null;
	private $request = null;
	private $session = null;
	private $redirect = null;
	private $curl = null;

	// Internal state
	private $ticket = null;
	private $ticketHasBeenTested = false;
	private $vaildTicket = false;
	private $userCasIdentifier = '';

	// Config variables
	private $url = '';
	private $service = null;
	private $userField = '';
	private $createUsers = true;

	/**
	 * Creates a new CAS service.
	 * 
	 * @param IlluminateAuthAuthManager       $auth
	 * @param IlluminateHttpRequest           $request
	 * @param IlluminateSessionSessionManager $session
	 * @param IlluminateRoutingRedirector     $redirect
	 * @param anlutrocURLcURL                 $curl
	 * @param string                          $url			Url for CAS-server
	 * @param string                          $service		Service path to be passed to the CAS-server as parameter
	 * @param string                          $userField 	User key identifier on local user object
	 * @param boolean                         $createUsers 	Whether or not to create missing users
	 */
	public function __construct(
		\Illuminate\Auth\AuthManager $auth, 
		\Illuminate\Http\Request $request,
		\Illuminate\Session\SessionManager $session,
		\Illuminate\Routing\Redirector $redirect,
		\anlutro\cURL\cURL $curl,
		$url,
		$service = '/user/cas',
		$userField = 'username',
		$createUsers = true)
	{
		// Set dependencies
		$this->auth = $auth;
		$this->request = $request;
		$this->session = $session;
		$this->redirect = $redirect;
		$this->curl = $curl;

		// Configure
		$this->url = $url;
		$this->service = $request->root() . $service;
		$this->userField = $userField;
		$this->createUsers = $createUsers;
	}

	/**
	 * Reads and parses ticket parameter from input.
	 * If successful, result is saved to $this->ticket.
	 * 
	 * @return void
	 */
	protected function readTicket()
	{
		$ticket = $this->request->input('ticket');

		if(preg_match('/^[SP]T-/', $ticket))
		{
			$this->ticket = $ticket;
		}
		else if (!empty($ticket))
		{
			// Bad ticket
			throw new Exception("Bad ticket found in URL. (ticket=\"".htmlentities($ticket)."\")");
		}
	}

	/**
	 * Validates current ticket against the CAS-server.
	 * Returns true if the ticket is successfully validated.
	 * 
	 * @return boolean
	 */
	protected function validateTicket()
	{
		// If the ticket is validated already, there's no need to validate the ticket again.
		if($this->ticketHasBeenTested) return $this->vaildTicket;

		// Perform ticket validation against CAS-server
		$response = $this->curl->get(
			$this->curl->buildUrl($this->url . '/serviceValidate', array(
				'service' => $this->service,
				'ticket' => $this->ticket
			))
		);

		// By now the ticket is considered tested (even though it might fail parsing etc.)
		$this->ticketHasBeenTested = true;

		// Try to parse the response.
		try
		{
			$root = new \SimpleXMLElement($response->body);
		}
		catch(Exception $e)
		{
			// Failed to parse response.
			// Ticket not validated.
			return false;
		}
		

		// Quick check of response document
		if($root->getName() == "serviceResponse" &&  in_array(self::$xmlNamespace, $root->getNamespaces()))
		{
			$casns = $root->children(self::$xmlNamespace);

			// Does the document contain a authentication success?
			if(isset($casns->authenticationSuccess) && isset($casns->authenticationSuccess->user))
			{
				// Get user
				$this->userCasIdentifier = $casns->authenticationSuccess->user;
				$this->vaildTicket = true;
			}
		}

		return $this->vaildTicket;
	}

	/**
	 * Perform ticket and credential check.
	 * Returns true if the user was successfully logged in.
	 * 
	 * @return boolean
	 */
	public function check()
	{
		$this->readTicket();

		if($this->ticket)
		{
			if($this->validateTicket())
			{
				// Get user from local authentication system
				$user = $this->auth->getProvider()->retrieveByCredentials(array(
					$this->userField => $this->userCasIdentifier
				));

				// If the user did not exist, create it if allowed by config.
				if($user == null)
				{
					if($this->createUsers)
					{
						// Create and login user
						$user = $this->auth->getProvider()->createModel();
						$user->{$this->userField} = $this->userCasIdentifier;
						$user->save();
						$this->auth->login($user);
						return true;
					}
					else
					{
						// It was a vaild user, but it has no auth rights on this system.
						return false;
					}
				}
				else
				{
					// The user exists. Login at the local system.
					$this->auth->login($user);
					return true;
				}
			}
		}

		return $this->vaildTicket;
	}

	/**
	 * Perform login. Note that you display the returned redirection response to the user.
	 * Returns a redirect response to the CAS login page.
	 * 
	 * @return \Illuminate\Http\RedirectResponse 
	 */
	public function login()
	{
		$this->session->flash('teuz/laravel-cas/redirect', $this->request->url());
		return $this->redirect->to($this->url . '/login?service=' . urlencode($this->service));
	}

	/**
	 * Perform reload. This should be done after a sucessful check.
	 * Returns a redirect response.
	 * 
	 * @return \Illuminate\Http\RedirectResponse 
	 */
	public function reload()
	{
		return $this->redirect->to($this->session->get('teuz/laravel-cas/redirect'));
	}

	/**
	 * Logout user from the local system and returns a redirect response to the CAS logout.
	 * The parameters $params can take two values, url and service. Those will be used in
	 * the CAS redirection URL. For more information about what the parameters actually do,
	 * see the CAS protocol (linked using see).
	 *
	 * @see https://github.com/Jasig/cas/blob/master/cas-server-protocol/3.0/cas_protocol_3_0.md#231-parameters CAS Protocol 3.0 on logout parameters
	 * @param  array  $params
	 * @return \Illuminate\Http\RedirectResponse 
	 */
	public function logout($params = array())
	{
		// Set param defaults
		if(!is_array($params)) $params = array();
		if(!isset($params['url'])) $params['url'] = $this->request->root();
		if(!isset($params['service'])) $params['service'] = $this->request->root();

		// Build url
		$url = $this->url . '/logout?';
		if($params['url']) $url .= 'url=' . urlencode($params['url']) . '&';
		if($params['service']) $url .= 'service=' . urlencode($params['service']);

		// Logout and redirect
		$this->auth->logout();
		return $this->redirect->to($url);
	}
}
