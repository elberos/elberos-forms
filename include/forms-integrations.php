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
			'enums' => json_decode($item['enums'], true),
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
	$form_id = $_GET['id'];
	$table_name = $wpdb->prefix . 'elberos_forms_integrations';
	$table_name_settings = $wpdb->prefix . 'elberos_forms_integrations_settings';
	$q = $wpdb->prepare(
		"SELECT t.*, s.enable FROM $table_name as t
			LEFT JOIN $table_name_settings as s on (t.id = s.integration_id and s.form_id = %d)
		",
		[$form_id]
	);
	static::$forms_integrations = $wpdb->get_results($q, ARRAY_A);
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
		box-sizing: border-box;
		display: inline-block;
		width: calc(50% - 10px);
		vertical-align: top;
		padding: 10px;
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
	.amocrm_additional_field{
	}
	.amocrm_additional_field_item{
		box-sizing: border-box;
		display: inline-block;
		vertical-align: middle;
		padding: 0px 5px;
	}
	.amocrm_additional_field_name{
		width: calc(33% - 20px);
	}
	.amocrm_additional_field_value1{
		width: calc(33% - 20px);
	}
	.amocrm_additional_field_value2{
		width: calc(33% - 20px);
	}
	.amocrm_additional_field_remove{
		width: 50px;
	}
	.amocrm_add_field_div{
		padding-top: 20px;
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
		$enable = $forms_integration['enable'];
		if ($enable == 1) $enable = 'ON'; else $enable = 'OFF';
		?>
		<a class='forms_integration_item' href='<?= $url . "&integration=" . $forms_integration['id'] ?>'>
			<?php echo $forms_integration['name'] ?> [<?= $enable ?>]
		</a>
		<?php
	}
}


public static function showIntegrationsSettings()
{
	global $wpdb;
	
	$nonce = isset($_POST['nonce']) ? $_POST['nonce'] : false;
	$form_id = $_GET['id'];
	$integration_id = $_GET['integration'];
	$forms_integration = static::getIntegrationById($integration_id);
	if ($forms_integration == null)
	{
		return "";
	}

	list($amocrm_pipelines, $amocrm_statuses) = static::loadAmoCRMPipelines($integration_id);
	$amocrm_managers = static::loadAmoCRMManagers($integration_id);
	$amocrm_fields = static::loadAmoCRMFields($integration_id);
	
	$item_enable = "0";
	$item = [
		'amocrm_pipeline' => '',
		'amocrm_status' => '',
		'amocrm_manager' => '',
		'amocrm_tags' => '',
		'amocrm_fields' => [],
		'amocrm_fields_additional' => [],
	];
	
	$notice = "";
	$message = "";	
	if ($nonce != false)
	{
		$message = "Ok";
		
		$item_enable = (int)(isset($_POST['enable']) ? $_POST['enable'] : "0");
		$item = [
			'amocrm_pipeline' => isset($_POST['amocrm_pipeline']) ? $_POST['amocrm_pipeline'] : '',
			'amocrm_status' => isset($_POST['amocrm_status']) ? $_POST['amocrm_status'] : '',
			'amocrm_manager' => isset($_POST['amocrm_manager']) ? $_POST['amocrm_manager'] : '',
			'amocrm_tags' => isset($_POST['amocrm_tags']) ? $_POST['amocrm_tags'] : '',
			'amocrm_fields' => isset($_POST['amocrm_fields']) ? $_POST['amocrm_fields'] : [],
			'amocrm_fields_additional' => isset($_POST['amocrm_fields_additional']) ? 
				$_POST['amocrm_fields_additional'] : [],
		];
		$item['amocrm_fields_additional'] = array_values($item['amocrm_fields_additional']);
		$item_settings = json_encode($item);
		$table_name_settings = $wpdb->prefix . 'elberos_forms_integrations_settings';
		$q = $wpdb->prepare(
			"INSERT INTO $table_name_settings 
				(
					form_id, integration_id, enable, settings
				) 
				VALUES( %d, %d, %d, %s) 
				ON DUPLICATE KEY UPDATE
					enable = %d,
					settings = %s
				",
			[
				$form_id, $integration_id, 
				$item_enable, $item_settings,
				$item_enable, $item_settings,
			]
		);
		$wpdb->query($q);
	}
	else
	{
		$table_name_settings = $wpdb->prefix . 'elberos_forms_integrations_settings';
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name_settings WHERE form_id = %d and integration_id = %d", 
				[
					$form_id, $integration_id
				]
			),
			ARRAY_A
		);
		if ($row)
		{
			$item_enable = $row['enable'];
			$item_settings = $row['settings'];
			$item = json_decode($item_settings, true);
		}
	}
	
	
