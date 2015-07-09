<?php
/**
	* Power-up Name: Constant Contact
	* Power-up Class: WPConstantContactConnect
	* Power-up Menu Text: 
	* Power-up Slug: constant_contact_connect
	* Power-up Menu Link: settings
	* Power-up URI: 
	* Power-up Description: Push your contacts to Constant Contact email lists.
	* Power-up Icon: power-up-icon-constant-contact-connect
	* Power-up Icon Small: power-up-icon-constant-contact-connect_small
	* First Introduced: 0.8.0
	* Power-up Tags: Newsletter, Email
	* Auto Activate: No
	* Permanently Enabled: No
	* Hidden: No
	* cURL Required: Yes
*/

//=============================================
// Define Constants
//=============================================

if ( !defined('LEADOUT_CONSTANT_CONTACT_CONNECT_PATH') )
    define('LEADOUT_CONSTANT_CONTACT_CONNECT_PATH', LEADOUT_PATH . '/power-ups/constant-contact-connect');

if ( !defined('LEADOUT_CONSTANT_CONTACT_CONNECT_PLUGIN_DIR') )
	define('LEADOUT_CONSTANT_CONTACT_CONNECT_PLUGIN_DIR', LEADOUT_PLUGIN_DIR . '/power-ups/constant-contact-connect');

if ( !defined('LEADOUT_CONSTANT_CONTACT_CONNECT_PLUGIN_SLUG') )
	define('LEADOUT_CONSTANT_CONTACT_CONNECT_SLUG', basename(dirname(__FILE__)));

if ( !defined('LEADOUT_CONSTANT_CONTACT_API_KEY') )
	define('LEADOUT_CONSTANT_CONTACT_API_KEY', 'p5hrzdhe2zrwbm76r2u7pvtc');



//=============================================
// Include Needed Files
//=============================================
require_once(LEADOUT_CONSTANT_CONTACT_CONNECT_PLUGIN_DIR . '/admin/constant-contact-connect-admin.php');
require_once(LEADOUT_CONSTANT_CONTACT_CONNECT_PLUGIN_DIR . '/inc/li_constant_contact.php');

//=============================================
// WPLeadOut Class
//=============================================
class WPConstantContactConnect extends WPLeadOut {
	
	var $admin;
	var $options;
	var $power_option_name = 'leadin_cc_options';
	var $constant_contact;
	var $cc_id;

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

		global $leadout_constant_contact_connect_wp;
		$leadout_constant_contact_connect_wp = $this;
		$this->options = get_option($this->power_option_name);
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

	/**
	 * Activate the power-up and add the defaults
	 */
	function add_defaults ()
	{

	}

	/**
     * Adds a subcsriber to a specific list
     *
     * @param   string
     * @param   string
     * @param   string
     * @param   string
     * @param   string
     * @return  int 		ContactID for the new entry
     */
	function push_contact_to_list ( $list_id = '', $email = '', $first_name = '', $last_name = '', $phone = '' ) 
	{
		if ( isset($this->options['li_cc_email']) && isset($this->options['li_cc_password']) && $this->options['li_cc_email'] && $this->options['li_cc_password'] )
		{
			// Convert the stored list id integer into the accepted constant contact list id format
			$list_id = 'http://api.constantcontact.com/ws/customers/' . str_replace('@', '%40', $this->options['li_cc_email']) . '/lists/' . $list_id;

			$this->constant_contact = new LI_ConstantContact($this->options['li_cc_email'], $this->options['li_cc_password'], LEADOUT_CONSTANT_CONTACT_API_KEY, FALSE);

			if ( ! isset($this->cc_id) )
			{
				$this->cc_id = $this->constant_contact->search_contact_by_email($email);
			}

			if ( $this->cc_id )
			{
				return $this->constant_contact->add_subscription($this->cc_id, $list_id, 'ACTION_BY_CLIENT');
			}
			else
			{
				$contact = array();
				if ( $email )
					$contact['EmailAddress'] = $email;

				if ( $first_name )
					$contact['FirstName'] = $first_name;

				if ( $last_name )
					$contact['LastName'] = $last_name;

				if ( $phone )
					$contact['HomePhone'] = $phone;

				if ( $phone )
					$contact['WorkPhone'] = $phone;

				return $this->constant_contact->add_contact($contact, array($list_id));
			}
	    }
	}

	/**
     * Removes an email address from a specific list
     *
     * @param   string
     * @param   string
     * @return  bool
     */
	function remove_contact_from_list ( $list_id = '', $email = '' ) 
	{
		if ( isset($this->options['li_cc_email']) && isset($this->options['li_cc_password']) && $this->options['li_cc_email'] && $this->options['li_cc_password'] )
		{
			// Convert the stored list id integer into the accepted constant contact list id format
			$list_id = 'http://api.constantcontact.com/ws/customers/' . str_replace('@', '%40', $this->options['li_cc_email']) . '/lists/' . $list_id;

			$this->constant_contact = new LI_ConstantContact($this->options['li_cc_email'], $this->options['li_cc_password'], LEADOUT_CONSTANT_CONTACT_API_KEY, FALSE);
			$cc_id = $this->constant_contact->search_contact_by_email($email);

			if ( $cc_id )
			{
				return $this->constant_contact->remove_subscription($cc_id, $list_id);
			}
			else
				return FALSE;
	    }
	}

	/**
     * Adds a subcsriber to a specific list
     *
     * @param   string
     * @param   array
     * @return  int/bool 		API status code OR false if api key not set
     */
	function bulk_push_contact_to_list ( $list_id = '', $contacts = '' ) 
	{
		/* 
			The majority of our user base doesn't use Constant Contact, so we decided not to retroactively sync contacts to the list.
			If people complain, we will respond with a support ticket and ask them to export/import manually.
		*/

		return FALSE;
	}
}

//=============================================
// ESP Connect Init
//=============================================

global $leadout_constant_contact_connect_wp;

?>