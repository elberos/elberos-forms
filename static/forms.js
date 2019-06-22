"use strict";

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


/**
 * Constructor ElberosWebComponent
 */ 
function ElberosWebComponent($el){
	if (typeof $el == "undefined")
		$el = null;
	
	if ($el != null)
		this.setElem($el);
	
	this.events = {};
}


Object.assign( ElberosWebComponent.prototype, {
	
	getId: function(){
		return this.$el.attr('id');
	},
	
	isset: function(a){ 
		return (a != null) && ((typeof a) != 'undefined'); 
	},
	
	setElem: function($el){
		this.$el = $el;
		
		// Set controller
		this.$el.get(0).controller = this;
	},
	
	
	getElem: function($el){
		return this.$el;
	},
	
	
	/**
	 * Init
	 */
	init: function(params){
		
		if (isset(params)){
			for (var key in params){
				this[key] = params[key];
			}
		}
		
	},
	
	
	
	/**
	 * Get component data
	 */
	getData: function(){
		return null;
	},
	
	
	/**
	 * Set component data
	 */
	setData: function(value){
	},
	
	
	/**
	 * Subscribe on form event
	 */
	subscribe: function(event, callback){
		
		if (!isset(this.events[event])){
			this.events[event] = new Array();
		}
		
		this.events[event].push(callback);
	},
	
	
	
	/**
	 * Send form event
	 */
	sendEvent: function(event, data){
		if (!isset(data)) data = null;
		
		var res = null;
		if (isset(this.events[event])){
			var events = this.events[event];
			for (var i=0; i<events.length; i++){
				res = events[i](event, data);
			}
		}
		
		return res;
	},
	
});



/**
 * Constructor ElberosWebDialog
 */ 
function ElberosWebDialog(){
	ElberosWebComponent.call(this, null);
	this.is_modal = false;
	this.styles = ['standart'];
	this.title = '';
	this.content = '';
	this.buttons = [];
	
	this.$shadow = null;
}

ElberosWebDialog.prototype = Object.create(ElberosWebComponent.prototype);
ElberosWebDialog.prototype.constructor = ElberosWebDialog;

ObjectAssign( ElberosWebDialog.prototype, {
	
	isModal: function(){
		return this.is_modal;
	},
	
	setContent: function(val){
		this.content = val;
		if (this.$el != null){
			this.$el.find('.web_dialog__content').html(val);
		}
	},
	
	getContent: function(){
		return this.content;
	},
	
	setTitle: function(val){
		this.title = val;
		if (this.$el != null){
			this.$el.find('.web_dialog__title').html(val);
		}
	},
	
	getTitle: function(){
		return this.title;
	},
	
	
	close: function(){
		this.$el.remove();
		//$('.web_dialog__shadow').remove();
		this.$shadow.remove();
		
		if ($('.web_dialog__box').length == 0)
			$('body').removeClass('scroll-lock');
		
		this.sendEvent('close');
	},

	open: function( animation ){
		
		if (animation == undefined) animation = null;
		
		var $box = this.getDialogBox();
		$box.find('td').append(this.getDialogHtml());
		this.setElem($box);

		// Add shadow
		this.$shadow = $('<div class="web_dialog__shadow"></div>');
		$('body').append(this.$shadow);
		
		// Add to page
		$('body').append($box);
		
		if (animation == 'fade_in')
		{
			var $content = $box.find('.web_dialog__box_table');
			$content.css("top", "-1000px").css("position", "relative");
			
			// Fade in overlay
			this.$shadow.fadeIn(400);
			$content.animate({top:0}, 400);
		}
		
		// Scroll lock
		$('body').addClass('scroll-lock');
	},
	
	getDialogBox: function(){
		var styles = '';
		if (this.styles instanceof Array){
			var styles_arr = [];
			for (var i in this.styles){
				styles_arr.push('web_dialog__box--' + this.styles[i])
			}
			styles = styles_arr.join(' ');
		}
		var $obj = $('<div class="web_dialog__box ' + styles + '"><table class="web_dialog__box_table"><tr class="web_dialog__box_tr"><td class="web_dialog__box_td"></td></tr></table></div>');
		
		//this.$shadow = $('<div class="web_dialog__shadow"></div>');
		//$obj.prepend(this.$shadow);
		
		return $obj;
	},
	
	getDialogHtml: function(){
		var $dialog = $('<div class="web_dialog"></div>');
		
		$dialog.append(this.getButtonCloseHtml());
		$dialog.append(this.getTitleHtml());
		$dialog.append(this.getContentHtml());
		$dialog.append(this.getButtonsHtml());
		$dialog.append("<div class='web_dialog__result'></div>");
		$dialog.append("<div class='clear'></div>");
		
		return $dialog;
	},
	
	getTitleHtml: function(){
		var $title = $("<div class='web_dialog__title'></div>");
		$title.append(this.getTitle());
		return $title;
	},
	
	getContentHtml: function(){
		var $content = $("<div class='web_dialog__content'></div>");
		$content.append(this.getContent());
		return $content;
	},
	
	getButtonCloseHtml: function(){
		return '<button class="button web_dialog__button_close"><div></div></button>';
	},
	
	getButtonsHtml: function(){
		
		if (this.buttons instanceof Array){
			var $html = $('<div class="web_dialog__buttons"></div>');
			for (var i=0; i<this.buttons.length; i++){
				var button = this.buttons[i];
				
				var $button = this.getButtonHtml(button);
				$html.append($button);
			}
			
			return $html;
		}
		else if (this.buttons !== null){
			return $('<div class="web_dialog__buttons">'+this.buttons+'</div>');
		}
	},
	
	getButtonHtml: function(button){
		var cls = this.isset(button['class']) ? button['class'] : 'button';
		var text = this.isset(button['text']) ? button['text'] : 'Text';
		var click = this.isset(button['click']) ? button['click'] : function(dialog, $button){
			dialog.close();
		}
		
		// Create button
		var $button = $("<button class='" + cls + "'>"+ text +"</button>");
		$button.click((function(dialog, $button, button){
			return function(){
				button['click'](dialog, $button);
			}
		})(this, $button, button));
		
		return $button;
	}
	
});


