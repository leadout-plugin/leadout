<?php
//=============================================
// LIAweberConnectAdmin Class
//=============================================
class LIAweberConnectAdmin extends WPLeadOutAdmin {
    
    var $power_up_settings_section = 'leadin_aweber_connect_options_section';
    var $power_option_name = 'leadin_aweber_connect_options';
    var $power_up_icon;
    var $options;
    var $authed = FALSE;
    var $invalid_key = FALSE;

    /**
     * Class constructor
     */
    function __construct ( $power_up_icon_small )
    {
        //=============================================
        // Hooks & Filters
        //=============================================

        if ( is_admin() )
        {
            $this->power_up_icon = $power_up_icon_small;
            add_action('admin_init', array($this, 'leadout_build_esp_settings_page'));
            $this->options = get_option($this->power_option_name);
            $this->authed = ( isset($this->options['li_ac_auth_code']) && $this->options['li_ac_auth_code'] ? TRUE : FALSE );

            if ( $this->authed )
            {
                if ( ! isset($this->options['li_ac_ck']) )
                {
                    $valid = $this->li_ac_connect_to_api($this->options['li_ac_auth_code']);

                    if ( ! $valid )
                        $this->invalid_key = TRUE;
                }
                else
                {
                    $this->invalid_key = $this->li_ac_check_invalid_auth_code($this->options['li_ac_ck'], $this->options['li_ac_cs'], $this->options['li_ac_ak'], $this->options['li_ac_as']);
                }
            }
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
        register_setting('leadout_settings_options', $this->power_option_name, array($this, 'sanitize'));
        add_settings_section($this->power_up_settings_section, $this->power_up_icon . "AWeber", array($this, 'aweber_connect_section_callback'), LEADOUT_ADMIN_PATH);
        add_settings_field('li_ac_auth_code', 'Authorization Code', array($this, 'li_ac_auth_code_callback'), LEADOUT_ADMIN_PATH, $this->power_up_settings_section);

        if ( isset($this->options['li_ac_auth_code']) )
        {
            if ( $this->options['li_ac_auth_code'] && ! $this->invalid_key )
                add_settings_field('li_print_synced_lists', 'Synced tags', array($this, 'li_print_synced_lists'), LEADOUT_ADMIN_PATH, $this->power_up_settings_section);
        }
    }

    function aweber_connect_section_callback ( )
    {
        $this->print_hidden_settings_fields();        
    }

    function print_hidden_settings_fields ()
    {
         // Hacky solution to solve the Settings API overwriting the default values
        $li_ac_ck = ( isset($this->options['li_ac_ck']) && $this->options['li_ac_ck'] ? $this->options['li_ac_ck'] : '' );
        $li_ac_cs = ( isset($this->options['li_ac_cs']) && $this->options['li_ac_cs'] ? $this->options['li_ac_cs'] : '' );
        $li_ac_ak = ( isset($this->options['li_ac_ak']) && $this->options['li_ac_ak'] ? $this->options['li_ac_ak'] : '' );
        $li_ac_as = ( isset($this->options['li_ac_as']) && $this->options['li_ac_as'] ? $this->options['li_ac_as'] : '' );

        if ( $li_ac_ck )
        {
            printf(
                '<input id="li_ac_ck" type="hidden" name="' . $this->power_option_name . '[li_ac_ck]" value="%s"/>',
                $li_ac_ck
            );
        }

        if ( $li_ac_cs )
        {
            printf(
                '<input id="li_ac_cs" type="hidden" name="' . $this->power_option_name . '[li_ac_cs]" value="%s"/>',
                $li_ac_cs
            );
        }

        if ( $li_ac_ak )
        {
            printf(
                '<input id="li_ac_ak" type="hidden" name="' . $this->power_option_name . '[li_ac_ak]" value="%s"/>',
                $li_ac_ak
            );
        }

        if ( $li_ac_as )
        {
            printf(
                '<input id="li_ac_as" type="hidden" name="' . $this->power_option_name . '[li_ac_as]" value="%s"/>',
                $li_ac_as
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

        if ( isset( $input['li_ac_auth_code'] ) )
            $new_input['li_ac_auth_code'] = sanitize_text_field( $input['li_ac_auth_code'] );

        if ( isset( $input['li_ac_ck'] ) )
            $new_input['li_ac_ck'] = sanitize_text_field( $input['li_ac_ck'] );

        if ( isset( $input['li_ac_cs'] ) )
            $new_input['li_ac_cs'] = sanitize_text_field( $input['li_ac_cs'] );

        if ( isset( $input['li_ac_ak'] ) )
            $new_input['li_ac_ak'] = sanitize_text_field( $input['li_ac_ak'] );

        if ( isset( $input['li_ac_as'] ) )
            $new_input['li_ac_as'] = sanitize_text_field( $input['li_ac_as'] );

        return $new_input;
    }

    /**
     * Prints API key input for settings page
     */
    function li_ac_auth_code_callback ()
    {
        $li_ac_auth_code = ( $this->options['li_ac_auth_code'] ? $this->options['li_ac_auth_code'] : '' ); // Get header from options, or show default
        
        printf(
            '<input id="li_ac_auth_code" type="text" id="title" name="' . $this->power_option_name . '[li_ac_auth_code]" value="%s" style="width: 430px;"/>',
            $li_ac_auth_code
        );

        if ( ! isset($li_ac_auth_code) || ! $li_ac_auth_code || $this->invalid_key )
            echo '<p><a target="_blank" href="https://help.aweber.com/hc/en-us/articles/204031226-How-Do-I-Authorize-an-App-">Get your Authorization Code</a> from <a href="https://auth.aweber.com/1.0/oauth/authorize_app/156b03fb" target="_blank">AWeber.com</a></p>';
    }

    /**
     * Prints synced lists out for settings page in format  Tag Name → ESP list
     */
    function li_print_synced_lists ()
    {
        $li_ac_auth_code = ( $this->options['li_ac_auth_code'] ? $this->options['li_ac_auth_code'] : '' ); // Get header from options, or show default
        
        if ( isset($li_ac_auth_code ) )
        {
            $synced_lists = $this->li_get_synced_list_for_esp('aweber');
            $list_value_pairs = array();
            $synced_list_count = 0;

            echo '<table>';
            foreach ( $synced_lists as $synced_list )
            {
                foreach ( stripslashes_deep(unserialize($synced_list->tag_synced_lists)) as $tag_synced_list )
                {
                    if ( $tag_synced_list['esp'] == 'aweber' )
                    {
                        echo '<tr class="synced-list-row">';
                            echo '<td class="synced-list-cell"><span class="icon-tag"></span> ' . $synced_list->tag_text . '</td>';
                            echo '<td class="synced-list-cell"><span class="synced-list-arrow">&#8594;</span></td>';
                            echo '<td class="synced-list-cell"><span class="icon-envelope"></span> ' . $tag_synced_list['list_name'] . '</td>';
                            echo '<td class="synced-list-edit"><a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags&action=edit_tag&tag=' . $synced_list->tag_id . '">edit tag</a></td>';
                        echo '</tr>';

                        $synced_list_count++;
                    }
                }
            }
            echo '</table>';

            if ( ! $synced_list_count ) {
                echo '<p>AWeber connected succesfully! <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags">Select a tag to send contacts to AWeber</a>.</p>';
            } else {
                echo '<p><a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags">Edit your tags</a> or <a href="https://www.aweber.com/users/newlist#about" target="_blank">Create a new list on AWeber.com</a></p>';
            }
        }
    }

    /**
     * Get synced list for the ESP from the WordPress database
     *
     * @return array/object    
     */
    function li_get_synced_list_for_esp ( $esp_name, $output_type = 'OBJECT' )
    {
        global $wpdb;

        $q = $wpdb->prepare("SELECT * FROM $wpdb->li_tags WHERE tag_synced_lists LIKE '%%%s%%' AND tag_deleted = 0", $esp_name);
        $synced_lists = $wpdb->get_results($q, $output_type);

        return $synced_lists;
    }

    /**
     * Format API-returned lists into parseable format on front end for LeadOut to consume
     *
     * @return array    
     */
    function li_get_lists ( )
    {
        $lists = $this->li_get_api_lists($this->options['li_ac_ck'], $this->options['li_ac_cs'], $this->options['li_ac_ak'], $this->options['li_ac_as']);
        
        $sanitized_lists = array();
        if ( count($lists) )
        {
            foreach ( $lists as $list )
            {
                $list_obj = (Object)NULL;
                $list_obj->id = $list->id;
                $list_obj->name = $list->name;

                array_push($sanitized_lists, $list_obj);;
            }
        }
        
        return $sanitized_lists;
    }

    /**
     * Get lists from AWeber account
     *
     * @param string
     * @return array    
     */
    function li_get_api_lists ( $consumer_key, $consumer_secret, $access_key, $access_secret )
    {
        try 
        {
            $aweber = new LI_AWeberAPI($consumer_key, $consumer_secret);
            $account = $aweber->getAccount($access_key, $access_secret);
            $lists = $account->lists;
            return $lists;
        }
        catch ( LI_AWeberAPIException $exc )
        {
            /*print "<h3>AWeberAPIException:</h3>";
            print " <li> Type: $exc->type              <br>";
            print " <li> Msg : $exc->message           <br>";
            print " <li> Docs: $exc->documentation_url <br>";
            print "<hr>";*/
        }
    }

    /**
     * Use MailChimp API key to try to grab corresponding user profile to check validity of key
     *
     * @param string
     * @return bool    
     */
    function li_ac_check_invalid_auth_code ( $consumer_key, $consumer_secret, $access_key, $access_secret )
    {
        try 
        {
            $aweber = new LI_AWeberAPI($consumer_key, $consumer_secret);
            $account = $aweber->getAccount($access_key, $access_secret);
        }
        catch ( LI_AWeberAPIException $exc )
        {
            if ( $exc->type == 'UnauthorizedError' )
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
            
            /*print "<h3>AWeberAPIException:</h3>";
            print " <li> Type: $exc->type              <br>";
            print " <li> Msg : $exc->message           <br>";
            print " <li> Docs: $exc->documentation_url <br>";
            print "<hr>";*/
        }
    } 

    function li_ac_connect_to_api ( $auth_code )
    {
        try 
        {
            $auth = LI_AWeberAPI::getDataFromAweberID($auth_code);
            list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = $auth;
            
            if ( $consumerKey )
                $this->options['li_ac_ck'] = $consumerKey;

            if ( $consumerSecret )
                $this->options['li_ac_cs'] = $consumerSecret;

            if ( $accessKey )
                $this->options['li_ac_ak'] = $accessKey;

            if ( $accessSecret )
                $this->options['li_ac_as'] = $accessSecret;

            if ( ! $consumerKey && ! $consumerSecret &&! $accessKey &&! $accessSecret )
                return FALSE;

            update_option($this->power_option_name, $this->options);
        }
        catch ( LI_AWeberAPIException $exc ) 
        {
            /*print "<h3>AWeberAPIException:</h3>";
            print " <li> Type: $exc->type              <br>";
            print " <li> Msg : $exc->message           <br>";
            print " <li> Docs: $exc->documentation_url <br>";
            print "<hr>";*/
        }
    }
}

?>
