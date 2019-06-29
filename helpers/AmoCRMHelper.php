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


class AmoCRMHelper
{
	
	
	/**
	 * Returns config from db row
	 */
	public static function getConfig($wpdb, $integration_id)
	{
		$table_name = static::getIntegrationsTableName($wpdb);
		
		$items = $wpdb->get_results(	
			$wpdb->prepare(
				"select * from $table_name where id=%d", $integration_id
			),
			ARRAY_A,
			0
		);
		
		$item = isset($items[0]) ? $items[0] : null;
		return static::getConfigByItem($item);
	}
	
	
	
	/**
	 * Returns config from db row
	 */
	public static function getConfigByItem($item)
	{
		if ($item == null) return null;
		$integration_id = isset($item['id']) ? $item['id'] : 0;
		$amocrm = isset($item['amocrm']) ? $item['amocrm'] : "";
		$config = @json_decode($amocrm, true);
		if (!$config) return null;
		$config['integration_id'] = $integration_id;
		return $config;
	}
	
	
	
	/**
	 * Returns User Agent
	 */
	public static function getUserAgent()
	{
		return 'AmoCRM-API-client/1.0';
	}
	
	
	
	/**
	 * Returns amocrm domain
	 */
	public static function getAmoCRMDomain($config)
	{
		return $config['domain'] . ".amocrm.ru";
	}
	
	
	
	/**
	 * Returns AmoCRM API Auth Url
	 */
	public static function getAuthUrl($config)
	{
		return "https://" . static::getAmoCRMDomain($config) . '/private/api/auth.php?type=json';
	}
	
	
	
	/**
	 * Returns AmoCRM API Account Url
	 */
	public static function getAccountUrl($config, $fields = [])
	{
		$str = implode(",", $fields);
		return "https://" . static::getAmoCRMDomain($config) . '/api/v2/account?with='.$str;
	}
	
	
	
	/**
	 * Returns Users table
	 */
	public static function getUsersTableName($wpdb)
	{
		return $wpdb->prefix . 'elberos_forms_integrations_amocrm_users';
	}
	
	
	
	/**
	 * Returns Fields table
	 */
	public static function getFieldsTableName($wpdb)
	{
		return $wpdb->prefix . 'elberos_forms_integrations_amocrm_fields';
	}
	
	
	
	/**
	 * Returns Pipelines table
	 */
	public static function getPipelinesTableName($wpdb)
	{
		return $wpdb->prefix . 'elberos_forms_integrations_amocrm_pipelines';
	}
	
	
	
	/**
	 * Returns Integrations table
	 */
	public static function getIntegrationsTableName($wpdb)
	{
		return $wpdb->prefix . 'elberos_forms_integrations';
	}
	
	
	
	/**
	 * Send curl
	 */
	public static function curl($url, $post = null)
	{
		$arr = parse_url($url);
		$host = isset($arr['host']) ? $arr['host'] : "";
		$cookie_file = ABSPATH . "wp-content/cache/amocrm-".$host.".cookie";
		
		# Сохраняем дескриптор сеанса cURL
		$curl = curl_init();
		
		# Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, static::getUserAgent());
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file); # PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file); # PHP>5.3.6 dirname(__FILE__) -> __DIR__
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		
		if ($post != null)
		{
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
		}
		else
		{
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		}
		
		# Инициируем запрос к API и сохраняем ответ в переменную
		$out = curl_exec($curl);
		
		# Получим HTTP-код ответа сервера
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		# Завершаем сеанс cURL
		curl_close($curl); 
		
		$response = null;
		$code = (int)$code;
		if ($code == 200 || $code == 204)
		{
			$response = @json_decode($out, true);
		}
		
