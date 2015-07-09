<?php
//=============================================
// WPLeadOutAdmin Class
//=============================================
class WPConstantContactConnectAdmin extends WPLeadOutAdmin {
    
    var $power_up_settings_section = 'leadin_cc_options_section';
    var $power_option_name = 'leadin_cc_options';
    var $power_up_icon;
    var $bad_api_call;
    var $constant_contact;
    var $lists;
    var $options;
    var $authed = FALSE;
    var $invalid_key = FALSE;

    /**
     * Class constructor
     */
    function __construct ( $power_up_icon_small )
    {
        global $pagenow;

        //=============================================
        // Hooks & Filters
        //=============================================
        
        if ( is_admin() )
        {
            $this->power_up_icon = $power_up_icon_small;
            add_action('admin_init', array($this, 'leadout_build_esp_settings_page'));
            $this->options = get_option($this->power_option_name);
            if ( isset($this->options['li_cc_email']) && isset($this->options['li_cc_password']) && $this->options['li_cc_email'] && $this->options['li_cc_password'] )
                $this->authed = TRUE;
        }
    }

    //=============================================
    // Settings Page
    //=============================================

    /**
     * Creates settings options
     */
    function leadout_build_esp_settings_page ()
    {
        global $leadout_constant_contact_connect_wp;

        register_setting('leadout_settings_options', $this->power_option_name, array($this, 'sanitize'));
     
        // If the creds are set, check if they are any good by hitting the API
        if ( $this->authed )
        {
            // Try to make a request using the authentication credentials
            $this->lists = $this->li_cc_get_email_lists(LEADOUT_CONSTANT_CONTACT_API_KEY, $this->options['li_cc_email'], $this->options['li_cc_password']);

            if ( $this->constant_contact->cc_exception )
            {
                $this->bad_api_call = TRUE;
            }
        }

        add_settings_section($this->power_up_settings_section, $this->power_up_icon . "Constant Contact", array($this, 'cc_section_callback'), LEADOUT_ADMIN_PATH);
        
        if ( $this->authed && ! $this->bad_api_call )
            add_settings_field('li_print_synced_lists', 'Synced tags', array($this, 'li_print_synced_lists'), LEADOUT_ADMIN_PATH, $this->power_up_settings_section); 
        else
        {
            add_settings_field('li_cc_email', 'Email', array($this, 'li_cc_email_callback'), LEADOUT_ADMIN_PATH, $this->power_up_settings_section);
            add_settings_field('li_cc_password', 'Password', array($this, 'li_cc_password_callback'), LEADOUT_ADMIN_PATH, $this->power_up_settings_section);
        }
    }

    function cc_section_callback ( )
    {
        if ( ! $this->authed )
            echo '<div class="leadout-section">Sign into your Constant Contact account below to setup Contact Sync</div>';
        else if ( $this->bad_api_call )
            echo '<div class="leadout-section"><p style="color: #f33f33; font-weight: bold;">' . $this->constant_contact->cc_exception . '</p></div>';

        $this->print_hidden_settings_fields();        
    }

