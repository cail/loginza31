<?php
/**
*
*/

namespace cail\loginza31\controller;

require_once 'LoginzaAPI.class.php';
require_once 'LoginzaUserProfile.class.php';

define('LOGINZA_REGISTER_DEFAULT_LOGIN_PREFIX', 'loginza');

use \LoginzaAPI;
use \LoginzaUserProfile;

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
		global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx;
		
		// если регистраци отключена
		if ($config['require_activation'] == USER_ACTIVATION_DISABLE)
		{
			trigger_error('UCP_REGISTER_DISABLE');
		}
		
		$LoginzaAPI = new LoginzaAPI();
		
		// запрос профил€ авторизованного пользовател€
		
		$profile = $LoginzaAPI->getAuthInfo($request->variable('token', '', true, \phpbb\request\request_interface::POST));
		
		// проверка на ошибки
		if (is_object($profile) && empty($profile->error_type)) {
			// поиск пользовател€ в Ѕƒ
			if ( !($user_id = $this->findUserByIdentity($profile->identity)) ) {
				$user_id = $this->regUser($profile);
			}
			
		}

		// авторизаци€ юзера
		$result = $user->session_create($user_id, 0, 1);
		
		// если сесси€ успешно создана
		if ($result === true) {
			$redirect = request_var('redirect', "{$phpbb_root_path}index.$phpEx");
			$message = $user->lang['LOGIN_REDIRECT'];
			$l_redirect = (($redirect === "{$phpbb_root_path}index.$phpEx" || $redirect === "index.$phpEx") ? $user->lang['RETURN_INDEX'] : $user->lang['RETURN_PAGE']);

			// append/replace SID (may change during the session for AOL users)
			$redirect = reapply_sid($redirect);

			// Special case... the user is effectively banned, but we allow founders to login
			if (defined('IN_CHECK_BAN') && $result['user_row']['user_type'] != USER_FOUNDER)
			{
				return;
			}

			$redirect = meta_refresh(3, $redirect);
			trigger_error($message . '<br /><br />' . sprintf($l_redirect, '<a href="' . $redirect . '">', '</a>'));
		}
		
		page_header($user->lang['LOGIN'], false);

		$template->set_filenames(array(
			'body' => 'login_body.html')
		);
		make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"));

		page_footer();
	}

	/**
	 * ѕоиск существующего пользовател€ по его identity
	 *
	 * @param string $identity
	 * @return array
	 */
	function findUserByIdentity ($identity) {
		global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx;
		
		$result = $db->sql_query("
			SELECT *
			FROM `".USERS_TABLE."`
			WHERE `loginza_identity` = '".$db->sql_escape($identity)."'
		");
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		return @$row['user_id'];
	}
	
	/**
	 * –егистраци€ пользовател€
	 *
	 * @param unknown_type $profile
	 * @return unknown
	 */
	function regUser ($profile) {
		global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx;
		
		// объект генерации полей профил€
		$LoginzaProfile = new LoginzaUserProfile($profile);
		
		// тайм зона поумолчанию
		$timezone = date('Z') / 3600;
		$is_dst = date('I');

		if ($config['board_timezone'] == $timezone || $config['board_timezone'] == ($timezone - 1)) {
			$timezone = ($is_dst) ? $timezone - 1 : $timezone;

			if (!isset($user->lang['tz_zones'][(string) $timezone]))
			{
				$timezone = $config['board_timezone'];
			}
		} else {
			$is_dst = $config['board_dst'];
			$timezone = $config['board_timezone'];
		}
		
		// сгенерированный пароль
		$gen_password = $LoginzaProfile->genRandomPassword();
		
		$data = array(
			'username'				=> utf8_normalize_nfc($LoginzaProfile->genNickname()),
			'user_password'			=> phpbb_hash($gen_password),
			'user_email'			=> strtolower($profile->email),
			'user_birthday'			=> date('d-m-Y', strtotime($profile->dob)),
			'user_avatar' 			=> (string)$profile->photo,
			'user_from' 			=> (string)$profile->address->home->city,
			'user_icq' 				=> (string)$profile->im->icq,
			'user_jabber' 			=> (string)$profile->im->jabber,
			'user_website' 			=> (string)$LoginzaProfile->genUserSite(),
			'user_timezone'			=> (float) $timezone,
			'user_dst'				=> $is_dst,
			'user_lang'				=> basename($user->lang_name),
			'user_type'				=> USER_NORMAL,
			'user_actkey'			=> '',
			'user_ip'				=> $user->ip,
			'user_regdate'			=> time(),
			'user_inactive_reason'	=> 0,
			'user_inactive_time'	=> 0,
			'loginza_identity' 		=> $profile->identity,
			'loginza_provider'		=> $profile->provider
		);
		
		$error = array();
		
		// валидаци€ полей
		$username_errors = validate_data($data, array(
			'username'			=> array(
				array('string', false, $config['min_name_chars'], $config['max_name_chars']),
				array('username', ''))
		));
		
		// логин зан€т или не удовлетвор€ет настройкам phpBB
		if (count($username_errors)) {
			// генерируем уникальный логин
			$result = $db->sql_query("
				SELECT count(`user_id`) AS `count`
				FROM `".USERS_TABLE."`
				WHERE 1
			");
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			$data['username'] = LOGINZA_REGISTER_DEFAULT_LOGIN_PREFIX.$row['count'];
		}
		
		$error = array();
		
		// DNSBL check
		if ($config['check_dnsbl'])
		{
			if (($dnsbl = $user->check_dnsbl('register')) !== false)
			{
				$error[] = sprintf($user->lang['IP_BLACKLISTED'], $user->ip, $dnsbl[1]);
			}
		}

		// если нету ошибок
		if (!count($error)) {
			$server_url = generate_board_url();

			// группа пользовател€
			$sql = 'SELECT group_id
				FROM ' . GROUPS_TABLE . "
				WHERE group_name = '" . $db->sql_escape('REGISTERED') . "'
					AND group_type = " . GROUP_SPECIAL;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row) {
				trigger_error('NO_GROUP');
			}
			
			// группа пользовател€
			$data['group_id'] = (int) $row['group_id'];
			
			// лимит сообщений нового пользовател€
			if ($config['new_member_post_limit']) {
				$data['user_new'] = 1;
			}
			
			// регистраци€ нового польщовател€
			$user_id = user_add($data);
			
			// This should not happen, because the required variables are listed above...
			if ($user_id === false) {
				trigger_error('NO_USER', E_USER_ERROR);
			}

			// отправка сообщени€ о регистрации
			$email_template = 'user_welcome';
			
			if ($config['email_enable']) {
				include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);

				$messenger = new messenger(false);

				$messenger->template($email_template, $data['lang']);

				$messenger->to($data['email'], $data['username']);

				$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
				$messenger->headers('X-AntiAbuse: User_id - ' . $user->data['user_id']);
				$messenger->headers('X-AntiAbuse: Username - ' . $user->data['username']);
				$messenger->headers('X-AntiAbuse: User IP - ' . $user->ip);

				$messenger->assign_vars(array(
					'WELCOME_MSG'	=> htmlspecialchars_decode(sprintf($user->lang['WELCOME_SUBJECT'], $config['sitename'])),
					'USERNAME'		=> htmlspecialchars_decode($data['username']),
					'PASSWORD'		=> htmlspecialchars_decode($gen_password)
					)
				);

				$messenger->send(NOTIFY_EMAIL);
			}
			
			return $user_id;
			
		} else {
			trigger_error (implode('', $error));
		}
		
		return false;
	}	

}