?>
<h2><?php echo $forms_integration['name']; ?></h2>

<?php if (!empty($notice)): ?>
	<div class="error"><p><?php echo $notice ?></p></div>
<?php endif;?>
<?php if (!empty($message)): ?>
	<div class="updated"><p><?php echo $message ?></p></div>
<?php endif;?>


<script>
var form_settings = <?= static::$form_settings['settings'] ?>;
var amocrm_pipelines = <?= json_encode($amocrm_pipelines) ?>;
var amocrm_statuses = <?= json_encode($amocrm_statuses) ?>;
var amocrm_managers = <?= json_encode($amocrm_managers) ?>;
var amocrm_fields = <?= json_encode($amocrm_fields) ?>;
var amocrm_item = <?= json_encode($item) ?>;
</script>


<form id="form" method="POST">

<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>


<!-- enable -->
<p>
	<label for="enable"><?php _e('Mail Enable:', 'elberos-forms')?></label>
<br>
	<select id="enable" name="enable" type="text" style="width: 100%"
		value="<?php echo esc_attr($item_enable)?>" >
		<option value="1" <?php selected( $item_enable, "1" ); ?>>Yes</option>
		<option value="0" <?php selected( $item_enable, "0" ); ?>>No</option>
	</select>
</p>


<!-- Pipeline -->
<p>
	<label for="amocrm_pipeline">Pipeline:</label>
	<br>
	<select id="amocrm_pipeline" name="amocrm_pipeline" class='amocrm_pipeline' type="text" style="width: 100%">
		<option value="">Select value</option>
		<?
			foreach ($amocrm_pipelines as $pipeline)
			{
				?>
				<option value="<?= esc_attr($pipeline['pipeline_id']) ?>" 
					<?php selected( $item['amocrm_pipeline'], $pipeline['pipeline_id'] ); ?>> 
						<?= esc_html($pipeline['name']) ?> 
				</option>
				<?php
			}
		?>
	</select>
</p>


<!-- Status -->
<p>
	<label for="amocrm_status">Status:</label>
	<br>
	<select id="amocrm_status" name="amocrm_status" class='amocrm_status' type="text" style="width: 100%">
		<option value="">Select value</option>
		<?
			foreach ($amocrm_statuses as $status)
			{
				if ($status['pipeline_id'] == $item['amocrm_pipeline'])
				{
				?>
				<option value="<?= esc_attr($status['status_id']) ?>" 
					data-pipeline-id="<?= esc_attr($status['pipeline_id']) ?>"
					<?php selected( $status['status_id'], $item['amocrm_status'] ); ?>> 
						<?= esc_html($status['name']) ?> (<?= esc_html($status['pipeline_name']) ?>)
				</option>
				<?php
				}
			}
		?>
	</select>
</p>


<script type='text/javascript'>
jQuery(document).ready(function(){
    jQuery('.amocrm_pipeline').change(function(){
		jQuery('.amocrm_status').html('');
		jQuery('.amocrm_status').append('<option value="">Select value</option>');
		var pipeline_id = jQuery('.amocrm_pipeline').val();
		for (var i in amocrm_statuses)
		{
			var status = amocrm_statuses[i];
			var status_id = status.status_id;
			if (status.pipeline_id == pipeline_id)
			{
				var $option = jQuery('<option></option>');
				$option.attr('value', status_id);
				$option.html(status.name + ' (' + status.pipeline_name + ')');
				jQuery('.amocrm_status').append($option);
			}
		}
	})
});
</script>


<!-- Manager -->
<p>
	<label for="amocrm_manager">Manager:</label>
	<br>
	<select id="amocrm_manager" name="amocrm_manager" class='amocrm_manager' type="text" style="width: 100%">
		<option value="">Select value</option>
		<?
			foreach ($amocrm_managers as $manager)
			{
				?>
				<option value="<?= esc_attr($manager['user_id']) ?>" 
					<?php selected( $item['amocrm_manager'], $manager['user_id'] ); ?>> 
						<?= esc_html($manager['name']) ?> (<?= esc_html($manager['login']) ?>)
				</option>
				<?php
			}
		?>
	</select>
</p>


<!-- Tags -->
<p>
	<label for="amocrm_tags">Tags:</label>
	<br>
	<input id="amocrm_tags" name="amocrm_tags" type="text" value="<?= esc_attr( $item['amocrm_tags'] )?>" 
		style="width: 100%" />
</p>


