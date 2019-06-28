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

if ( !class_exists( FormsIntegrations::class ) ) 
{
	
	
class FormsIntegrations
{

static $forms_integrations = [];
static $form_settings = [];


public static function loadAmoCRMPipelines($integration_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'elberos_forms_integrations_amocrm_pipelines';
	$q = $wpdb->prepare(
		"SELECT * FROM $table_name where is_deleted = 0 and integration_id = %d", (int)$integration_id
	);
	$items = $wpdb->get_results(
		$q, 
		ARRAY_A
	);
	
	$amocrm_pipelines = [];
	$amocrm_statuses = [];
	foreach ($items as $item)
	{
		$status_arr = [];
		$statuses = @json_decode($item['statuses'], true);
		if ($statuses == null) $statuses = [];
		foreach ($statuses as $key => $status)
		{
			$status['status_id'] = $status['id'];
			$status['pipeline_id'] = $item['pipeline_id'];
			$status['pipeline_name'] = $item['name'];
			$amocrm_statuses[] = $status;
			$status_arr[] = $status;
		}
		
		$amocrm_pipelines[] = 
		[		
			'pipeline_id' => $item['pipeline_id'],
			'name' => $item['name'],
			'sort' => $item['sort'],
			'is_main' => $item['is_main'],
			'statuses' => $status_arr,
		];
	}
	
	return [$amocrm_pipelines, $amocrm_statuses];
}


public static function loadAmoCRMManagers($integration_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'elberos_forms_integrations_amocrm_users';
	$items = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name where is_deleted = 0 and integration_id = %d", $integration_id
		), 
		ARRAY_A
	);
	
	$res = [];
	foreach ($items as $item)
	{
		$res[] = 
		[		
			'user_id' => $item['user_id'],
			'name' => $item['name'],
			'last_name' => $item['last_name'],
			'login' => $item['login'],
		];
	}
	
	return $res;
}


public static function loadAmoCRMFields($integration_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'elberos_forms_integrations_amocrm_fields';
	$items = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name where is_deleted = 0 and integration_id = %d", $integration_id
		), 
		ARRAY_A
	);
	
	$res = [];
	foreach ($items as $item)
	{
		$res[] = 
		[		
			'field_id' => $item['field_id'],
			'db' => $item['db'],
			'name' => $item['name'],
			'field_type' => $item['field_type'],
			'sort' => $item['sort'],
		];
	}
	
	return $res;
}


public static function loadFormSettings()
{
	global $wpdb;
	$form_id = $_GET['id'];
	$table_name = $wpdb->prefix . 'elberos_forms';
	static::$form_settings = $wpdb->get_row(
		$wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $form_id), ARRAY_A
	);
}


public static function loadIntegrations()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'elberos_forms_integrations';
	static::$forms_integrations = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name", []
		), 
		ARRAY_A
	);	
}



public static function getIntegrationById($integration_id)
{
	foreach (static::$forms_integrations as $forms_integration)
	{
		if ($forms_integration['id'] == $integration_id)
		{
			return $forms_integration;
		}
	}
	return null;
}



public static function showIntegrations()
{
	static::loadFormSettings();
	static::loadIntegrations();
	
	if (static::$form_settings == null)
	{
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2><?php _e('Forms Integrations', 'elberos-forms')?></h2>
			<a type="button" class='button-primary'  href='?page=elberos-forms'> Back </a> </a>
		</div>
		<?php
	}
?>

<style>
	.forms_integrations{
		padding-top: 20px;
	}
	.forms_integrations_left, .forms_integrations_right{
		display: inline-block;
		width: calc(50% - 10px);
		vertical-align: top;
		padding: 10px;
		box-sizing: border-box;
	}
	.forms_integration_item{
		display: block;
		margin: 5px 0px;
		padding: 6px 12px;
		background: #fff;
		box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
		cursor: pointer;
		text-decoration: none;
	}
	.forms_integrations_right h2
	{
		margin-top: 0px;
	}
	.amocrm_field_item{
		box-sizing: border-box;
		display: inline-block;
		vertical-align: middle;
		padding: 10px;
	}
	.amocrm_field_name {
		width: calc(33% - 20px);
		text-align: right;
	}
	.amocrm_field_value {
		width: calc(66% - 30px);
	}
	.amocrm_field_value select, .amocrm_field_value input
	{
		width: 100%;
	}
</style>

<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2><?php _e('Forms Integrations', 'elberos-forms'); echo " " . static::$form_settings['name']; ?></h2>
	<a type="button" class='button-primary'  href='?page=elberos-forms'> Back </a> </a>
	
	<div class='forms_integrations'>
		<div class='forms_integrations_left'>
			<?php echo static::showIntegrationsList(); ?>
		</div>
		<div class='forms_integrations_right'>
			<?php echo static::showIntegrationsSettings(); ?>
		</div>
	</div>
	
</div>


<?php
}



public static function showIntegrationsList()
{
	$form_id = $_GET['id'];
	$url = "?page=elberos-forms&action=integrations&id=" . $form_id;
	foreach (static::$forms_integrations as $forms_integration)
	{
		?>
		<a class='forms_integration_item' href='<?= $url . "&integration=" . $forms_integration['id'] ?>'>
			<?php echo $forms_integration['name'] ?>
		</a>
		<?php
	}
}


