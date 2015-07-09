<?php
/**
    * Power-up Name: GetResponse
    * Power-up Class: LIGetResponseConnect
    * Power-up Menu Text: 
    * Power-up Slug: getresponse_connect
    * Power-up Menu Link: settings
    * Power-up URI: 
    * Power-up Description: Push your contacts to GetResponse email lists.
    * Power-up Icon: power-up-icon-getresponse-connect
    * Power-up Icon Small: power-up-icon-getresponse-connect_small
    * First Introduced: 3.1.0
    * Power-up Tags: Newsletter, Email
    * Auto Activate: No
    * Permanently Enabled: No
    * Hidden: No
    * cURL Required: Yes
*/

//=============================================
// Define Constants
//=============================================

if ( !defined('LEADIN_GETRESPONSE_CONNECT_PATH') )
    define('LEADIN_GETRESPONSE_CONNECT_PATH', LEADIN_PATH . '/power-ups/getresponse-connect');

if ( !defined('LEADIN_GETRESPONSE_CONNECT_PLUGIN_DIR') )
    define('LEADIN_GETRESPONSE_CONNECT_PLUGIN_DIR', LEADIN_PLUGIN_DIR . '/power-ups/getresponse-connect');

if ( !defined('LEADIN_GETRESPONSE_CONNECT_PLUGIN_SLUG') )
    define('LEADIN_GETRESPONSE_CONNECT_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================
require_once(LEADIN_GETRESPONSE_CONNECT_PLUGIN_DIR . '/admin/getresponse-connect-admin.php');
require_once(LEADIN_GETRESPONSE_CONNECT_PLUGIN_DIR . '/inc/LI_GetResponseAPI.php');

//=============================================
// WPLeadIn Class
//=============================================
class LIGetResponseConnect extends WPLeadIn {
    
    var $admin;
    var $options;
    var $power_option_name = 'leadin_getresponse_connect_options';

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

        global $leadout_getresponse_connect_wp;
        $leadout_getresponse_connect_wp = $this;
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
     * @return  int/bool
     */
    function push_contact_to_list ( $list_id = '', $email = '', $first_name = '', $last_name = '', $phone = '' ) 
    {
        if ( isset($this->options['li_gr_api_key']) && $this->options['li_gr_api_key'] && $list_id )
        {
            $gr = new LI_GetResponse($this->options['li_gr_api_key']);
            $contact_synced = $gr->addContact($list_id, $first_name . ' ' . $last_name, $email);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Adds a subcsriber to a specific list
     *
     * @param   string
     * @param   array
     * @return  int/bool        API status code OR false if api key not set
     */
    function bulk_push_contact_to_list ( $list_id = '', $contacts = '' ) 
    {
        /* 
            The majority of our user base doesn't use Get Response, so we decided not to retroactively sync contacts to the list.
            If people complain, we will respond with a support ticket and ask them to export/import manually.
        */

        return FALSE;
    }
}

//=============================================
// ESP Connect Init
//=============================================

global $leadout_getresponse_connect_wp;

?>