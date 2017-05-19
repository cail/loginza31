<?php

namespace cail\loginza31\event;

/**
* Event listener
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class page_header_after implements EventSubscriberInterface
{

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\controller\helper */
	protected $controller_helper;

	public function __construct(\phpbb\config\config $config,
								\phpbb\template\template $template,
								\phpbb\user $user,
								\phpbb\controller\helper $controller_helper)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->controller_helper = $controller_helper;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.page_header_after'	=> 'page_header_after',
		);
	}

	/**
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function page_header_after($event)
	{
		global $db, $config, $template, $SID, $_SID, $_EXTRA_URL, $user, $auth, $phpEx, $phpbb_root_path;

		// Add board announcements language file
		$this->user->add_lang_ext('cail/loginza31', 'loginza');

		// Output board announcement to the template
		$this->template->assign_vars(array(
			'S_LOGINZA'			=> true,
			'LOGINZA_RETURN_URL'	=> urlencode( generate_board_url(true) . 
				$this->controller_helper->route('cail_loginza31_controller',
					array('hash' => generate_link_hash('auth')), false, true)
			),
			'xLOGINZA_RETURN_URL'  => urlencode( append_sid(generate_board_url() . "/ucp.$phpEx", 'mode=register')),
		));
	}
}
