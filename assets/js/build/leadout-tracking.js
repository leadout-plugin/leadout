/*!
 * jQuery Cookie Plugin v1.4.0
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as anonymous module.
		define(['jquery'], factory);
	} else {
		// Browser globals.
		factory(jQuery);
	}
}(function ($) {

	var pluses = /\+/g;

	function encode(s) {
		return config.raw ? s : encodeURIComponent(s);
	}

	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}

	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}

	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape...
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}

		try {
			// Replace server-side written pluses with spaces.
			// If we can't decode the cookie, ignore it, it's unusable.
			// If we can't parse the cookie, ignore it, it's unusable.
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch(e) {}
	}

	function read(s, converter) {
		var value = config.raw ? s : parseCookieValue(s);
		return $.isFunction(converter) ? converter(value) : value;
	}

	var config = $.cookie = function (key, value, options) {

		// Write
		if (value !== undefined && !$.isFunction(value)) {
			options = $.extend({}, config.defaults, options);

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setDate(t.getDate() + days);
			}

			return (document.cookie = [
				encode(key), '=', stringifyCookieValue(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// Read

		var result = key ? undefined : {};

		// To prevent the for loop in the first place assign an empty array
		// in case there are no cookies at all. Also prevents odd result when
		// calling $.cookie().
		var cookies = document.cookie ? document.cookie.split('; ') : [];

		for (var i = 0, l = cookies.length; i < l; i++) {
			var parts = cookies[i].split('=');
			var name = decode(parts.shift());
			var cookie = parts.join('=');

			if (key && key === name) {
				// If second argument (value) is a function it's a converter...
				result = read(cookie, value);
				break;
			}

			// Prevent storing a cookie that we couldn't decode.
			if (!key && (cookie = read(cookie)) !== undefined) {
				result[name] = cookie;
			}
		}

		return result;
	};

	config.defaults = {};

	$.removeCookie = function (key, options) {
		if ($.cookie(key) === undefined) {
			return false;
		}

		// Must not alter options, thus extending a fresh object...
		$.cookie(key, '', $.extend({}, options, { expires: -1 }));
		return !$.cookie(key);
	};

}));
var page_title = jQuery(document).find("title").text();
var page_url = window.location.href;
var page_referrer = document.referrer;
var form_saved = false;
var ignore_form = false;
var leadout_debug_mode = false;

jQuery(document).ready( function ( $ ) {

	var hashkey = $.cookie("li_hash");
	var li_submission_cookie = $.cookie("li_submission");

	// The submission didn't officially finish before the page refresh, so try it again
	if ( li_submission_cookie )
	{
		var submission_data = JSON.parse(li_submission_cookie);
		leadout_insert_form_submission(
			submission_data.submission_hash, 
			submission_data.hashkey, 
			submission_data.page_title, 
			submission_data.page_url, 
			submission_data.json_form_fields, 
			submission_data.lead_email, 
			submission_data.lead_first_name, 
			submission_data.lead_last_name, 
			submission_data.lead_phone, 
			submission_data.form_selector_id, 
			submission_data.form_selector_classes, 
			function ( data ) {
				// Form was submitted successfully before page reload. Delete cookie for this submission
				$.removeCookie('li_submission', {path: "/", domain: ""});
			}
		);
	}

	if ( !hashkey )
	{
		hashkey = Math.random().toString(36).slice(2);
		$.cookie("li_hash", hashkey, {path: "/", domain: ""});
		leadout_insert_lead(hashkey, page_referrer);
	}

	leadout_log_pageview(hashkey, page_title, page_url, page_referrer, $.cookie('li_last_visit'));

	var date = new Date();
	var current_time = date.getTime();
	date.setTime(date.getTime() + (60 * 60 * 1000));
	
	// The li_last_visit has expired, so check to see if this is a stale contact that has been merged
	if ( !$.cookie('li_last_visit') )
	{
		leadout_check_merged_contact(hashkey);
	}

	$.cookie("li_last_visit", current_time, {path: "/", domain: "", expires: date});
});

jQuery(function($){

	// Many WordPress sites run outdated version of jQuery. This is a fix to support jQuery < 1.7.0 and futureproof the plugin when bind, live, etc are deprecated
	if ( $.versioncompare($.fn.jquery, '1.7.0') != -1 )
	{
		$(document).on('submit', 'form', function( e ) {
			if ( ! ( $(this).attr('id') == 'loginform' && $(this).attr('action').indexOf('wp-login.php') != -1 ) && ! ( $(this).attr('id') == 'lostpasswordform' && $(this).attr('action').indexOf('wp-login.php') != -1 ) )
			{
				var $form = $(this).closest('form');
				leadout_submit_form($form, $);
			}
		});
	}
	else
	{
		$(document).bind('submit', 'form', function( e ) {
			if ( ! ( $(this).attr('id') == 'loginform' && $(this).attr('action').indexOf('wp-login.php') != -1 ) && ! ( $(this).attr('id') == 'lostpasswordform' && $(this).attr('action').indexOf('wp-login.php') != -1 ) )
			{
				var $form = $(this).closest('form');
				leadout_submit_form($form, $);
			}
		});
	}
});

function leadout_start_debug_mode ()
{
	leadout_debug_mode = true;

	jQuery.ajax({
		type: 'POST',
		url: li_ajax.ajax_url,
		data: {
			"action": "leadout_print_debug_values"
		},
		success: function( data ) {
			console.log("SERVER CONFIG:\n------------\n");
			console.log('jQuery version: ' + jQuery.fn.jquery + "\n");

			console.log(data);

			if ( jQuery.versioncompare('1.7.0', jQuery.fn.jquery) != -1 )
			{
				console.log('- jQuery version < 1.7.0');
			}
		},
		error: function ( error_data ) {
			//alert(error_data);
		}
	});

	return "Debug started...";	
}

function leadout_submit_form ( $form, $ )
{
	if ( leadout_debug_mode )
	{
		console.log("\nFUNCTION FIRED: leadout_submit_form()");
		console.log("FIELDS:\n-----------\n");
	}

	var $this = $form;

	var form_fields 	= [];
	var lead_email 		= '';
	var lead_first_name = '';
	var lead_last_name 	= '';
	var lead_phone 		= '';
	var form_selector_id 		= ( $form.attr('id') ? $form.attr('id') : '' );
	var form_selector_classes 	= ( $form.classes() ? $form.classes().join(',') : '' );

	// Excludes hidden input fields + submit inputs
	$this.find('input[type!="submit"], textarea').not('input[type="hidden"], input[type="radio"], input[type="password"]').each( function ( index ) { 

		var $element = $(this);
		var $value = $element.val();

		if ( !$element.is(':visible' ) )
			return true; 

		// Check if input has an attached lable using for= tag
		var $label = $("label[for='" + $element.attr('id') + "']").text();
		
		// Ninja Forms hack
		if ($label.length == 0) 
		{
			if ( $('#' + $element.attr('id') + "_label").length )
				$label = $('#' + $element.attr('id') + "_label").text();
		}

		// Check for label in same container immediately before input
		if ($label.length == 0) 
		{
			$label = $element.prev('label').not('.li_used').addClass('li_used').first().text();

			if ( !$label.length ) 
			{
				$label = $element.prevAll('b, strong, span').text(); // Find previous closest string
			}
		}

		// Check for label in same container immediately after input
		if ($label.length == 0) 
		{
			$label = $element.next('label').not('.li_used').addClass('li_used').first().text();

			if ( !$label.length ) 
			{
				$label = $element.nextAll('b, strong, span').text(); // Find next closest string
			}
		}

		// Checks the parent for a label or bold text
		if ($label.length == 0) 
		{
			$label = $element.parent().find('label, b, strong').not('.li_used').first().text();
		}

		// Checks the parent's parent for a label or bold text
		if ($label.length == 0) 
		{
			if ( $.contains($this, $element.parent().parent()) )
			{
				$label = $element.parent().parent().find('label, b, strong').first().text();
			}
		}

		// Looks for closests p tag parent, and looks for label inside
		if ( $label.length == 0 ) 
		{
			$p = $element.closest('p').not('.li_used').addClass('li_used');
			
			// This gets the text from the p tag parent if it exists
			if ( $p.length )
			{
				$label = $p.text();
				$label = $.trim($label.replace($value, "")); // Hack to exclude the textarea text from the label text
			}
		}

		// Check for placeholder attribute
		if ( $label.length == 0 )
		{
			if ( $element.attr('placeholder') !== undefined )
			{
				$label = $element.attr('placeholder').toString();
			}
		}

		if ( $label.length == 0 ) 
		{
			if ( $element.attr('name') !== undefined )
			{
				$label = $element.attr('name').toString();
			}
		}

		if ( $element.is(':checkbox') )
		{
			if ( $element.is(':checked')) 
			{
				$value = 'Checked';
			}
			else
			{
				$value = 'Not checked';
			}
		}

		// Remove fakepath from input[type="file"]
		$value = $value.replace("C:\\fakepath\\", "");

		var $label_text = $.trim($label.replaceArray(["(", ")", "required", "Required", "*", ":"], [""]));
		var lower_label_text = $label_text.toLowerCase();

		if ( ! ignore_field($label_text, $value) )
			push_form_field($label_text, $value, form_fields);
		else
		{
			if ( leadout_debug_mode )
				console.log('	- Skipping... label: ' + $label + ' value: ' + $value);	
		}

		// Set email
		if ( $value.indexOf('@') != -1 && $value.indexOf('.') != -1 && !lead_email )
			lead_email = $value;

		// Set first name 
		if ( ! lead_first_name )
		{
			if ( $element.attr('id') == 'leadout-subscribe-fname' )
				lead_first_name = $value;
			else if ( lower_label_text == 'first' || lower_label_text == 'first name' || lower_label_text == 'name' || lower_label_text == 'your name' )
				lead_first_name = $value;
		}

		// Set last name
		if ( ! lead_last_name )
		{
			if ( $element.attr('id') == 'leadout-subscribe-lname' )
				lead_last_name = $value;
			else if ( lower_label_text == 'last' || lower_label_text == 'last name' || lower_label_text == 'your last name' || lower_label_text == 'surname' )
				lead_last_name = $value;
		}

		// Set phone number
		if ( ! lead_phone )
		{
			if ( $element.attr('id') == 'leadout-subscribe-phone' )
				lead_phone = $value;
			else if ( lower_label_text == 'phone' || lower_label_text == 'phone number' )
				lead_phone = $value;
		}
	});

	var radio_groups = [];
	var rbg_label_values = [];
	$this.find(":radio").each(function(){
		if ( $.inArray(this.name, radio_groups) == -1 )
	   		radio_groups.push(this.name);
	   		rbg_label_values.push($(this).val());
	});

	for ( var i = 0; i < radio_groups.length; i++ )
	{
		var $rbg = $("input:radio[name='" + radio_groups[i] + "']");
		var $rbg_value = $("input:radio[name='" + radio_groups[i] + "']:checked").val();

		if ( $this.find('.gfield').length ) // Hack for gravity forms
			$p = $rbg.closest('.gfield').not('.li_used').addClass('li_used');
		else if ( $this.find('.frm_form_field').length ) // Hack for Formidable
			$p = $rbg.closest('.frm_form_field').not('.li_used').addClass('li_used');
		else
			$p = $rbg.closest('div, p').not('.li_used').addClass('li_used');
		
		// This gets the text from the p tag parent if it exists
		if ( $p.length )
		{
			//$p.find('label, strong, span, b').html();
			$rbg_label = $p.text();
			$rbg_label = $.trim($rbg_label.replaceArray(rbg_label_values, [""]).replace($p.find('.gfield_description').text(), ''));
			// Remove .gfield_description from gravity forms
		}

		var rgb_selected = ( !$("input:radio[name='" + radio_groups[i] + "']:checked").val() ) ? 'not selected' : $("input:radio[name='" + radio_groups[i] + "']:checked").val();

		if ( ! ignore_field($rbg_label, rgb_selected) )
			push_form_field($rbg_label, rgb_selected, form_fields);
		else
		{
			if ( leadout_debug_mode )
				console.log('Skipping... label: ' + $label + ' value: ' + $value);	
		}
	}

	$this.find('select').each( function ( ) {
		var $select = $(this);
		var $select_label = $("label[for='" + $select.attr('id') + "']").text();

		if ( !$select_label.length )
		{
			var select_values = [];
			$select.find("option").each(function(){
				if ( $.inArray($(this).val(), select_values) == -1 )
			   		select_values.push($(this).val());
			});

			$p = $select.closest('div, p').not('.li_used').addClass('li_used');

			if ( $this.find('.gfield').length ) // Hack for gravity forms
				$p = $select.closest('.gfield').not('.li_used').addClass('li_used');
			else
			{	
				$p = $select.closest('div, p').addClass('li_used');
			}

			if ( $p.length )
			{
				$select_label = $p.text();
				$select_label = $.trim($select_label.replaceArray(select_values, [""]).replace($p.find('.gfield_description').text(), ''));
			}
		}

		var select_value = '';
		if ( $select.val() instanceof Array )
		{
			var select_vals = $select.val();
			
			for ( i = 0; i < select_vals.length; i++ )
			{
				select_value += select_vals[i];
				if ( i != select_vals.length - 1 )
					select_value += ', ';
			}
		}
		else
		{
			if ( $select.find('option:selected').text() )
				select_value = $select.find('option:selected').text();
			else
				select_value = $select.val();
		}

		if ( ! ignore_field($select_label, select_value) )
			push_form_field($select_label, select_value, form_fields);
		else
		{
			if ( leadout_debug_mode )
				console.log('Skipping... label: ' + $label + ' value: ' + $value);	
		}
	});

	$this.find('.li_used').removeClass('li_used'); // Clean up added classes

	// Save submission into database if email is present and form is not ignore, send LeadOut email, and submit form as usual
	if ( lead_email )
	{
		if ( leadout_debug_mode )
			console.log("\nFOUND lead_email: " + lead_email + "\n");

		if ( ignore_form )
		{
			push_form_field('Credit card form submitted', 'Payment fields not collected for security', form_fields);
		}

		var submission_hash = Math.random().toString(36).slice(2);
		var hashkey = $.cookie("li_hash");
		var json_form_fields = JSON.stringify(form_fields);

		var form_submission = {};
		form_submission = {
			"submission_hash": 	submission_hash,
			"hashkey": 			hashkey,
			"lead_email": 		lead_email,
			"lead_first_name": 	lead_first_name,
			"lead_last_name": 	lead_last_name,
			"lead_phone": 		lead_phone,
			"page_title": 		page_title,
			"page_url": 		page_url,
			"json_form_fields": 		json_form_fields,
			"form_selector_id": 		form_selector_id,
			"form_selector_classes": 	form_selector_classes
		};

		if ( leadout_debug_mode )
		{
			console.log("\nFORM SUBMISSION OBJECT:");
			console.log(form_submission);
		}

		$.cookie("li_submission", JSON.stringify(form_submission), {path: "/", domain: ""});

		leadout_insert_form_submission(
			submission_hash, 
			hashkey, 
			page_title, 
			page_url, 
			json_form_fields, 
			lead_email, 
			lead_first_name, 
			lead_last_name, 
			lead_phone,
			form_selector_id,
			form_selector_classes, 
			function ( data ) {
				// Form was executed 100% successfully before page reload. Delete cookie for this submission
				$.removeCookie('li_submission', {path: "/", domain: ""});
			}
		);
	}
	else // No lead - submit form as usual
	{
		form_saved = true;

		if ( leadout_debug_mode )
			console.log("\ERROR: lead_email not found\n");
	}
}

function leadout_check_merged_contact ( hashkey )
{
	jQuery.ajax({
		type: 'POST',
		url: li_ajax.ajax_url,
		data: {
			"action": "leadout_check_merged_contact", 
			"li_id": hashkey
		},
		success: function(data){
			// Force override the current tracking with the merged value
			var json_data = jQuery.parseJSON(data);
			if ( json_data )
				jQuery.cookie("li_hash", json_data, {path: "/", domain: ""});
		},
		error: function ( error_data ) {
			//alert(error_data);
		}
	});
}

function leadout_check_visitor_status ( hashkey, callback )
{
	jQuery.ajax({
		type: 'POST',
		url: li_ajax.ajax_url,
		data: {
			"action": "leadout_check_visitor_status", 
			"li_id": hashkey
		},
		success: function(data){
			// Force override the current tracking with the merged value
			var json_data = jQuery.parseJSON(data);
			
			if ( callback )
				callback(json_data);
		},
		error: function ( error_data ) {
			//alert(error_data);
		}
	});
}

function leadout_log_pageview ( hashkey, page_title, page_url, page_referrer, last_visit )
{
	jQuery.ajax({
		type: 'POST',
		url: li_ajax.ajax_url,
		data: {
			"action": "leadout_log_pageview", 
			"li_id": hashkey,
			"li_title": page_title,
			"li_url": page_url,
			"li_referrer": page_referrer,
			"li_last_visit": last_visit
		},
		success: function(data){
		},
		error: function ( error_data ) {
			//alert(error_data);
		}
	});
}

function leadout_insert_lead ( hashkey, page_referrer ) {
	jQuery.ajax({
		type: 'POST',
		url: li_ajax.ajax_url,
		data: {
			"action": "leadout_insert_lead", 
			"li_id": hashkey,
			"li_referrer": page_referrer
		},
		success: function(data){
		},
		error: function ( error_data ) {
			//alert(error_data);
		}
	});
}

function leadout_insert_form_submission ( submission_haskey, hashkey, page_title, page_url, json_fields, lead_email, lead_first_name, lead_last_name, lead_phone, form_selector_id, form_selector_classes, Callback )
{
	if ( leadout_debug_mode )
		console.log("\nFUNCTION FIRED: leadout_insert_form_submission()");

	jQuery.ajax({
		type: 'POST',
		url: li_ajax.ajax_url,
		data: {
			"action": 			"leadout_insert_form_submission", 
			"li_submission_id": submission_haskey,
			"li_id": 			hashkey,
			"li_title": 		page_title,
			"li_url": 			page_url,
			"li_fields": 		json_fields,
			"li_email": 		lead_email,
			"li_first_name": 	lead_first_name,
			"li_last_name": 	lead_last_name,
			"li_phone": 		lead_phone,
			"li_form_selector_id": 	form_selector_id,
			"li_form_selector_classes": form_selector_classes
		},
		success: function(data){
			
			if ( leadout_debug_mode )
				console.log('RESULT rows updated: ' + data);
			
			if ( Callback )
				Callback(data);
		},
		error: function ( error_data ) {
			//alert(error_data);
		}
	});

}

function push_form_field ( label, value, form_fields )
{
	var field = {
	    label: label,
	    value: value
	};

	form_fields.push(field);

	if ( leadout_debug_mode )
		console.log('	+ Adding... [label:] ' + label + ' [value:] ' + value);
}

function ignore_field ( label, value )
{
	var bool_ignore_field = false;

	// Ignore any fields with labels that indicate a credit card field
	if ( label.toLowerCase().indexOf('credit card') != -1 || label.toLowerCase().indexOf('card number') != -1 )
		bool_ignore_field = true;

	if ( label.toLowerCase().indexOf('expiration') != -1 || label.toLowerCase().indexOf('expiry') != -1)
		bool_ignore_field = true;

	if ( label.toLowerCase() == 'month' || label.toLowerCase() == 'mm' || label.toLowerCase() == 'yy' || label.toLowerCase() == 'yyyy' || label.toLowerCase() == 'year' )
		bool_ignore_field = true;

	if ( label.toLowerCase().indexOf('cvv') != -1 || label.toLowerCase().indexOf('cvc') != -1 || label.toLowerCase().indexOf('secure code') != -1 || label.toLowerCase().indexOf('security code') != -1 )
		bool_ignore_field = true;

	if ( value.toLowerCase() == 'visa' || value.toLowerCase() == 'mastercard' || value.toLowerCase() == 'american express' || value.toLowerCase() == 'amex' || value.toLowerCase() == 'discover' )
		bool_ignore_field = true;

	// Check if value has integers, strip out spaces, then ignore anything with a credit card length (>16) or an expiration/cvv length (<5)
	var int_regex = new RegExp("/^[0-9]+$/"); 
	if ( int_regex.test(value) )
	{
		var value_no_spaces = value.replace(' ', '');

		if ( isInt(value_no_spaces) && value_no_spaces.length >= 16 )
			bool_ignore_field = true;
	}

	// Hack for the form parser sometimes rolling up all form fields into one massive label
	if ( label.length > 250 )
		bool_ignore_field = true;

	if ( bool_ignore_field )
	{
		if ( ! ignore_form )
			ignore_form = true;

		return true;
	}
	else
		return false;
}

String.prototype.replaceArray = function(find, replace) {
  var replaceString = this;
  for (var i = 0; i < find.length; i++) {
  	if ( replace.length != 1 )
    	replaceString = replaceString.replace(find[i], replace[i]);	
    else
    	replaceString = replaceString.replace(find[i], replace[0]);	
  }
  return replaceString;
};

/** 
 * Checks the version number of jQuery and compares to string
 *
 * @param string
 * @param string
 *
 * @return bool
 */