    function print_hidden_settings_fields ()
    {
         // Hacky solution to solve the Settings API overwriting the default values
        $li_cc_email = ( isset($this->options['li_cc_email']) && $this->options['li_cc_email'] ? $this->options['li_cc_email'] : '' );
        $li_cc_password = ( isset($this->options['li_cc_password']) && $this->options['li_cc_password'] ? $this->options['li_cc_password'] : '' );

        if ( $li_cc_email )
        {
            printf(
                '<input id="li_cc_email" type="hidden" name="' . $this->power_option_name . '[li_cc_email]" value="%s"/>',
                $li_cc_email
            );
        }

        if ( $li_cc_password )
        {
            printf(
                '<input id="li_cc_password" type="hidden" name="' . $this->power_option_name . '[li_cc_password]" value="%s"/>',
                $li_cc_password
            );
        }
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize ( $input )
    {
        $new_input = array();

        if ( isset( $input['li_cc_email'] ) )
            $new_input['li_cc_email'] = sanitize_text_field( $input['li_cc_email'] );

        if ( isset( $input['li_cc_password'] ) )
            $new_input['li_cc_password'] = sanitize_text_field( $input['li_cc_password'] );

        return $new_input;
    }

    /**
     * Prints email input for settings page
     */
    function li_cc_email_callback ()
    {
        $li_cc_email = ( $this->options['li_cc_email'] ? $this->options['li_cc_email'] : '' ); // Get header from options, or show default
        
        printf(
            '<input id="li_cc_email" type="text" id="title" name="' . $this->power_option_name . '[li_cc_email]" value="%s" style="width: 430px;"/>',
            $li_cc_email
        );
    }

    /**
     * Prints password input for settings page
     */
    function li_cc_password_callback ()
    {
        $li_cc_password = ( $this->options['li_cc_password'] ? $this->options['li_cc_password'] : '' ); // Get header from options, or show default
        
        printf(
            '<input id="li_cc_password" type="password" id="title" name="' . $this->power_option_name . '[li_cc_password]" value="%s" style="width: 430px;"/>',
            $li_cc_password
        );
    }

    function li_cc_get_email_lists ( $api_key, $username, $password )
    {
        $this->constant_contact = new LI_ConstantContact($username, $password, $api_key, FALSE);
        $lists = $this->constant_contact->get_lists();

        if ( count($lists) )
        {
            return $lists;
        }
        else
        {
            return array();
        }
    }

    /**
     * Prints synced lists with tag name
     */
    function li_print_synced_lists ()
    {
        $synced_lists = $this->li_get_synced_list_for_esp('constant_contact');
        $list_value_pairs = array();
        $synced_list_count = 0;

        echo '<table>';
        foreach ( $synced_lists as $synced_list )
        {
            foreach ( stripslashes_deep(unserialize($synced_list->tag_synced_lists)) as $tag_synced_list )
            {
                if ( $tag_synced_list['esp'] == 'constant_contact' )
                {
                    echo '<tr class="synced-list-row">';
                        echo '<td class="synced-list-cell"><span class="icon-tag"></span> ' . $synced_list->tag_text . '</td>';
                        echo '<td class="synced-list-cell"><span class="synced-list-arrow">&#8594;</span></td>';
                        echo '<td class="synced-list-cell"><span class="icon-envelope"></span> ' . $tag_synced_list['list_name'] . '</td>';
                        echo '<td class="synced-list-edit"><a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_contacts&action=edit_tag&tag=' . $synced_list->tag_id . '">edit</a></td>';
                    echo '</tr>';

                    $synced_list_count++;
                }
            }
        }
        echo '</table>';

        if ( ! $synced_list_count )
                echo "<p>You don't have any Constant Contact lists synced with LeadOut yet...</p>";
            
        echo '<p><a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags' . '">Manage tags</a></p>';

        echo '<p style="padding-top: 10px;"><a href="https://login.constantcontact.com/login/login.sdo?goto=https://ui.constantcontact.com/rnavmap/distui/contacts" target="_blank">Create a new list on ConstantContact.com</a></p>';
    }

    function li_get_synced_list_for_esp ( $esp_name, $output_type = 'OBJECT' )
    {
        global $wpdb;

        $q = $wpdb->prepare("SELECT * FROM $wpdb->li_tags WHERE tag_synced_lists LIKE '%%%s%%' AND tag_deleted = 0", $esp_name);
        $synced_lists = $wpdb->get_results($q, $output_type);

        return $synced_lists;
    }

    function li_get_lists ( )
    {
        $lists = $this->li_cc_get_email_lists(LEADOUT_CONSTANT_CONTACT_API_KEY, $this->options['li_cc_email'], $this->options['li_cc_password']);

        $sanitized_lists = array();

        if ( count($lists) )
        {
            foreach ( $lists as $list )
            {
                $list_obj = (Object)NULL;
                $list_obj->id = $list['ListID'];
                $list_obj->name = $list['Name'];

                array_push($sanitized_lists, $list_obj);;
            }
        }
        
        return $sanitized_lists;
    }
}

?>
