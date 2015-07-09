<?php
//=============================================
// LIGetResponseConnectAdmin Class
//=============================================
class LIGetResponseConnectAdmin extends WPLeadInAdmin {
    
    var $power_up_settings_section = 'leadin_getresponse_connect_options_section';
    var $power_option_name = 'leadin_getresponse_connect_options';
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
            $this->authed = ( isset($this->options['li_gr_api_key']) && $this->options['li_gr_api_key'] ? TRUE : FALSE );

            if ( $this->authed )
                $this->invalid_key = $this->li_check_invalid_api_key($this->options['li_gr_api_key']);
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
        add_settings_section($this->power_up_settings_section, $this->power_up_icon . "GetResponse", '', LEADIN_ADMIN_PATH);
        add_settings_field('li_gr_api_key', 'API key', array($this, 'li_gr_api_key_callback'), LEADIN_ADMIN_PATH, $this->power_up_settings_section);

        if ( isset($this->options['li_gr_api_key']) )
        {
            if ( $this->options['li_gr_api_key'] && ! $this->invalid_key )
                add_settings_field('li_print_synced_lists', 'Synced tags', array($this, 'li_print_synced_lists'), LEADIN_ADMIN_PATH, $this->power_up_settings_section);
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

        if ( isset( $input['li_gr_api_key'] ) )
            $new_input['li_gr_api_key'] = sanitize_text_field( $input['li_gr_api_key'] );

        return $new_input;
    }

    /**
     * Prints API key input for settings page
     */
    function li_gr_api_key_callback ()
    {
        $li_gr_api_key = ( isset($this->options['li_gr_api_key']) && $this->options['li_gr_api_key'] ? $this->options['li_gr_api_key'] : '' ); // Get header from options, or show default
        
        printf(
            '<input id="li_gr_api_key" type="text" id="title" name="' . $this->power_option_name . '[li_gr_api_key]" value="%s" size="50"/>',
            $li_gr_api_key
        );

        if ( ! isset($li_gr_api_key) || ! $li_gr_api_key || $this->invalid_key )
            echo '<p><a target="_blank" href="http://support.getresponse.com/faq/where-i-find-api-key">Get your API key</a> from <a href="https://app.getresponse.com/account.html#api" target="_blank">GetResponse.com</a></p>';
    }

    /**
     * Prints synced lists out for settings page in format  Tag Name → ESP list
     */
    function li_print_synced_lists ()
    {
        $li_gr_api_key = ( $this->options['li_gr_api_key'] ? $this->options['li_gr_api_key'] : '' ); // Get header from options, or show default
        
        if ( isset($li_gr_api_key ) )
        {
            $synced_lists = $this->li_get_synced_list_for_esp('getresponse');
            $list_value_pairs = array();
            $synced_list_count = 0;

            echo '<table>';
            foreach ( $synced_lists as $synced_list )
            {
                foreach ( stripslashes_deep(unserialize($synced_list->tag_synced_lists)) as $tag_synced_list )
                {
                    if ( $tag_synced_list['esp'] == 'getresponse' )
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
                echo '<p>GetResponse connected succesfully! <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags">Select a tag to send contacts to GetResponse</a>.</p>';
            } else {
                echo '<p><a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags">Edit your tags</a> or <a href="https://app.getresponse.com/create_campaign.html" target="_blank">Create a new list on GetResponse.com</a></p>';
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
     * Format API-returned lists into parseable format on front end
     *
     * @return array    
     */
    function li_get_lists ( )
    {
        $lists = $this->li_get_api_lists($this->options['li_gr_api_key']);
        
        $sanitized_lists = array();
        if ( count($lists) )
        {
            foreach ( $lists as $list_id => $list )
            {
                $list_obj = (Object)NULL;
                $list_obj->id = $list_id;
                $list_obj->name = $list->name;

                array_push($sanitized_lists, $list_obj);;
            }
        }
        
        return $sanitized_lists;
    }

    /**
     * Get lists from GetResponse account
     *
     * @param string
     * @return array    
     */
    function li_get_api_lists ( $api_key )
    {
        $gr = new LI_GetResponse($api_key);
        $campaigns = $gr->getCampaigns();

        return $campaigns;
    }

    /**
     * Use MailChimp API key to try to grab corresponding user profile to check validity of key
     *
     * @param string
     * @return bool    
     */
    function li_check_invalid_api_key ( $api_key )
    {
        $gr = new LI_GetResponse($api_key);
        $ping = $gr->ping();

        if ( ! empty($ping) )
            $invalid_key = FALSE;
        else
        {
            $invalid_key = TRUE;
        }

        return $invalid_key;
    } 
}

?>
