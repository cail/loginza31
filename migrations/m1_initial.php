<?php
/**
*
*
* @copyright (c) 2014 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace cail\loginza31\migrations;

/**
* Migration stage 1: Initial schema changes to the database
*/
class m1_initial extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\gold');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'loginza_identity'	=> array('VCHAR:255', ''),
					'loginza_provider'  => array('VCHAR:255', ''),
				),
			),
			'add_index' => array(
				$this->table_prefix . 'users'	=> array(
					'loginza_identity',
				),	
			),	
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'users'	=> array(
					'loginza_identity', 'loginza_provider'
				),
			),
		);
	}
}
