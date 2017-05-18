<?php
/**
*
* Loginza extension for the phpBB Forum Software package.
*
*/

namespace cail\loginza31;

/**
* Extension class for custom enable/disable/purge actions
*/
class ext extends \phpbb\extension\base
{
	/**
	 * Enable extension if phpBB minimum version requirement is met
	 *
	 * Requires phpBB 3.1.3 due to usage of new exception classes.
	 *
	 * @return bool
	 * @aceess public
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return phpbb_version_compare($config['version'], '3.1.3', '>=');
	}
}