$(document).on('click', '.web_dialog__button_close', function(){
	var $box = $(this).parents('.web_dialog__box');
	if ($box.length == 0)
		return;
	if ($box.controller == 0)
		return;
	var box = $box.get(0);
	if (box.controller instanceof ElberosWebDialog)	
		box.controller.close();
});


$(document).on('click', '.web_dialog__box .web_dialog__box_table', function(e){
	
	var $box = $(e.target);
	if (
		$box.hasClass('web_dialog__box_table') ||
		$box.hasClass('web_dialog__box_tr') || 
		$box.hasClass('web_dialog__box_td') ||
		$box.hasClass('web_dialog__shadow')
	){
		$box = $box.parents('.web_dialog__box');
	}
	if (!$box.hasClass('web_dialog__box') && !$(e.target).hasClass('web_dialog__shadow'))
		return;
	if ($box.length == 0)
		return;
	if ($box.controller == 0)
		return;
	var box = $box.get(0);
	if (box.controller instanceof ElberosWebDialog)	
		if (!box.controller.isModal())
			box.controller.close();
});


/** Web Image Dialog **/

function ElberosWebImageDialog(){
	ElberosWebDialog.call(this);
	
	this.styles.push('image');
	
	this.images = [];
	this.pos = 0;
}

ElberosWebImageDialog.prototype = Object.create(ElberosWebDialog.prototype);
ElberosWebImageDialog.prototype.constructor = ElberosWebDialog;