		return [$out, $code, $response];
	}
	
	
	
	/**
	 * Авторизация в AmoCRM
	 * https://www.amocrm.ru/developers/content/api/auth
	 */
	public static function auth($config, $fake = false)
	{
		$subdomain = $config['domain'];
		$login = $config['login'];
		$api_key = $config['api'];
		if ($subdomain == "" || $login == "" || $api_key == "")
		{
			return false;
		}
		
		# Подготовим запрос
		$url = static::getAuthUrl($config);
		$user = array(
			'USER_LOGIN' => $login,
			'USER_HASH' => $api_key
		);
		
		list($out, $code, $response) = static::curl($url, $user);
		if ($response != null)
		{
			$response = $response['response'];
			if (isset($response['auth']))
			{
				return true;
			}
		}
		
		return false;
	}
	
	
	
	/**
	 * Синхронизирует данные из AmoCRM
	 */
	public static function syncData($wpdb, $config)
	{
		$host = $config["domain"] . ".amocrm.ru";
		$file_cache = ABSPATH . "wp-content/cache/amocrm-".$host.".data.cache";
		$table_name_users = static::getUsersTableName($wpdb);
		$table_name_fields = static::getFieldsTableName($wpdb);
		$table_name_pipelines = static::getPipelinesTableName($wpdb);
		
		if (true)
		{
			$url = static::getAccountUrl($config, ['custom_fields', 'users', 'pipelines']);
			list($out, $code, $response) = static::curl($url);
			file_put_contents($file_cache, json_encode($response) );
		}
		else
		{
			$data = file_get_contents($file_cache);
			$response = json_decode($data, true);
		}
		
		if (!$response || !isset($response['_embedded']))
		{
			throw new \Exception('Data failed');
			return false;
		}
		$data = $response['_embedded'];
		
		$users = isset($data['users']) ? $data['users'] : [];
		$custom_fields = isset($data['custom_fields']) ? $data['custom_fields'] : [];
		$pipelines = isset($data['pipelines']) ? $data['pipelines'] : [];
		
		// Set is deleted
		$wpdb->query("update $table_name_users set is_deleted=1");
		$wpdb->query("update $table_name_fields set is_deleted=1");
		$wpdb->query("update $table_name_pipelines set is_deleted=1");
		
		// Update Users
		foreach ($users as $row)
		{
			$integration_id = $config['integration_id'];
			$user_id = isset($row['id']) ? $row['id'] : 0;
			$name = isset($row['name']) ? $row['name'] : "";
			$last_name = isset($row['last_name']) ? $row['last_name'] : "";
			$login = isset($row['login']) ? $row['login'] : "";
			$language = isset($row['language']) ? $row['language'] : "";
			$group_id = isset($row['group_id']) ? $row['group_id'] : 0;
			$is_active = isset($row['is_active']) ? $row['is_active'] : 0;
			$is_free = isset($row['is_free']) ? $row['is_free'] : 0;
			$is_admin = isset($row['is_admin']) ? $row['is_admin'] : 0;
			$is_deleted = 0;
			
			$q = $wpdb->prepare(
				"INSERT INTO $table_name_users 
					(
						integration_id, user_id, name, last_name, login, language,
						group_id, is_active, is_free, is_admin, is_deleted
					) 
					VALUES( %d, %d, %s, %s, %s, %s, %d, %d, %d, %d, %d ) 
					ON DUPLICATE KEY UPDATE
						name = %s,
						last_name = %s,
						login = %s,
						language = %s,
						group_id = %d,
						is_active = %d,
						is_free = %d,
						is_admin = %d,
						is_deleted = %d
					",
				[
					$integration_id,
					$user_id,
					$name, $last_name, $login, $language, $group_id, $is_active, $is_free, $is_admin, $is_deleted,
					$name, $last_name, $login, $language, $group_id, $is_active, $is_free, $is_admin, $is_deleted,
				]
			);
			//var_dump($q);
			$wpdb->query($q);
		}
		
		
		// Update Custom Fields
		foreach ($custom_fields as $db => $arr1)
		{
			foreach ($arr1 as $row)
			{
				$integration_id = $config['integration_id'];
				$field_id = isset($row['id']) ? $row['id'] : 0;
				$name = isset($row['name']) ? $row['name'] : "";
				$field_type = isset($row['field_type']) ? $row['field_type'] : 0;
				$sort = isset($row['sort']) ? $row['sort'] : 0;
				$enums = json_encode(isset($row['enums']) ? $row['enums'] : []);
				$is_multiple = isset($row['is_multiple']) ? $row['is_multiple'] : 0;
				$is_system = isset($row['is_system']) ? $row['is_system'] : 0;
				$is_editable = isset($row['is_editable']) ? $row['is_editable'] : 0;
				
				$q = $wpdb->prepare(
					"INSERT INTO $table_name_fields 
						(
							integration_id, field_id, db, name, field_type,
							sort, enums, is_multiple, is_system, is_editable, is_deleted
						) 
						VALUES( %d, %d, %s, %s, %d, %d, %s, %d, %d, %d, %d) 
						ON DUPLICATE KEY UPDATE
							db = %s,
							name = %s,
							field_type = %d,
							sort = %d,
							enums = %s,
							is_multiple = %d,
							is_system = %d,
							is_editable = %d,
							is_deleted = %d
						",
					[
						$integration_id,
						$field_id,
						$db, $name, $field_type, $sort, $enums, $is_multiple, $is_system, $is_editable, $is_deleted,
						$db, $name, $field_type, $sort, $enums, $is_multiple, $is_system, $is_editable, $is_deleted,
					]
				);
				//var_dump($q);
				$wpdb->query($q);
			}
		}
		
		// Update Pipelines
		foreach ($pipelines as $row)
		{
			$integration_id = $config['integration_id'];
			$pipeline_id = isset($row['id']) ? $row['id'] : 0;
			$name = isset($row['name']) ? $row['name'] : "";
			$sort = isset($row['sort']) ? $row['sort'] : 0;
			$is_main = isset($row['is_main']) ? $row['is_main'] : 0;
			$is_deleted = isset($row['is_deleted']) ? $row['is_deleted'] : 0;
			$statuses = json_encode(isset($row['statuses']) ? $row['statuses'] : []);
			
			$q = $wpdb->prepare(
				"INSERT INTO $table_name_pipelines 
					(
						integration_id, pipeline_id, name, sort,
						is_main, is_deleted, statuses
					) 
					VALUES( %d, %d, %s, %d, %d, %d, %s) 
					ON DUPLICATE KEY UPDATE
						name = %s,
						sort = %d,
						is_main = %d,
						is_deleted = %d,
						statuses = %s
					",
				[
					$integration_id,
					$pipeline_id,
					$name, $sort, $is_main, $is_deleted, $statuses,
					$name, $sort, $is_main, $is_deleted, $statuses,
				]
			);
			//var_dump($q);
			$wpdb->query($q);
			
		}
		
		return true;
	}
	
	
	
}