(function($){
  $.versioncompare = function(version1, version2){
    if ('undefined' === typeof version1) {
      throw new Error("$.versioncompare needs at least one parameter.");
    }
    version2 = version2 || $.fn.jquery;
    if (version1 == version2) {
      return 0;
    }
    var v1 = normalize(version1);
    var v2 = normalize(version2);
    var len = Math.max(v1.length, v2.length);
    for (var i = 0; i < len; i++) {
      v1[i] = v1[i] || 0;
      v2[i] = v2[i] || 0;
      if (v1[i] == v2[i]) {
        continue;
      }
      return v1[i] > v2[i] ? 1 : -1;
    }
    return 0;
  };
  function normalize(version){
    return $.map(version.split('.'), function(value){
      return parseInt(value, 10);
    });
  }
}(jQuery));


(function ($) {
    $.fn.classes = function (callback) {
        var classes = [];
        $.each(this, function (i, v) {
            var splitClassName = v.className.split(/\s+/);
            for (var j in splitClassName) {
                var className = splitClassName[j];
                if (-1 === classes.indexOf(className)) {
                    classes.push(className);
                }
            }
        });
        if ('function' === typeof callback) {
            for (var i in classes) {
                callback(classes[i]);
            }
        }
        return classes;
    };
})(jQuery);

function isInt ( n ) {
    return typeof n== "number" && isFinite(n) && n%1===0;
}