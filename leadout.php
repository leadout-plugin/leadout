<?php
/*
Plugin Name: LeadOut
Plugin URI: http://github.com/leadout-plugin/leadout/
Description: LeadOut is an easy-to-use marketing automation and lead tracking plugin for WordPress that helps you better understand your web site visitors.
Version: 3.1.9
Author: LeadOut
Author URI: http://github.com/leadout-plugin/leadout/
License: GPL2
*/

//=============================================
// Hooks & Filters
//=============================================

/**
 * Activate the plugin
 */
function activate_leadout ( $network_wide )
{
	// Check activation on entire network or one blog
	if ( is_multisite() && $network_wide ) 
	{ 
		global $wpdb;
 
		// Get this so we can switch back to it later
		$current_blog = $wpdb->blogid;
		// For storing the list of activated blogs
		$activated = array();
 
		// Get all blogs in the network and activate plugin on each one
		$q = "SELECT blog_id FROM $wpdb->blogs";
		$blog_ids = $wpdb->get_col($q);
		foreach ( $blog_ids as $blog_id ) 
		{
			switch_to_blog($blog_id);
			add_leadout_defaults();
			$activated[] = $blog_id;
		}
 
		// Switch back to the current blog
		switch_to_blog($current_blog);
 
		// Store the array for a later function
		update_site_option('leadout_activated', $activated);
	}
	else
	{
		add_leadout_defaults();
	}
}

/**
 * Check LeadOut installation and set options
 */
