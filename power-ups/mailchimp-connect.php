<?php
/**
    * Power-up Name: MailChimp
    * Power-up Class: WPMailChimpConnect
    * Power-up Menu Text: 
    * Power-up Slug: mailchimp_connect
    * Power-up Menu Link: settings
    * Power-up URI: 
    * Power-up Description: Push your contacts to MailChimp email lists.
    * Power-up Icon: power-up-icon-mailchimp-connect
    * Power-up Icon Small: power-up-icon-mailchimp-connect_small
    * First Introduced: 0.7.0
    * Power-up Tags: Newsletter, Email
    * Auto Activate: No
    * Permanently Enabled: No
    * Hidden: No
    * cURL Required: Yes
*/

//=============================================
// Define Constants
//=============================================

if ( !defined('LEADIN_MAILCHIMP_CONNECT_PATH') )
    define('LEADIN_MAILCHIMP_CONNECT_PATH', LEADIN_PATH . '/power-ups/mailchimp-connect');

if ( !defined('LEADIN_MAILCHIMP_CONNECT_PLUGIN_DIR') )
    define('LEADIN_MAILCHIMP_CONNECT_PLUGIN_DIR', LEADIN_PLUGIN_DIR . '/power-ups/mailchimp-connect');

if ( !defined('LEADIN_MAILCHIMP_CONNECT_PLUGIN_SLUG') )
    define('LEADIN_MAILCHIMP_CONNECT_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================
require_once(LEADIN_MAILCHIMP_CONNECT_PLUGIN_DIR . '/admin/mailchimp-connect-admin.php');
require_once(LEADIN_MAILCHIMP_CONNECT_PLUGIN_DIR . '/inc/MailChimp-API.php');

//=============================================
// WPLeadIn Class
//=============================================
class WPMailChimpConnect extends WPLeadIn {
    
    var $admin;
    var $options;
    var $power_option_name = 'leadin_mls_options';

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

        global $leadout_mailchimp_connect_wp;
        $leadout_mailchimp_connect_wp = $this;
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
     * @return  int/bool        API status code OR false if api key not set
     */
    function push_contact_to_list ( $list_id = '', $email = '', $first_name = '', $last_name = '', $phone = '' ) 
    {
        if ( isset($this->options['li_mls_api_key']) && $this->options['li_mls_api_key'] && $list_id )
        {
            $MailChimp = new LI_MailChimp($this->options['li_mls_api_key']);
            $contact_synced = $MailChimp->call("lists/subscribe", array(
                "id" => $list_id,
                "email" => array('email' => $email),
                "send_welcome" => FALSE,
                "email_type" => 'html',
                "update_existing" => TRUE,
                'replace_interests' => FALSE,
                'double_optin' => FALSE,
                "merge_vars" => array(
                    'EMAIL' => $email,
                    'FNAME' => $first_name,
                    'LNAME' => $last_name,
                    'PHONE' => $phone
                )
            ));

            return $contact_synced;
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
        if ( isset($this->options['li_mls_api_key']) && $this->options['li_mls_api_key'] && $list_id )
        {
            $MailChimp = new LI_MailChimp($this->options['li_mls_api_key']);

            $batch_contacts = array();
            if ( count($contacts) )
            {
                foreach ( $contacts as $contact )
                {
                    array_push($batch_contacts, array(
                        'email' => array('email' => $contact->lead_email), 
                        'merge_vars' => array(
                            'EMAIL' => $contact->lead_email,
                            'FNAME' => $contact->lead_first_name,
                            'LNAME' => $contact->lead_last_name
                        ))
                    );
                }
            }

            $list_updated = $MailChimp->call("lists/batch-subscribe", array(
                "id" => $list_id,
                "send_welcome" => FALSE,
                "email_type" => 'html',
                "update_existing" => TRUE,
                'replace_interests' => FALSE,
                'double_optin' => FALSE,
                "batch" => $batch_contacts,
            ));

            return $list_updated;
        }

        return FALSE;
    }
}

//=============================================
// ESP Connect Init
//=============================================

global $leadout_mailchimp_connect_wp;

?>