Object.assign( ElberosWebImageDialog.prototype, {
	
	getDialogHtml: function(){
		var $dialog = $('<div class="web_dialog"></div>');
		
		var $img = $('<div class="web_dialog__image"></div>')
		$img.append("<img class='web_dialog__image_preview' unselectable='on'></img>");
		$img.append(this.getButtonCloseHtml());
		$img.append("<div class='web_dialog__image_title'></div>");
		
		$dialog.append($img);
		$dialog.append("<div class='clear'></div>");
		if (this.images.length > 1)
		{
			$dialog.append("<div class='web_dialog__arrow web_dialog__arrow--left'></div>");
			$dialog.append("<div class='web_dialog__arrow web_dialog__arrow--right'></div>");
		}
		
		// Disable select
		$dialog.find('.web_dialog__image_preview').on('selectstart', false);
		
		// Arrow click
		$dialog.find('.web_dialog__arrow--left').click((function(obj){
			return function(e){ 
				obj.showPrevImage();
				e.preventDefault();
				return false;
			}
		})(this));
		
		$dialog.find('.web_dialog__arrow--right').click((function(obj){
			return function(e){ 
				obj.showNextImage();
				e.preventDefault();
				return false;
			}
		})(this));
	
		
		// Mouse move
		$dialog.find('.web_dialog__arrow').mousemove((function(obj){
			return function(e){ 
				e.preventDefault();
				return false;
			}
		})(this));
		
		return $dialog;
	},
	
	open: function(){
		ElberosWebDialog.prototype.open.call(this);
		this.showCurrentImage();
	},
	
	push: function(src, title){
		this.images.push({
			src: src,
			title: title,
		});
	},
	
	setCurrentImage: function(src){
		this.pos = 0;
		
		for (var i=0; i<this.images.length; i++){
			if (this.images[i].src == src)
				this.pos = i;
		}
	},
	
	showCurrentImage: function(){
		this.pos = this.showImage(this.pos);
	},
	
	showNextImage: function(){
		this.pos = this.showImage(this.pos + 1);
	},
	
	showPrevImage: function(){
		this.pos = this.showImage(this.pos - 1);
	},
	
	showImage: function(pos){
		if (this.images.length == 0)
			return 0;
		
		pos = pos % this.images.length;
		pos = (pos + 2 * this.images.length) % this.images.length;
		
		var image = this.images[pos];
		this.$el.find('.web_dialog__image_preview').attr('src', '');
		this.$el.find('.web_dialog__image_preview').attr('src', image.src);
		
		if (image.title == undefined) this.$el.find('.web_dialog__image_title').html('');
		else this.$el.find('.web_dialog__image_title').html(image.title);
		
		return pos;
	},
	
});


$(document).on('click', '.gallery__item', function(e){
	
	var dialog = new ElberosWebImageDialog();
	var $gallery = $(this).parents('.gallery');
	
	$gallery.find('.gallery__item').each(function(){
		var src = $(this).attr('src');
		var href = $(this).attr('data-image-big');
		var title = $(this).attr('data-image-title');
		
		if ( $(this).hasClass('bx-clone') )
			return;
		
		if (href == undefined || href == null) href = src;
		
		dialog.push(href, title);
	});
	
	dialog.setCurrentImage( $(this).attr('data-image-big') );
	dialog.open();
});



/**
 * Get forms data
 */
function elberosFormsGetFieldValue (elem)
{
	var $elem = $(elem);
	var type = $elem.attr('type');
	var tag = $elem.prop("tagName").toLowerCase();
	
	if (typeof elem.controller != "undefined" && elem.controller != null)
	{
		return elem.controller.getData();
	}
	else if ($elem.hasClass('ckeditor_type'))
	{
		var instance = CKEDITOR.instances[elem.id];
		if (typeof instance != 'undefined'){
			var value = instance.getData();
			return value;
		}
	}
	else if ($elem.hasClass('multiselect'))
	{
		var arr = $elem.select2('data');
		var res = [];
		for (var i in arr){
			res.push(arr[i].id);
		}
		return res;
	}
	else if (tag == 'input' && type == 'checkbox')
	{
		if ($elem.prop("checked"))
			return 1;
		return 0;
	}
	else if (tag == 'input' && type == 'radio')
	{
		return null;
	}
	
	else if (tag == 'input' && type == 'file')
	{
		var multiple = $elem.attr('multiple');
		if (typeof multiple != "undefined" && multiple !== false){
			return $elem.get(0).files;
		}
		return $elem.get(0).files[0];
	}
	
	else if (tag == 'select')
	{
		var value = $elem.val();
		if ($elem.prop('multiple'))
		{
			if (value == null) return [];
		}
		if (value == null) return "";
		return value;
	}
	
	return $elem.val();
}

function elberosFormsGetData( $items )
{
	var res = {};
	$items.each(
		function()
		{
			var key = $(this).attr('name');
			var value = elberosFormsGetFieldValue(this);
			res[key] = value;
		}
	);
	return res;
}


