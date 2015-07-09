<?php
/**
	* Power-up Name: Visitor Tracking
	* Power-up Class: WPLeadOutContacts
	* Power-up Menu Text: Contacts
	* Power-up Menu Link: contacts
	* Power-up Slug: contacts
	* Power-up URI: 
	* Power-up Description: Get an in-depth history of each contact in your database.
	* Power-up Icon: powerup-icon-leads
	* Power-up Icon Small: powerup-icon-leads
	* First Introduced: 0.4.7
	* Power-up Tags: Lead Tracking
	* Auto Activate: Yes
	* Permanently Enabled: Yes
	* Hidden: No
	* cURL Required: No
*/

//=============================================
// Define Constants
//=============================================

if ( !defined('LEADOUT_CONTACTS_PATH') )
    define('LEADOUT_CONTACTS_PATH', LEADOUT_PATH . '/power-ups/contacts');

if ( !defined('LEADOUT_CONTACTS_PLUGIN_DIR') )
	define('LEADOUT_CONTACTS_PLUGIN_DIR', LEADOUT_PLUGIN_DIR . '/power-ups/contacts');

if ( !defined('LEADOUT_CONTACTS_PLUGIN_SLUG') )
	define('LEADOUT_CONTACTS_PLUGIN_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================

require_once(LEADOUT_CONTACTS_PLUGIN_DIR . '/admin/contacts-admin.php');

//=============================================
// WPLeadOutContacts Class
//=============================================
class WPLeadOutContacts extends WPLeadOut {
	
	var $admin;
	var $options;

	/**
	 * Class constructor
	 */
	function __construct ( $activated )
	{
		//=============================================
		// Hooks & Filters
		//=============================================

		if ( ! $activated )
			return false;

		global $leadout_contacts;
		$leadout_contacts = $this;
	}

	public function admin_init ( )
	{
		$admin_class = get_class($this) . 'Admin';
		$this->admin = new $admin_class($this->icon_small);
	}

	function power_up_setup_callback ( )
	{
		$this->admin->power_up_setup_callback();
	}
}

//=============================================
// LeadOut Init
//=============================================

global $leadout_contacts;
//$leadout_contacts = new WPLeadOutContacts();

?>