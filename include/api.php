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
	public function reload_amocrm_settings($params)
	{
		global $wpdb;
		
		$integration_id = isset($_POST['integration_id']) ? $_POST['integration_id'] : 0;		
		$config = AmoCRMHelper::getConfig($wpdb, $integration_id);
		if ($config == null)
		{
			return 
			[
				"success" => false,
				"integration_id" => $integration_id,
				"message"=>"AmoCRM config error",
				"code" => -1
			];
		}
		$auth = AmoCRMHelper::auth($config);
		if (!$auth)
		{
			return 
			[
				"success" => false,
				"integration_id" => $integration_id,
				"message"=>"Auth Error",
				"code" => -1
			];
		}
		AmoCRMHelper::syncData($wpdb, $config);
		
		return 
		[
			"success" => true,
			"integration_id" => $integration_id,
			"message" => "Success",
			"code" => 1,
		];
	}
	
	
	
	/**
	 * Get field by name
	 */
	public static function getFieldByName($fields, $field_name)
	{
		foreach ($fields as $field)
		{
			if ($field['name'] == $field_name)
			{
				return $field;
			}
		}
		return null;
	}
	
	
	
	/**
	 * Api submit form
	 */
	public function submit_form($params)
	{
		global $wpdb;
		
		$table_forms_name = $wpdb->prefix . 'elberos_forms';
		$table_forms_data_name = $wpdb->prefix . 'elberos_forms_data';
		$form_api_name = isset($_POST["form_api_name"]) ? $_POST["form_api_name"] : "";
		$forms_wp_nonce = isset($_POST["_wpnonce"]) ? $_POST["_wpnonce"] : "";
		$wp_nonce_res = (int)wp_verify_nonce($forms_wp_nonce, 'wp_rest');
		
		/* Check wp nonce */
		if ($wp_nonce_res == 0)
		{
			return 
			[
				"success" => false,
				"message" => __("Form error. Please reload page and send form again", "elberos-forms"),
				"fields" => [],
				"code" => -1,
			];
		}
		
		/* Find form */
		$forms = $wpdb->get_results(	
			$wpdb->prepare(
				"select * from $table_forms_name where api_name=%s", $form_api_name
			),
			ARRAY_A,
			0
		);
		$form = isset($forms[0]) ? $forms[0] : null;
		if ($form == null)
		{
			return 
			[
				"success" => false,
				"message" => "Form not found",
				"fields" => [],
				"code" => -1,
			];
		}
		
		$form_id = $form['id'];
		$form_settings = @json_decode($form['settings'], true);
		$form_settings_fields = isset($form_settings['fields']) ? $form_settings['fields'] : [];
		$form_data = [];
		$data = isset($_POST["data"]) ? $_POST["data"] : [];
		$utm = isset($_POST["utm"]) ? $_POST["utm"] : [];
		
		/* Validate fields */
		$fields = [];
		foreach ($data as $key => $value)
		{
			$field = static::getFieldByName($form_settings_fields, $key);
			if ($field == null)
			{
				continue;
			}
			
			$required = isset($field['required']) ? $field['required'] : false;
			if ($value == "" && $required)
			{
				$fields[$key][] = __("Empty fields", "elberos-forms");
			}
			
			$form_data[$key] = $value;
		}
		
		/* Add missing fields */
		foreach ($form_settings_fields as $field)
		{
			$key = isset($field['name']) ? $field['name'] : "";
			if ($key == null)
			{
				continue;
			}
			if (isset($data[$key]))
			{
				continue;
			}
			$required = isset($field['required']) ? $field['required'] : false;
			if ($required)
			{
				$fields[$key][] = __("Empty fields", "elberos-forms");
			}
			
			$form_data[$key] = "";
		}
		
		/* If validate fields error */
		if (count ($fields) > 0)
		{
			return 
			[
				"success" => false,
				"message" => __("Validate fields error", "elberos-forms"),
				"fields" => $fields,
				"code" => -1,
			];
		}
		
		/* Add UTM */
		$f_utm = isset($_COOKIE['f_utm']) ? $_COOKIE['f_utm'] : null;
		if ($f_utm) $f_utm = @json_decode( stripslashes($f_utm), true);
		if ($f_utm)
		{
			$utm['utm_source'] = isset($f_utm['s']) ? $f_utm['s'] : null;
			$utm['utm_medium'] = isset($f_utm['m']) ? $f_utm['m'] : null;
			$utm['utm_campaign'] = isset($f_utm['cmp']) ? $f_utm['cmp'] : null;
			$utm['utm_content'] = isset($f_utm['cnt']) ? $f_utm['cnt'] : null;
			$utm['utm_term'] = isset($f_utm['t']) ? $f_utm['t'] : null;
		}
		
		
		/* Insert data */
		$data_s = json_encode($form_data);
		$utm_s = json_encode($utm);
		
		$q = $wpdb->prepare(
			"INSERT INTO $table_forms_data_name
				(
					form_id, data, utm
				) 
				VALUES( %d, %s, %s )",
			[
				$form_id, $data_s, $utm_s
			]
		);
		$wpdb->query($q);
		
		return
		[
			"success" => true,
			"message" => "Ok",
			"fields" => [],
			"code" => 1,
		];
	}
	
}

}