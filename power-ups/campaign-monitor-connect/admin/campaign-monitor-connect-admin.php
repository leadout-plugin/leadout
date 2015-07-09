<?php
//=============================================
// LICampaignMonitorConnectAdmin Class
//=============================================
class LICampaignMonitorConnectAdmin extends WPLeadInAdmin {
    
    var $power_up_settings_section = 'leadin_campaign_monitor_connect_options_section';
    var $power_option_name = 'leadin_campaign_monitor_connect_options';
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
            $this->authed = ( isset($this->options['li_cm_api_key']) && $this->options['li_cm_api_key'] ? TRUE : FALSE );

            if ( $this->authed )
                $this->invalid_key = $this->li_check_invalid_api_key($this->options['li_cm_api_key']);
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
        add_settings_section($this->power_up_settings_section, $this->power_up_icon . "Campaign Monitor", '', LEADIN_ADMIN_PATH);
        add_settings_field('li_cm_api_key', 'API key', array($this, 'li_cm_api_key_callback'), LEADIN_ADMIN_PATH, $this->power_up_settings_section);

        if ( isset($this->options['li_cm_api_key']) )
        {
            if ( $this->options['li_cm_api_key'] && ! $this->invalid_key )
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

        if ( isset( $input['li_cm_api_key'] ) )
            $new_input['li_cm_api_key'] = sanitize_text_field( $input['li_cm_api_key'] );

        return $new_input;
    }

    /**
     * Prints API key input for settings page
     */
    function li_cm_api_key_callback ()
    {
        $li_cm_api_key = ( isset($this->options['li_cm_api_key']) && $this->options['li_cm_api_key'] ? $this->options['li_cm_api_key'] : '' ); // Get header from options, or show default
        
        printf(
            '<input id="li_cm_api_key" type="text" id="title" name="' . $this->power_option_name . '[li_cm_api_key]" value="%s" style="width: 430px;"/>',
            $li_cm_api_key
        );

        if ( ! isset($li_cm_api_key) || ! $li_cm_api_key || $this->invalid_key )
            echo '<p><a target="_blank" href="http://help.campaignmonitor.com/topic.aspx?t=206">Get your API key</a> from <a href="https://login.createsend.com/l" target="_blank">CampaignMonitor.com</a></p>';
    }

    /**
     * Prints synced lists out for settings page in format  Tag Name → ESP list
     */
    function li_print_synced_lists ()
    {
        $li_cm_api_key = ( $this->options['li_cm_api_key'] ? $this->options['li_cm_api_key'] : '' ); // Get header from options, or show default
        
        if ( isset($li_cm_api_key ) )
        {
            $synced_lists = $this->li_get_synced_list_for_esp('campaign_monitor');
            $list_value_pairs = array();
            $synced_list_count = 0;

            echo '<table>';
            foreach ( $synced_lists as $synced_list )
            {
                foreach ( stripslashes_deep(unserialize($synced_list->tag_synced_lists)) as $tag_synced_list )
                {
                    if ( $tag_synced_list['esp'] == 'campaign_monitor' )
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
                echo '<p>Campaign Monitor connected succesfully! <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags">Select a tag to send contacts to Campaign Monitor</a>.</p>';
            } else {
                echo '<p><a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_tags">Edit your tags</a> or <a href="https://login.createsend.com/l" target="_blank">Create a new list on CampaignMonitor.com</a></p>';
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
        $lists = $this->li_get_api_lists($this->options['li_cm_api_key']);

        $sanitized_lists = array();
        if ( count($lists) )
        {
            foreach ( $lists as $list )
            {
                $list_obj = (Object)NULL;
                $list_obj->id = $list['ListID'];
                $list_obj->name = $list['Name'] . ' (Client: ' . $list['ClientName'] . ')';

                array_push($sanitized_lists, $list_obj);;
            }
        }
        
        return $sanitized_lists;
    }

    /**
     * Get lists from Campaign Monitor account
     *
     * @param string
     * @return array    
     */
    function li_get_api_lists ( $api_key )
    {
        $all_lists = array();

        $cm = new LI_Campaign_Monitor($api_key);
        $clients = $cm->call('clients', 'GET');

        if ( count($clients['response']) )
        {
            foreach ( $clients['response'] as $client )
            {
                $lists = array();
                $lists = $cm->call('clients/' . $client['ClientID'] . '/lists', 'GET');

                if ( count($lists['response']) )
                {
                    foreach ( $lists['response'] as $list )
                    {
                        $list['ClientName'] = $client['Name'];
                        array_push($all_lists, $list);
                    }
                }
            }
        }

        return $all_lists;
    }

    /**
     * Use MailChimp API key to try to grab corresponding user profile to check validity of key
     *
     * @param string
     * @return bool    
     */
    function li_check_invalid_api_key ( $api_key )
    {
        $cm = new LI_Campaign_Monitor($api_key);
        $test = $cm->call('clients', 'GET');

        if ( $test['code'] >= 400 )
        {
            $invalid_key = TRUE;
        }
        else
            $invalid_key = FALSE;

        return $invalid_key;
    } 
}

?>
