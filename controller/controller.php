<?php
/**
*
*/

namespace phpbb\boardannouncements\controller;

class controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\config\db_text */
	protected $config_text;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Constructor
	*
	* @param \phpbb\config\config                $config         Config object
	* @param \phpbb\config\db_text               $config_text    DB text object
	* @param \phpbb\db\driver\driver_interface   $db             Database object
	* @param \phpbb\request\request              $request        Request object
	* @param \phpbb\user                         $user           User object
	* @access public
	*/
	public function __construct(\phpbb\config\config $config,
								\phpbb\config\db_text $config_text,
								\phpbb\db\driver\driver_interface $db,
								\phpbb\request\request $request,
								\phpbb\user $user)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->db = $db;
		$this->request = $request;
		$this->user = $user;
	}

	/**
	*
	* @throws \phpbb\exception\http_exception An http exception
	* @return \Symfony\Component\HttpFoundation\JsonResponse A Symfony JSON Response object
	* @access public
	*/
	public function auth()
	{
		//

		// We shouldn't get here, but throw an http exception just in case
		throw new \phpbb\exception\http_exception(500, 'GENERAL_ERROR');
	}

}
