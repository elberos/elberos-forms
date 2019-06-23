<?php

/*!
 *  Elberos Forms
 *
 *  (c) Copyright 2019 "Ildar Bikmamatov" <support@elberos.org>
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

namespace Elberos\Forms;


if ( !class_exists( 'Elberos_Forms_Api' ) ) 
{

class Api
{
	
	/**
	 * Api submit form
	 */
	public function reload_amocrm_settings($arr)
	{
		global $wpdb;
		
		$integration_id = isset($_POST['integration_id']) ? $_POST['integration_id'] : 0;
		$message = "success";
		$success = true;
		
		$config = AmoCRMHelper::getConfig($wpdb, $integration_id);
		if ($config == null)
		{
			return [ "success" => false, "integration_id" => $integration_id, "message"=>"AmoCRM config error" ];
		}
		$auth = AmoCRMHelper::auth($config);
		if (!$auth)
		{
			return [ "success" => false, "integration_id" => $integration_id, "message"=>"Auth Error" ];
		}
		AmoCRMHelper::syncData($wpdb, $config);
		
		return 
		[
			"success" => true,
			"integration_id" => $integration_id,
			"message" => $message,
		];
	}
	
	
	
	/**
	 * Api submit form
	 */
	public function submit_form($arr)
	{
		
	}
	
}

}