public static function showIntegrationsSettings()
{
	$nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : false;
	$integration_id = $_GET['integration'];
	$forms_integration = static::getIntegrationById($integration_id);
	if ($forms_integration == null)
	{
		return "";
	}

	list($amocrm_pipelines, $amocrm_statuses) = static::loadAmoCRMPipelines($integration_id);
	$amocrm_managers = static::loadAmoCRMManagers($integration_id);
	$amocrm_fields = static::loadAmoCRMFields($integration_id);
	
	//var_dump($amocrm_statuses);
	
	$item = [
		'pipeline_id' => '',
		'status_id' => '',
		'manager_id' => '',
		'tags' => '',
		'fields' => '',
	];
	
	$notice = "";
	$message = "";	
	if ($nonce != false)
	{
		$message = "Ok";
	}
	
	
?>
<h2><?php echo $forms_integration['name']; ?></h2>

<?php if (!empty($notice)): ?>
	<div id="notice" class="error"><p><?php echo $notice ?></p></div>
<?php endif;?>
<?php if (!empty($message)): ?>
	<div id="message" class="updated"><p><?php echo $message ?></p></div>
<?php endif;?>

<form id="form" method="POST">

<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>

<!-- Pipeline -->
<p>
	<label for="pipeline">Pipeline:</label>
	<br>
	<select id="pipeline" name="pipeline" type="text" style="width: 100%">
		<option value="">Select value</option>
		<?
			foreach ($amocrm_pipelines as $pipeline)
			{
				?>
				<option value="<?= esc_attr($pipeline['pipeline_id']) ?>" 
					<?php selected( $item['pipeline_id'], $pipeline['pipeline_id'] ); ?>> 
						<?= esc_html($pipeline['name']) ?> 
				</option>
				<?php
			}
		?>
	</select>
</p>


<!-- Status -->
<p>
	<label for="status">Status:</label>
	<br>
	<select id="status" name="status" type="text" style="width: 100%">
		<option value="">Select value</option>
		<?
			foreach ($amocrm_statuses as $status)
			{
				?>
				<option value="<?= esc_attr($status['status_id']) ?>" 
					data-pipeline-id="<?= esc_attr($status['pipeline_id']) ?>"
					<?php selected( $status['status_id'], $item['status_id'] ); ?>> 
						<?= esc_html($status['name']) ?> (<?= esc_html($status['pipeline_name']) ?>)
				</option>
				<?php
			}
		?>
	</select>
</p>


<!-- Manager -->
<p>
	<label for="manager">Manager:</label>
	<br>
	<select id="manager" name="manager" type="text" style="width: 100%">
		<option value="">Select value</option>
		<?
			foreach ($amocrm_managers as $manager)
			{
				?>
				<option value="<?= esc_attr($manager['user_id']) ?>" 
					<?php selected( $item['manager_id'], $manager['user_id'] ); ?>> 
						<?= esc_html($manager['name']) ?> (<?= esc_html($manager['login']) ?>)
				</option>
				<?php
			}
		?>
	</select>
</p>


<!-- Tags -->
<p>
	<label for="tags">Tags:</label>
	<br>
	<input id="tags" name="tags" type="text" value="<?= esc_attr( $item['tags'] )?>" style="width: 100%" />
</p>


<!-- Fields -->
<p>
	<label for="tags">Fields:</label>
	<br>
	<!-- Field name -->
	<div class="amocrm_field">
		<div class="amocrm_field_item amocrm_field_name">Name</div>
		<div class="amocrm_field_item amocrm_field_value">
			<select name="fields[client_name]">
				<option value="">Не указано</option>
				<? static::displaySelectAmoCRMFormFields($item, static::$form_settings) ?>
			</select>
		</div>
	</div>
	
	
	<!-- Field phone -->
	<div class="amocrm_field">
		<div class="amocrm_field_item amocrm_field_name">Phone</div>
		<div class="amocrm_field_item amocrm_field_value">
			<select name="fields[client_phone]">
				<option value="">Не указано</option>
				<? static::displaySelectAmoCRMFormFields($item, static::$form_settings) ?>
			</select>
		</div>
	</div>
	
	
	<!-- Field email -->
	<div class="amocrm_field">
		<div class="amocrm_field_item amocrm_field_name">Email</div>
		<div class="amocrm_field_item amocrm_field_value">
			<select name="fields[client_email]">
				<option value="">Не указано</option>
				<? static::displaySelectAmoCRMFormFields($item, static::$form_settings) ?>
			</select>
		</div>
	</div>
	
</p>


<!-- Additional Fields -->
<p>
	<label for="tags">Additional Fields:</label>
	<br>
</p>

<input type="submit" id="submit" class="button-primary" name="submit"
	value="<?php _e('Save', 'elberos-forms')?>" >

</form>
<?php
}



public static function displaySelectAmoCRMFormFields($item, $form_settings)
{
	$settings = json_decode($form_settings['settings'], true);
	if (!isset($settings['fields']))
	{
		return;
	}
	
	foreach ($settings['fields'] as $field)
	{
		?>
		<option value="<?= esc_attr($field['name']) ?>" >
			<?= esc_html($field['title']) ?> (<?= esc_html($field['name']) ?>)
		</option>
		<?php
	}
}


public static function displaySelectAmoCRMFields($item, $amocrm_fields)
{
	foreach ($amocrm_fields as $field)
	{
		?>
		<option value="<?= esc_attr($field['user_id']) ?>" >
			<?= esc_html($field['name']) ?> (<?= esc_html($field['db']) ?>)
		</option>
		<?php
	}
	
}




}
	
	
}