function elberosFormSubmit ( form_api_name, send_data, callback )
{
	var data = {};
	data['form_api_name'] = form_api_name;
	data['data'] = send_data;
	//data['_form_token'] = getCookie(SECRET_TOKEN_NAME);
	
	var gclid = null;
	try{
		gclid = ga.getAll()[0].get('clientId');
	}
	catch(ex){
	}
	if (gclid)
	{
		data['data']['gclid'] = gclid;
	}
	
	var contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
	var processData = true;
	if (data instanceof FormData){
		contentType = false;
		processData = false;
	}
	
	var url = "/api/";
	
	$.ajax({
		url: url,
		data: data,
		dataType: 'json',
		method: 'post',
		
		cache: false,
        contentType: contentType,
        processData: processData,
		
		success: (function(callback){
			return function(data, textStatus, jqXHR){
				if (data.success){
					callback(data);
				}
				else{
					callback(data);
				}
			}
		})(success, error),
		error: (function(callback){
			return function(data, textStatus, jqXHR){
				
				callback({
					message: "System error",
					code: -100,
				});
				
			}
		})(success, error),
	});
	
}



function elberosFormShowDialog(params)
{
	var dialog = (params.dialog != undefined) ? params.dialog : new WebDialog();
	dialog.is_modal = dialog.isset(params['is_modal']) ? params['is_modal'] : false;
	dialog.styles = dialog.isset(params['styles']) ? params['styles'] : ['standart'];
	dialog.title = dialog.isset(params['title']) ? params['title'] : 'Title';
	dialog.content = $(params['selector']).html();
	dialog.buttons = dialog.isset(params['buttons']) ? params['buttons'] : [
		{
			'class': 'button button--blue',
			'text': MESSAGES['CLOSE'],
			'click': function(dialog, $button){
				dialog.close();
			},
		}
	];
	dialog.open();
	
	if (params.onOpenDialog != undefined)
	{
		params.onOpenDialog(dialog.$el);
	}
	
	var $el = dialog.$el;
	
	if (dialog.isset(params['fields'])){
		var fields = params['fields'];
		for (var field_name in fields){
			var field = fields[field_name];
			
			if (dialog.isset(params['name']))
				$el.find('.form__label[for='+field_name+']')
					.html(field['name']);
					
			if (dialog.isset(params['placeholder']))
				$el.find('.form__field[name='+field_name+']')
					.attr('placeholder', field['placeholder']);
		}
	}
	
	if (dialog.isset(params['button_text'])){
		$el.find('.button--submit').html(params['button_text']);
	}
	
	if (dialog.isset(params['form__title'])){
		$el.find('.form__title').html( dialog.isset(params['form__title']) ? 
				params['form__title'] : '');
	}
	
	var $form = $el.find('form');
	$form.find('.button--submit').click( function($form, params)
	{
		return function()
		{
			var data = formsGetData( $form.find('.web_form__field') );
			var goal_type = dialog.isset(params['goal_type']) ? params['goal_type'] : 'site_zakaz';
			var form_api_name = dialog.isset(params['form_api_name']) ? params['form_api_name'] : '';
			var lead_title = dialog.isset(params['lead_title']) ? params['lead_title'] : '';
			data['goal_type'] = goal_type;
			data['lead_title'] = lead_title;
			
			$form.find('.web_form__result').html(MESSAGES['WAIT_REQUEST']);
			$form.find('.web_form__result').removeClass('web_form__result--error');
			$form.find('.web_form__result').removeClass('web_form__result--success');
			$form.find('.web_form__field_error').html('');
			
			elberosFormSubmit(
				form_api_name, data,
				
				// Result
				function(res)
				{
					if (res.code == 1)
					{
						$form.find('.web_form__result').addClass('web_form__result--success');
						if (params.success_message != undefined)
						{
							$form.find('.web_form__result').html(params.success_message);
						}
						else
						{
							$form.find('.web_form__result').html(res.message);
						}
						
						if (sendSiteEvent != undefined)
						{
							sendSiteEvent('metrika_event', goal_type);
						}
						
						if (params.success_redirect != undefined)
						{
							setTimeout((function(success_redirect){
								return function()
								{
									document.location=success_redirect;
								}
							})(params.success_redirect), 500);
						}
					}
					else 
					{
						$form.find('.web_form__result').addClass('web_form__result--error');
						$form.find('.web_form__result').html(res.message);
						
						if (res.fields)
						{
							for (var key in res.fields)
							{
								$form.find('.web_form__field_error').each(
									function()
									{
										var name = $(this).attr("data-api-name");
										if (name != key) return;
										var arr = res.fields[key];
										for (var i=0; i<arr.length; i++)
										{
											$(this).append( "<div>" + arr[i].message + "</div>" );
										}
									}
								);
							}
						}
						
					}
				},
			);
		}
	}($form, params));
	
	return;
}