<!-- Fields -->
<p>
	<label for="tags">Fields:</label>
	<br>
	<!-- Field name -->
	<div class="amocrm_field">
		<div class="amocrm_field_item amocrm_field_name">Name</div>
		<div class="amocrm_field_item amocrm_field_value">
			<select name="amocrm_fields[client_name]">
				<option value="">Не указано</option>
				<? static::displaySelectAmoCRMFormFields($item, static::$form_settings, 'client_name') ?>
			</select>
		</div>
	</div>
	
	
	<!-- Field phone -->
	<div class="amocrm_field">
		<div class="amocrm_field_item amocrm_field_name">Phone</div>
		<div class="amocrm_field_item amocrm_field_value">
			<select name="amocrm_fields[client_phone]">
				<option value="">Не указано</option>
				<? static::displaySelectAmoCRMFormFields($item, static::$form_settings, 'client_phone') ?>
			</select>
		</div>
	</div>
	
	
	<!-- Field email -->
	<div class="amocrm_field">
		<div class="amocrm_field_item amocrm_field_name">Email</div>
		<div class="amocrm_field_item amocrm_field_value">
			<select name="amocrm_fields[client_email]">
				<option value="">Не указано</option>
				<? static::displaySelectAmoCRMFormFields($item, static::$form_settings, 'client_email') ?>
			</select>
		</div>
	</div>
	
</p>


<!-- Additional Fields -->
<p>
	<label for="tags">Additional Fields:</label>
	<br>
	<?php static::displayAmoCRMAdditionalFields() ?>
</p>

<input type="submit" id="submit" class="button-primary" name="submit"
	value="<?php _e('Save', 'elberos-forms')?>" >

</form>
<?php
}



