<?php
/**
    * Power-up Name: AWeber
    * Power-up Class: LIAWeberConnect
    * Power-up Menu Text: 
    * Power-up Slug: aweber_connect
    * Power-up Menu Link: settings
    * Power-up URI: 
    * Power-up Description: Push your contacts to AWeber email lists.
    * Power-up Icon: power-up-icon-aweber-connect
    * Power-up Icon Small: power-up-icon-aweber-connect_small
    * First Introduced: 2.3.0
    * Power-up Tags: Newsletter, Email
    * Auto Activate: No
    * Permanently Enabled: No
    * Hidden: No
    * cURL Required: Yes
*/

//=============================================
// Define Constants
//=============================================

if ( !defined('LEADOUT_AWEBER_CONNECT_PATH') )
    define('LEADOUT_AWEBER_CONNECT_PATH', LEADOUT_PATH . '/power-ups/aweber-connect');

if ( !defined('LEADOUT_AWEBER_CONNECT_PLUGIN_DIR') )
    define('LEADOUT_AWEBER_CONNECT_PLUGIN_DIR', LEADOUT_PLUGIN_DIR . '/power-ups/aweber-connect');

if ( !defined('LEADOUT_AWEBER_CONNECT_PLUGIN_SLUG') )
    define('LEADOUT_AWEBER_CONNECT_PLUGIN_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================
require_once(LEADOUT_AWEBER_CONNECT_PLUGIN_DIR . '/admin/aweber-connect-admin.php');
require_once(LEADOUT_AWEBER_CONNECT_PLUGIN_DIR . '/inc/aweber_api/li_aweber_api.php');

//=============================================
// WPLeadOut Class
//=============================================
class LIAWeberConnect extends WPLeadOut {
    
    var $admin; 
    var $options;
    var $power_option_name = 'leadin_aweber_connect_options';

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

        global $leadout_aweber_connect_wp;
        $leadout_aweber_connect_wp = $this;
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
        if ( isset($this->options['li_ac_ck']) && $this->options['li_ac_ck'] && $list_id )
        {
            try
            {
                $aweber = new LI_AWeberAPI($this->options['li_ac_ck'], $this->options['li_ac_cs']);
                $account = $aweber->getAccount($this->options['li_ac_ak'], $this->options['li_ac_as']);

                $list_url = "/accounts/" . $account->id . "/lists/" . $list_id;

                //Check if custom Field Exists
                $list_custom_fields_url = "/accounts/" . $account->id . "/lists/" . $list_id . "/custom_fields";
                $list_custom_fields = $account->loadFromUrl($list_custom_fields_url);
                
                $params = array(
                    'email' => $email,             
                    'name' => $first_name . $last_name
                );

                if ( count($list_custom_fields->data['entries']) )
                {
                    foreach ( $list_custom_fields->data['entries'] as $entry ) 
                    {                                   
                        if ( $entry['name'] == 'phone' )
                        {
                            $params['custom_fields'] = array('phone' => $phone);
                            break;
                        }
                    }
                }
                 
                $list = $account->loadFromUrl($list_url);      

                $subscribers = $list->subscribers;
                $subscribers->create($params);

                return TRUE;
            } 
            catch ( AWeberAPIException $exc )
            {
                   
            }
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
            The majority of our user base doesn't use AWeber, so we decided not to retroactively sync contacts to the list.
            If people complain, we will respond with a support ticket and ask them to export/import manually.
        */

        return FALSE;
    }
}

//=============================================
// ESP Connect Init
//=============================================

global $leadout_aweber_connect_wp;

?>