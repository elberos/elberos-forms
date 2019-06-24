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


if ( !class_exists( Integrations::class ) ) 
{

class Integrations
{
	public static function show()
	{
		$table = new Integrations_Table();
		$table->display();		
	}
}


class Integrations_Table extends \WP_List_Table 
{
	
	const KIND_AMOCRM = "amocrm";
	
	function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'elberos-forms-integrations',
            'plural' => 'elberos-forms-integrations',
        ));
    }
	
	function get_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'elberos_forms_integrations';
	}
	
	// Вывод значений по умолчанию
	function get_default()
	{
		return array(
			'id' => 0,
			'name' => '',
			'kind' => '',
		);
	}
	
	// Валидация значений
	function item_validate($item)
	{
		return true;
	}
	
	// Колонки таблицы
	function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', 
            'name' => __('Name', 'elberos-forms'),
            'kind' => __('Kind', 'elberos-forms'),
            'buttons' => __('', 'elberos-forms'),
        );
        return $columns;
    }
	
	// Сортируемые колонки
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', true),
            'api_name' => array('api_name', true),
        );
        return $sortable_columns;
    }
	
	// Действия
	function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }
	
	// Вывод каждой ячейки таблицы
	function column_default($item, $column_name)
    {
        return isset($item[$column_name])?$item[$column_name]:'';
    }
	
	// Заполнение колонки cb
	function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }
	
	// Колонка name
	function column_buttons($item)
	{
		$actions = array(
			'edit' => sprintf(
				'<a href="?page=elberos-forms-integrations&action=edit&id=%s">%s</a>',
				$item['id'], 
				__('Edit', 'elberos-forms')
			),
		);
		
		return $this->row_actions($actions, true);
	}
	
	// Создает элементы таблицы
    function prepare_items()
    {
        global $wpdb;
        $table_name = $this->get_table_name();
		
        $per_page = 10; 

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
       
        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);


        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
	
	
    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $this->get_table_name();

        if ($this->current_action() == 'delete')
		{
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }
	
	function display_add_or_edit()
	{
		global $wpdb;
		
		$action = $this->current_action();
		$table_name = $this->get_table_name();
		$message = "";
		$notice = "";
		$nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : false;
		$item = [];
		$default = $this->get_default();
		
		if ($nonce != false)
		{
			$item = shortcode_atts($default, $_REQUEST);
			$item_valid = $this->item_validate($item);
			$item['amocrm'] = json_encode($_REQUEST['amocrm']);
			if ($item_valid === true)
			{
				if ($item['id'] == 0)
				{
					$result = $wpdb->insert($table_name, $item);
					$item['id'] = $wpdb->insert_id;
					if ($result)
					{
						$message = __('Item was successfully saved', 'elberos-forms');
					}
					else
					{
						$notice = __('There was an error while saving item', 'elberos-forms');
					}
				}
				else
				{
					$result = $wpdb->update($table_name, $item, array('id' => $item['id']));
					if ($result)
					{
						$message = __('Item was successfully updated', 'elberos-forms');
					}
					else
					{
						$notice = __('There was an error while updating item', 'elberos-forms');
					}
				}
			}
			else
			{
				$notice = $item_valid;
			}
		}
		else if (isset($_REQUEST['id']))
		{
			$item_id = (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
			$item = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $item_id), ARRAY_A
			);
			if (!$item)
			{
				$item = $default;
				$notice = __('Item not found', 'elberos-forms');
			}
		}
		else
		{
			$item = $default;
		}
		
		?>
		
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2><?php _e($item['id'] > 0 ? 'Forms Edit' : 'Forms Add', 'elberos-forms')?></h2>
			
			<?php if (!empty($notice)): ?>
				<div id="notice" class="error"><p><?php echo $notice ?></p></div>
			<?php endif;?>
			<?php if (!empty($message)): ?>
				<div id="message" class="updated"><p><?php echo $message ?></p></div>
			<?php endif;?>
			
			<form id="form" method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
				<input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>
				<div class="metabox-holder" id="poststuff">
					<div id="post-body">
						<div id="post-body-content">
							<div class="add_or_edit_form" style="width: 60%">
								<? $this->display_form($item) ?>
							</div>
							<input type="submit" id="submit" class="button-primary" name="submit"
								value="<?php _e('Save', 'elberos-forms')?>" >
						</div>
					</div>
				</div>
			</form>
		</div>
		
		<?php if ($item['id'] > 0): ?>
			<br/>
			<input type="button" class="button-secondary elberos_forms_amocrm_reload_settings_button" 
				value="Reload amocrm settings"></input>
			<div class="elberos_forms_amocrm_reload_settings"></div>
			
			<script>
				jQuery(function($){
					$('.elberos_forms_amocrm_reload_settings_button').click(function(){
						$('.elberos_forms_amocrm_reload_settings').html("Wait please");
						$.ajax({
							url: "/wp-json/elberos_forms/reload_amocrm_settings/",
							data: {
								"integration_id": <?= json_encode($item['id']) ?>,
							},
							dataType: 'json',
							method: 'post',
							
							cache: false,
							contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
							processData: true,
							
							success: function(data, textStatus, jqXHR)
							{
								if (data.success)
								{
									$('.elberos_forms_amocrm_reload_settings').html("Success");
								}
								else
								{
									$('.elberos_forms_amocrm_reload_settings').html(data.message);
								}
							},
							error: function(data)
							{
								$('.elberos_forms_amocrm_reload_settings').html(data);
							},
						});
					});
				});
			</script>
			
		<?php endif;?>
		
		<?php
	}
	
	function display_form($item)
	{
		$amocrm_settings = @json_decode(isset($item['amocrm']) ? $item['amocrm'] : "", true);
		if (!$amocrm_settings) $amocrm_settings = [];
		
		?>
		<!-- Name -->
		<p>			
		    <label for="name"><?php _e('Name:', 'elberos-forms')?></label>
		<br>	
            <input id="name" name="name" type="text" style="width: 100%" required
				value="<?php echo esc_attr($item['name'])?>" >
		</p>
		
		<!-- Kind -->
		<p>	
            <label for="kind"><?php _e('Kind:', 'elberos-forms')?></label>
		<br>
			<select id="kind" name="kind" type="text" style="width: 100%" required
				value="<?php echo esc_attr($item['kind'])?>" >
				<option value="" <?php selected( $item['kind'], "" ); ?>>Select value</option>
				<option value="amocrm" <?php selected( $item['kind'], "amocrm" ); ?>>AmoCRM</option>
			</select>
        </p>
		
		<!-- AmoCRM Settings -->
		<div class='elberos_forms_integrations_amocrm'>
			<p><b>AmoCRM Settings</b></p>
			
			<!-- AmoCRM Domain -->
			<p>
				<label for="amocrm_domain"><?php _e('AmoCRM Domain:', 'elberos-forms')?></label>
			<br>
				<input id="amocrm_domain" name="amocrm[domain]" type="text" style="width: 100%"
					value="<?php echo esc_attr($amocrm_settings['domain'])?>" >
			</p>
			
			<!-- AmoCRM Login -->
			<p>
				<label for="amocrm_login"><?php _e('AmoCRM Login:', 'elberos-forms')?></label>
			<br>
				<input id="amocrm_login" name="amocrm[login]" type="text" style="width: 100%"
					value="<?php echo esc_attr($amocrm_settings['login'])?>" >
			</p>
			
			<!-- AmoCRM API Key -->
			<p>
				<label for="amocrm_api"><?php _e('AmoCRM API Key:', 'elberos-forms')?></label>
			<br>
				<input id="amocrm_api" name="amocrm[api]" type="text" style="width: 100%"
					value="<?php echo esc_attr($amocrm_settings['api'])?>" >
			</p>
			
			<!-- AmoCRM WEB HOOK -->
			<p>
				<label for="amocrm_web_hook"><?php _e('AmoCRM WEB HOOK:', 'elberos-forms')?></label>
			<br>
				<input id="amocrm_web_hook" name="amocrm[web_hook]" type="text" style="width: 100%"
					value="<?php echo esc_attr($amocrm_settings['web_hook'])?>" >
			</p>
			
		</div>
		
		<?php
	}
	
	function display_table()
	{
		$this->prepare_items();
		$message = "";
		?>
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e('Forms', 'elberos-forms')?>
			<a href="<?php echo get_admin_url(get_current_blog_id(), 
				'admin.php?page=elberos-forms-integrations&action=add');?>"
				class="add-new-h2"
			>
				<?php _e('Add new', 'elberos-forms')?>
			</a>
		</h2>
		<?php echo $message; ?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title() ?></h2>

			<?php
			// выводим таблицу на экран где нужно
			echo '<form action="" method="POST">';
			parent::display();
			echo '</form>';
			?>

		</div>
		<?php
	}
	
	function display()
	{
		$action = $this->current_action();
		
		if ($action == 'add' or $action == 'edit')
		{
			$this->display_add_or_edit();
		}
		else
		{
			$this->display_table();
		}
	}
	
}

}