public static function displayAmoCRMAdditionalFields()
{
?>

<div class='amocrm_additional_fields'>
</div>

<script>

function amocrm_find_field(field_id)
{
	for (var i in amocrm_fields)
	{
		var field = amocrm_fields[i];
		if (field.field_id == field_id)
		{
			return field;
		}
	}
	return null;
}
	
function amocrm_find_additional_field(field_id)
{
	var amocrm_fields_additional = amocrm_item.amocrm_fields_additional;
	if (amocrm_fields_additional == undefined) amocrm_fields_additional = [];
	for (var i in amocrm_fields_additional)
	{
		var field = amocrm_fields_additional[i];
		if (field['field_id'] == field_id)
		{
			return field;
		}
	}
	return null;
}
	
	
function render_additional_fields()
{
	jQuery('.amocrm_additional_fields').html('');
	
	
	// Rend field list
	var amocrm_fields_additional = amocrm_item.amocrm_fields_additional;
	if (amocrm_fields_additional == undefined) amocrm_fields_additional = [];
	for (var amocrm_fields_additional_key in amocrm_fields_additional)
	{
		var field_item = amocrm_fields_additional[amocrm_fields_additional_key];
		var field_id = field_item.field_id;
		var field = amocrm_find_field(field_id);
		if (!field)
		{
			continue ;
		}
		var field_value1 = field_item.field_value1;
		var field_value2 = field_item.field_value2;
		
		var $div_field = jQuery('<div class="amocrm_additional_field"></div>');
		$div_field.attr('id', field_id);
		
		
		// Add hidden input
		var $input = jQuery('<input></input>');
		$input.attr('type', 'hidden');
		$input.attr('name', 'amocrm_fields_additional['+field_id+'][field_id]');
		$input.val(field_id);
		$div_field.append($input);
		
		
		
		// Add field name
		var $div_name = jQuery('<div class="amocrm_additional_field_item amocrm_additional_field_name"></div>');
		$div_name.html(field.name + '(' + field.db + ')');
		$div_field.append($div_name);
		
		
		
		// Add field values
		var $div_value1 = jQuery('<div class="amocrm_additional_field_item amocrm_additional_field_value1"></div>');
		var $select = jQuery('<select style="width: 100%"></select>');
		$select.attr("data-field-id", field_id);
		$select.attr('name', 'amocrm_fields_additional['+field_id+'][field_value1]');
		$select.append('<option value="">Select value</option>');
		for (var j in form_settings.fields)
		{
			var val = form_settings.fields[j];
			var $option = jQuery('<option></option>');
			if (val == field_value1) $option.attr('selected', 'selected');
			$option.attr('value', val.name);
			$option.html(val.name + '(' + val.title + ')');
			$select.append($option);
		}
		$div_value1.append($select);
		$div_field.append($div_value1);
		
		
		
		// Add field list
		var $div_value2 = jQuery('<div class="amocrm_additional_field_item amocrm_additional_field_value2"></div>');
		if (field.field_type == 4) // Amocrm Select Type
		{
			var $select = jQuery('<select style="width: 100%"></select>');
			$select.attr("data-field-id", field_id);
			$select.attr('name', 'amocrm_fields_additional['+field_id+'][field_value2]');
			$select.append('<option value="">Select value</option>');
			for (var item_enum_key in field.enums)
			{
				var item_enum_value = field.enums[item_enum_key];
				var $option = jQuery('<option></option>');
				if (item_enum_key == field_value2) $option.attr('selected', 'selected');
				$option.attr('value', item_enum_key);
				$option.html(item_enum_value);
				$select.append($option);
			}
			$div_value2.append($select);
		}
		else // Other
		{
		}
		$div_field.append($div_value2);
		
		
		// Remove button
		var $div_remove = jQuery('<div class="amocrm_additional_field_item amocrm_additional_field_remove"></div>');
		var $button = jQuery("<button type='button' class='amocrm_additional_field_remove_button'>[Del]</button>");
		$button.attr("data-field-id", field_id);
		$div_remove.append($button);
		$div_field.append($div_remove);
		
		
		jQuery('.amocrm_additional_fields').append($div_field);
	}
	
	
	// Render add field select
	var $div_add = jQuery('<div class="amocrm_add_field_div"></div>');
	var $add_fields = jQuery('<select class="amocrm_add_field_select"></select>');
	$add_fields.append('<option value="">Add field</option>');
	for (var i in amocrm_fields)
	{
		var field = amocrm_fields[i];
		if (!amocrm_find_additional_field(field.field_id))
		{
			var $option = jQuery('<option></option>');
			$option.attr('value', field.field_id);
			$option.html(field.name + ' ('+ field.db +')');
			$add_fields.append($option);
		}
	}
	$div_add.append($add_fields);
	jQuery('.amocrm_additional_fields').append($div_add);
}

jQuery(document).ready(function(){
	
	render_additional_fields();
	
	
	// Change value1
	jQuery(document).on(
		'change',
		'.amocrm_additional_field_value1 select', 
		function()
		{
			var value = jQuery(this).val();
			var field_id = jQuery(this).attr("data-field-id");
			for (var i in amocrm_item.amocrm_fields_additional)
			{
				var field = amocrm_item.amocrm_fields_additional[i];
				if (field['field_id'] == field_id)
				{
					amocrm_item.amocrm_fields_additional[i]['field_value1'] = value;
					return;
				}
			}
		}
	);
	
	
	// Change value2
	jQuery(document).on(
		'change',
		'.amocrm_additional_field_value2 select', 
		function()
		{
			var value = jQuery(this).val();
			var field_id = jQuery(this).attr("data-field-id");
			for (var i in amocrm_item.amocrm_item.amocrm_fields_additional)
			{
				var field = amocrm_item.amocrm_fields_additional[i];
				if (field['field_id'] == field_id)
				{
					amocrm_item.amocrm_fields_additional[i]['field_value2'] = value;
					return;
				}
			}
		}
	);
	
	
	// Remove value
	jQuery(document).on(
		'click',
		'.amocrm_additional_field_remove_button', 
		function()
		{
			var field_id = jQuery(this).attr("data-field-id");
			for (var i in amocrm_item.amocrm_fields_additional)
			{
				var field = amocrm_item.amocrm_fields_additional[i];
				if (field['field_id'] == field_id)
				{
					amocrm_item.amocrm_fields_additional.splice(i, 1);
					break;
				}
			}
			
			render_additional_fields();
		}
	);
	
	
	// Add field
	jQuery(document).on(
		'change',
		'.amocrm_add_field_select', 
		function()
		{
			if (amocrm_item.amocrm_fields_additional == undefined) amocrm_item.amocrm_fields_additional = [];
			var field_id = jQuery(this).val();
			var field = amocrm_find_field(field_id);
			if (!field)
			{
				return ;
			}
			if (amocrm_find_additional_field(field_id))
			{
				return;
			}
			
			amocrm_item.amocrm_fields_additional.push({
				'field_id': field.field_id,
				'field_value1': '',
				'field_value2': '',
			});
			
			render_additional_fields();
		}
	);
	
});
</script>

<?php
}





public static function displaySelectAmoCRMFormFields($item, $form_settings, $key)
{
	$settings = json_decode($form_settings['settings'], true);
	if (!isset($settings['fields']))
	{
		return;
	}
	
	$fields = isset($item['amocrm_fields']) ? $item['amocrm_fields'] : [];
	$value = isset($fields[$key]) ? $fields[$key] : '';
	
	foreach ($settings['fields'] as $field)
	{
		?>
		<option value="<?= esc_attr($field['name']) ?>" 
			<?php selected( $value, $field['name'] ); ?> >
			<?= esc_html($field['title']) ?> (<?= esc_html($field['name']) ?>)
		</option>
		<?php
	}
}




}
	
	
}