function add_leadout_defaults ( )
{
	global $wpdb;

	$options = get_option('leadin_options');

	if ( ($options['li_installed'] != 1) || (!is_array($options)) )
	{
		$opt = array(
			'li_installed'				=> 1,
			'leadout_version'			=> LEADOUT_PLUGIN_VERSION,
			'li_db_version'				=> LEADOUT_DB_VERSION,
			'li_email' 					=> get_bloginfo('admin_email'),
			'li_updates_subscription'	=> 1,
			'onboarding_step'			=> 1,
			'onboarding_complete'		=> 0,
			'ignore_settings_popup'		=> 0,
			'data_recovered'			=> 1,
			'delete_flags_fixed'		=> 1,
			'converted_to_tags'			=> 1,
			'names_added_to_contacts'	=> 1
		);

		// this is a hack because multisite doesn't recognize local options using either update_option or update_site_option...
		if ( is_multisite() )
		{
			$multisite_prefix = ( is_multisite() ? $wpdb->prefix : '' );
			$q = $wpdb->prepare("
				INSERT INTO " . $multisite_prefix . "options 
			        ( option_name, option_value ) 
			    VALUES ('leadin_options', %s)", serialize($opt));
			$wpdb->query($q);
		}
		else
			update_option('leadin_options', $opt);
		
		leadout_db_install();

		$multisite_prefix = ( is_multisite() ? $wpdb->prefix : '' );
		$q = "
			INSERT INTO " . $multisite_prefix . "li_tags 
		        ( tag_text, tag_slug, tag_form_selectors, tag_synced_lists, tag_order ) 
		    VALUES ('Commenters', 'commenters', '#commentform', '', 1),
		        ('Leads', 'leads', '', '', 2),
		        ('Contacted', 'contacted', '', '', 3),
		        ('Customers', 'customers', '', '', 4)";
		$wpdb->query($q);
	}

	$leadout_active_power_ups = get_option('leadin_active_power_ups');

	if ( ! $leadout_active_power_ups )
	{
		$auto_activate = array(
			'contacts',
			'lookups' // 3.1.4 change - auto activating this power-up and using the Pro flag to toggle on/off
		);

		update_option('leadin_active_power_ups', serialize($auto_activate));
	}
}

/**
 * Deactivate LeadOut plugin hook
 */
function deactivate_leadout ( $network_wide )
{
	if ( is_multisite() && $network_wide ) 
	{ 
		global $wpdb;
 
		// Get this so we can switch back to it later
		$current_blog = $wpdb->blogid;
 
		// Get all blogs in the network and activate plugin on each one
		$q = "SELECT blog_id FROM $wpdb->blogs";
		$blog_ids = $wpdb->get_col($q);
		foreach ( $blog_ids as $blog_id ) 
		{
			switch_to_blog($blog_id);
		}
 
		// Switch back to the current blog
		switch_to_blog($current_blog);
	}
}

function activate_leadout_on_new_blog ( $blog_id, $user_id, $domain, $path, $site_id, $meta )
{
	global $wpdb;

	if ( is_plugin_active_for_network('leadout/leadout.php') )
	{
		$current_blog = $wpdb->blogid;
		switch_to_blog($blog_id);
		add_leadout_defaults();
		switch_to_blog($current_blog);
	}
}

/**
 * Checks the stored database version against the current data version + updates if needed
 */
function leadout_init ()
{
	if ( function_exists( 'activate_leadin' ) ) 
	{
		remove_action( 'plugins_loaded', 'leadout_init' );
     	deactivate_plugins(plugin_basename( __FILE__ ));

		add_action( 'admin_notices', 'deactivate_leadout_notice' );
	    return;
	}

	//=============================================
	// Define Constants
	//=============================================

	if ( !defined('LEADOUT_PATH') )
	    define('LEADOUT_PATH', untrailingslashit(plugins_url('', __FILE__ )));

	if ( !defined('LEADOUT_PLUGIN_DIR') )
		define('LEADOUT_PLUGIN_DIR', untrailingslashit(dirname( __FILE__ )));

	if ( !defined('LEADOUT_PLUGIN_SLUG') )
		define('LEADOUT_PLUGIN_SLUG', basename(dirname(__FILE__)));

	if ( !defined('LEADOUT_DB_VERSION') )
		define('LEADOUT_DB_VERSION', '2.2.4');

	if ( !defined('LEADOUT_PLUGIN_VERSION') )
		define('LEADOUT_PLUGIN_VERSION', '3.1.9');

	//=============================================
	// Include Needed Files
	//=============================================

	require_once(LEADOUT_PLUGIN_DIR . '/inc/leadout-ajax-functions.php');
	require_once(LEADOUT_PLUGIN_DIR . '/inc/leadout-functions.php');
	require_once(LEADOUT_PLUGIN_DIR . '/inc/class-emailer.php');
	require_once(LEADOUT_PLUGIN_DIR . '/admin/leadout-admin.php');

	require_once(LEADOUT_PLUGIN_DIR . '/inc/class-leadout.php');

	require_once(LEADOUT_PLUGIN_DIR . '/power-ups/subscribe-widget.php');
	require_once(LEADOUT_PLUGIN_DIR . '/power-ups/contacts.php');
	require_once(LEADOUT_PLUGIN_DIR . '/power-ups/mailchimp-connect.php');
	require_once(LEADOUT_PLUGIN_DIR . '/power-ups/constant-contact-connect.php');
	require_once(LEADOUT_PLUGIN_DIR . '/power-ups/aweber-connect.php');
	require_once(LEADOUT_PLUGIN_DIR . '/power-ups/campaign-monitor-connect.php');
	require_once(LEADOUT_PLUGIN_DIR . '/power-ups/getresponse-connect.php');

    $leadout_wp = new WPLeadOut();
}

//=============================================
// Database update
//=============================================

/**
 * Creates or updates the LeadOut tables
 */
function leadout_db_install ()
{
	global $wpdb;

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$multisite_prefix = ( is_multisite() ? $wpdb->prefix : '' );

	$sql = "
		CREATE TABLE " . $multisite_prefix . "li_leads (
		  `lead_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `lead_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `hashkey` varchar(16) DEFAULT NULL,
		  `lead_ip` varchar(40) DEFAULT NULL,
		  `lead_source` text,
		  `lead_email` varchar(255) DEFAULT NULL,
		  `lead_first_name` varchar(255) NOT NULL,
  		  `lead_last_name` varchar(255) NOT NULL,
		  `lead_status` set('contact','lead','comment','subscribe','contacted','customer') NOT NULL DEFAULT 'contact',
		  `merged_hashkeys` text,
		  `lead_deleted` int(1) NOT NULL DEFAULT '0',
		  `blog_id` int(11) unsigned NOT NULL,
		  `company_data` mediumtext NOT NULL,
  		  `social_data` mediumtext NOT NULL,
		  PRIMARY KEY (`lead_id`),
		  KEY `hashkey` (`hashkey`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

		CREATE TABLE " . $multisite_prefix . "li_pageviews (
		  `pageview_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `pageview_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `lead_hashkey` varchar(16) NOT NULL,
		  `pageview_title` varchar(255) NOT NULL,
		  `pageview_url` text NOT NULL,
		  `pageview_source` text NOT NULL,
		  `pageview_session_start` int(1) NOT NULL,
		  `pageview_deleted` int(1) NOT NULL DEFAULT '0',
		  `blog_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`pageview_id`),
		  KEY `lead_hashkey` (`lead_hashkey`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

		CREATE TABLE " . $multisite_prefix . "li_submissions (
		  `form_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `form_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `lead_hashkey` varchar(16) NOT NULL,
		  `form_page_title` varchar(255) NOT NULL,
		  `form_page_url` text NOT NULL,
		  `form_fields` text NOT NULL,
		  `form_selector_id` mediumtext NOT NULL,
		  `form_selector_classes` mediumtext NOT NULL,
		  `form_hashkey` varchar(16) NOT NULL,
		  `form_deleted` int(1) NOT NULL DEFAULT '0',
		  `blog_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`form_id`),
		  KEY `lead_hashkey` (`lead_hashkey`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

		CREATE TABLE " . $multisite_prefix . "li_tags (
		  `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `tag_text` varchar(255) NOT NULL,
		  `tag_slug` varchar(255) NOT NULL,
		  `tag_form_selectors` mediumtext NOT NULL,
		  `tag_synced_lists` mediumtext NOT NULL,
		  `tag_order` int(11) unsigned NOT NULL,
		  `blog_id` int(11) unsigned NOT NULL,
		  `tag_deleted` int(1) NOT NULL,
		  PRIMARY KEY (`tag_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

		CREATE TABLE " . $multisite_prefix . "li_tag_relationships (
		  `tag_relationship_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `tag_id` int(11) unsigned NOT NULL,
		  `contact_hashkey` varchar(16) NOT NULL,
  		  `form_hashkey` varchar(16) NOT NULL,
		  `tag_relationship_deleted` int(1) unsigned NOT NULL,
		  `blog_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`tag_relationship_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

	dbDelta($sql);

    leadout_update_option('leadin_options', 'li_db_version', LEADOUT_DB_VERSION);
}

function deactivate_leadout_notice () 
{
    ?>
    <div id="message" class="error">
        <?php _e( '<p><b>LeadOut was not activated because Leadin is still activated...</b></p><p>Don\'t panic - Leadin and LeadOut are like two rival siblings - they don\'t play nice together. Deactivate <b><i>Leadin</i></b> and then try activating <b><i>LeadOut</i></b> again, and everything should work fine.</p>', 'my-text-domain' ); ?>
    </div>
    <?php
}

add_action( 'plugins_loaded', 'leadout_init', 14 );

if ( is_admin() ) 
{
	// Activate + install LeadOut
	register_activation_hook( __FILE__, 'activate_leadout');

	// Deactivate LeadOut
	register_deactivation_hook( __FILE__, 'deactivate_leadout');

	// Activate on newly created wpmu blog
	add_action('wpmu_new_blog', 'activate_leadout_on_new_blog', 10, 6);
}

?>