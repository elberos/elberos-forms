<?php
/**
 * Plugin Name: Elberos Forms
 * Plugin URI:  https://github.com/elberos/elberos-forms
 * Description: Elberos Forms
 * Version:     0.1.0
 * Author:      Ildar Bikmamatov <support@elberos.org>
 * Author URI:  https://elberos.org/
 * License:     Apache License 2.0
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
 
 
if ( !class_exists( 'Elberos_Forms_Plugin' ) ) 
{

require_once "helpers/AmoCRMHelper.php";
require_once "include/api.php";

class Elberos_Forms_Plugin
{
	
	/**
	 * Init Plugin
	 */
	public static function init()
	{
		add_action(
			'admin_init', 
			function(){
				require_once "include/forms.php";
				require_once "include/integrations.php";
				require_once "include/mail-settings.php";
				require_once "helpers/AmoCRMHelper.php";
			}
		);
		add_action('admin_menu', 'Elberos_Forms_Plugin::register_admin_menu' );
		add_action('rest_api_init', 'Elberos_Forms_Plugin::register_api' );
	}
	
	
	/**
	 * Register Admin Menu
	 */
	public static function register_admin_menu()
	{
		add_menu_page(
			'Elberos Forms', 'Elberos Forms', 
			'manage_options', 'elberos-forms',
			function ()
			{
				\Elberos\Forms\Settings::show();
			},
			null
		);
		
		add_submenu_page(
			'elberos-forms', 
			'Forms data', 'Forms data', 
			'manage_options', 'elberos-forms-data', 
			function()
			{
				//\Elberos\Forms\Data::show();
				echo "1234";
			}
		);
		
		
		add_submenu_page(
			'elberos-forms', 
			'Integrations', 'Integrations', 
			'manage_options', 'elberos-forms-integrations', 
			function()
			{
				\Elberos\Forms\Integrations::show();
			}
		);
		
		add_submenu_page(
			'elberos-forms', 
			'Mail Settings', 'Mail Settings', 
			'manage_options', 'elberos-forms-mail-settings', 
			function()
			{
				\Elberos\Forms\MailSettings::show();
			}
		);
		
	}
	
	
	/**
	 * Register API
	 */
	public static function register_api()
	{
		register_rest_route
		(
			'elberos_forms',
			'submit_form',
			array(
				'methods' => 'POST',
				'callback' => function ($arr){ return \Elberos\Forms\Api::submit_form($arr); },
			)
		);
		register_rest_route
		(
			'elberos_forms',
			'reload_amocrm_settings',
			array(
				'methods' => 'POST',
				'callback' => function ($arr){ return \Elberos\Forms\Api::reload_amocrm_settings($arr); },'permission_callback' => function ()
				{
					$user_id = apply_filters( 'determine_current_user', false );
					$user = get_userdata($user_id);
					if ( in_array( 'administrator', (array) $user->roles ) )
					{
						return true;
					}
					return false;
				}
			)
		);
	}
	
}

Elberos_Forms_Plugin::init();

}
