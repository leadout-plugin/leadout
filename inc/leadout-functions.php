<?php

if ( !defined('LEADOUT_PLUGIN_VERSION') ) 
{
    header('HTTP/1.0 403 Forbidden');
    die;
}

/**
 * Looks for a GET/POST value and echos if present. If nothing is set, echos blank
 *
 * @param   string
 * @return  null
 */
function print_submission_val ( $url_param ) 
{
    if ( isset($_GET[$url_param]) ) 
    {
        return $_GET[$url_param];
    }

    if ( isset($_POST[$url_param]) )
    {
        return $_POST[$url_param];
    }

    return '';
}

/**
 * Updates an option in the multi-dimensional option array
 *
 * @param   string   $option        option_name in wp_options
 * @param   string   $option_key    key for array
 * @param   string   $option        new value for array
 *
 * @return  bool            True if option value has changed, false if not or if update failed.
 */
function leadout_update_option ( $option, $option_key, $new_value ) 
{
    $options_array = get_option($option);

    if ( isset($options_array[$option_key]) )
    {
        if ( $options_array[$option_key] == $new_value )
            return false; // Don't update an option if it already is set to the value
    }

    if ( !is_array( $options_array ) ) {
        $options_array = array();
    }

    $options_array[$option_key] = $new_value;
    update_option($option, $options_array);

    $options_array = get_option($option);
    return update_option($option, $options_array);
}

/**
 * Prints a number with a singular or plural label depending on number
 *
 * @param   int
 * @param   string
 * @param   string
 * @return  string 
 */
function leadout_single_plural_label ( $number, $singular_label, $plural_label ) 
{
    //Set number = 0 when the variable is blank
    $number = ( !is_numeric($number) ? 0 : $number );

    return ( $number != 1 ? $number . " $plural_label" : $number . " $singular_label" );
}

/**
 * Gets the total number of contacts, comments and subscribers for above the table
 */
function get_total_contacts ()
{
    global $wpdb;

    if ( ! isset($wpdb->li_leads) )
        return 0;

    $q = "
        SELECT 
            COUNT(DISTINCT hashkey) AS total_contacts
        FROM 
            $wpdb->li_leads
        WHERE
            lead_email != '' AND lead_deleted = 0 AND hashkey != ''";

    $total_contacts = $wpdb->get_var($q);
    return $total_contacts;
}

/**
 * Logs a debug statement to /wp-content/debug.log
 *
 * @param   string
 */
function leadout_log_debug ( $message )
{
    if ( WP_DEBUG === TRUE )
    {
        if ( is_array($message) || is_object($message) )
            error_log(print_r($message, TRUE));
        else 
            error_log($message);
    }
}

/**
 * Deletes an element or elements from an array
 *
 * @param   array
 * @param   wildcard
 * @return  array
 */
function leadout_array_delete ( $array, $element )
{
    if ( !is_array($element) )
        $element = array($element);

    return array_diff($array, $element);
}

/**
 * Deletes an element or elements from an array
 *
 * @param   array
 * @param   wildcard
 * @return  array
 */
function leadout_get_value_by_key ( $key_value, $array )
{
    foreach ( $array as $key => $value )
    {
        if ( is_array($value) && $value['label'] == $key_value )
            return $value['value'];
    }

    return null;
}

/** 
 * Data recovery algorithm for 0.7.2 upgrade
 *
 */
function leadout_recover_contact_data ()
{
    global $wpdb;

    $q = $wpdb->prepare("SELECT * FROM $wpdb->li_submissions AS s LEFT JOIN $wpdb->li_leads AS l ON s.lead_hashkey = l.hashkey WHERE l.hashkey IS NULL AND s.form_fields LIKE '%%%s%%' AND s.form_fields LIKE '%%%s%%' AND form_deleted = 0", '@', '.');
    $submissions = $wpdb->get_results($q);

    if ( count($submissions) )
    {
        foreach ( $submissions as $submission )
        {
            $json = json_decode(stripslashes($submission->form_fields), TRUE);

            if ( count($json) )
            {
                foreach ( $json as $object )
                {
                    if ( strstr($object['value'], '@') && strstr($object['value'], '@') && strlen($object['value']) <= 254 )
                    {
                        // check to see if the contact exists and if it does, skip the data recovery
                        $q = $wpdb->prepare("SELECT lead_email FROM $wpdb->li_leads WHERE lead_email = %s AND lead_deleted = 0", $object['value']); // @HERE
                        $exists = $wpdb->get_var($q);

                        if ( $exists )
                            continue;

                        // get the original data
                        $q = $wpdb->prepare("SELECT pageview_date, pageview_source FROM $wpdb->li_pageviews WHERE lead_hashkey = %s AND pageview_deleted = 0 ORDER BY pageview_date ASC LIMIT 1", $submission->lead_hashkey);
                        $first_pageview = $wpdb->get_row($q);

                        // recreate the contact
                        $q = $wpdb->prepare("INSERT INTO $wpdb->li_leads ( lead_date, hashkey, lead_source, lead_email ) VALUES ( %s, %s, %s, %s )",
                            ( $first_pageview->pageview_date ? $first_pageview->pageview_date : $submission->form_date), 
                            $submission->lead_hashkey,
                            ( $first_pageview->pageview_source ? $first_pageview->pageview_source : ''),
                            $object['value']
                        );

                        $wpdb->query($q);
                    }
                }
            }
        }
    }

    leadout_update_option('leadin_options', 'data_recovered', 1);
}

/** 
 * Algorithm to set deleted contacts flag for 0.8.3 upgrade
 *
 */
function leadout_delete_flag_fix ()
{
    global $wpdb;

    $q = $wpdb->prepare("SELECT lead_email, COUNT(hashkey) c FROM $wpdb->li_leads WHERE lead_email != '' AND lead_deleted = 0 GROUP BY lead_email HAVING c > 1", '');
    $duplicates = $wpdb->get_results($q);

    if ( count($duplicates) )
    {
        foreach ( $duplicates as $duplicate )
        {
            $q = $wpdb->prepare("SELECT lead_email, hashkey, merged_hashkeys FROM $wpdb->li_leads WHERE lead_email = %s AND lead_deleted = 0 ORDER BY lead_date DESC", $duplicate->lead_email);
            $existing_contacts = $wpdb->get_results($q);

            $newest = $existing_contacts[0];
 
            // Setup the string for the existing hashkeys
            $existing_contact_hashkeys = $newest->merged_hashkeys;
            if ( $newest->merged_hashkeys && count($existing_contacts) )
                $existing_contact_hashkeys .= ',';

            // Do some merging if the email exists already in the contact table
            if ( count($existing_contacts) )
            {
                for ( $i = 0; $i < count($existing_contacts); $i++ )
                {
                    // Start with the existing contact's hashkeys and create a string containg comma-deliminated hashes
                    $existing_contact_hashkeys .= "'" . $existing_contacts[$i]->hashkey . "'";

                    // Add any of those existing contact row's merged hashkeys
                    if ( $existing_contacts[$i]->merged_hashkeys )
                        $existing_contact_hashkeys .= "," . $existing_contacts[$i]->merged_hashkeys;

                    // Add a comma delimiter 
                    if ( $i != count($existing_contacts)-1 )
                        $existing_contact_hashkeys .= ",";
                }
            }

            // Remove duplicates from the array and original hashkey just in case
            $existing_contact_hashkeys = leadout_array_delete(array_unique(explode(',', $existing_contact_hashkeys)), "'" . $newest->hashkey . "'");

            // Safety precaution - trim any trailing commas
            $existing_contact_hashkey_string = rtrim(implode(',', $existing_contact_hashkeys), ',');

            if ( $existing_contact_hashkey_string )
            {
                // Set the merged hashkeys with the fixed merged hashkey values
                $q = $wpdb->prepare("UPDATE $wpdb->li_leads SET merged_hashkeys = %s WHERE hashkey = %s", $existing_contact_hashkey_string, $newest->hashkey);
                $wpdb->query($q);

                // "Delete" all the old contacts
                $q = $wpdb->prepare("UPDATE $wpdb->li_leads SET merged_hashkeys = '', lead_deleted = 1 WHERE hashkey IN ( $existing_contact_hashkey_string )", '');
                $wpdb->query($q);

                // Set all the pageviews and submissions to the new hashkey just in case
                $q = $wpdb->prepare("UPDATE $wpdb->li_pageviews SET lead_hashkey = %s WHERE lead_hashkey IN ( $existing_contact_hashkey_string )", $newest->hashkey);
                $wpdb->query($q);

                // Update all the previous submissions to the new hashkey just in case
                $q = $wpdb->prepare("UPDATE $wpdb->li_submissions SET lead_hashkey = %s WHERE lead_hashkey IN ( $existing_contact_hashkey_string )", $newest->hashkey);
                $wpdb->query($q);
            }
        }
    }

    leadout_update_option('leadin_options', 'delete_flags_fixed', 1);
}

/** 
 * Sets the default lists for V2.0.0 and converts all existing statuses to tag format
 *
 */
function leadout_convert_statuses_to_tags ( )
{
    global $wpdb;

    $blog_ids = array();
    if ( is_multisite() )
    {       
        $blog_id = (Object)Null;
        $blog_id->blog_id = $wpdb->blogid;
        array_push($blog_ids, $blog_id);
    }
    else
    {
        $blog_id = (Object)Null;
        $blog_id->blog_id = 0;
        array_push($blog_ids, $blog_id);
    }

    foreach ( $blog_ids as $blog )
    {
        if ( is_multisite() )
        {
            $q = $wpdb->prepare("SELECT COUNT(TABLE_NAME) FROM information_schema.tables WHERE TABLE_NAME = $wpdb->li_leads LIMIT 1");
            $leadout_tables_exist = $wpdb->get_var($q);

            if ( ! $leadout_tables_exist )
            {
                leadout_db_install();
            }
        }

        // Get all the contacts from li_leads
        $q = $wpdb->prepare("SELECT lead_id, lead_status, hashkey FROM li_leads WHERE lead_status != 'contact' AND lead_email != '' AND lead_deleted = 0 AND blog_id = %d", $blog->blog_id);
        $contacts = $wpdb->get_results($q);

        // Check if there are any subscribers in the li_leads table and build the list if any exist
        $subscriber_exists = FALSE;
        foreach ( $contacts as $contact )
        {
            if ( $contact->lead_status == 'subscribe' )
            {
                $subscriber_exists = TRUE;
                break;
            }
        }

        // Check if LeadOut Subscribe is activated
        if ( ! $subscriber_exists )
        {
            if ( WPLeadOut::is_power_up_active('subscribe_widget') )
                $subscriber_exists = TRUE;
        }

        $existing_synced_lists = array();
        
        // Check to see if the mailchimp power-up is active and add the existing synced list for serialization
        $mailchimp_options = get_option('leadin_mls_options');
        if ( $mailchimp_options['li_mls_subscribers_to_list'] )
        {
            $leadout_mailchimp = new WPMailChimpConnect(TRUE);
            $leadout_mailchimp->admin_init();
            $lists = $leadout_mailchimp->admin->li_get_lists();

            if ( count($lists) )
            {
                foreach ( $lists as $list )
                {
                    if ( $list->id == $mailchimp_options['li_mls_subscribers_to_list'] )
                    {
                        array_push($existing_synced_lists,
                            array(
                                'esp' => 'mailchimp',
                                'list_id' => $list->id,
                                'list_name' => $list->name
                            )
                        );

                        break;
                    }
                }
            }
        }

        // Check to see if the constant contact power-up is active and add the existing synced list for serialization
        $constant_contact_options = get_option('leadin_cc_options');
        if ( $constant_contact_options['li_cc_subscribers_to_list'] )
        {
            $leadout_constant_contact = new WPConstantContactConnect(TRUE);
            $leadout_constant_contact->admin_init();
            $lists = $leadout_constant_contact->admin->li_get_lists();

            if ( count($lists) )
            {
                foreach ( $lists as $list )
                {
                    if ( $list->id == str_replace('@', '%40', $constant_contact_options['li_cc_subscribers_to_list']) ) 
                    {
                        array_push($existing_synced_lists,
                            array(
                                'esp' => 'constant_contact',
                                'list_id' => end(explode('/', $list->id)), // Changed the list_id for constant contact to just store the list integer in 2.0
                                'list_name' => $list->name
                            )
                        );

                        break;
                    }
                }
            }
        }

        unset($leadout_constant_contact);
        unset($leadout_mailchimp);

        // Create all the default comment lists (Commenters, Leads, Contacted, Customers). Figures out if it should add the subscriber list and puts the lists in the correct order
        $q = "
            INSERT INTO $wpdb->li_tags 
                ( tag_text, tag_slug, tag_form_selectors, tag_synced_lists, tag_order ) 
            VALUES " .
                ( $subscriber_exists ? "('Subscribers', 'subscribers', '.vex-dialog-form', " . ( count($existing_synced_lists) ? $wpdb->prepare('%s', serialize($existing_synced_lists)) : "''" ) . ", 1 ), " : "" ) .
                " ('Commenters', 'commenters', '#commentform', '', " . ( $subscriber_exists ? "2" : "1" ) . "),
                ('Leads', 'leads', '', '', " . ( $subscriber_exists ? "3" : "2" ) . "),
                ('Contacted', 'contacted', '', '', " . ( $subscriber_exists ? "4" : "3" ) . "),
                ('Customers', 'customers', '', '', " . ( $subscriber_exists ? "5" : "4" ) . ")";

        $wpdb->query($q);

        $tags = $wpdb->get_results("SELECT tag_id, tag_slug FROM $wpdb->li_tags WHERE tag_slug IN ( 'commenters', 'leads', 'contacted', 'customers', 'subscribers' )");
        foreach ( $tags as $tag )
            ${$tag->tag_slug . '_tag_id'} = $tag->tag_id;

        $insert_values = '';
        foreach ( $contacts as $contact )
        {
            switch ( $contact->lead_status )
            {
                case 'comment' :
                    $tag_id = $commenters_tag_id;
                break;

                case 'lead' :
                    $tag_id = $leads_tag_id;
                break;

                case 'contacted' :
                    $tag_id = $contacted_tag_id;
                break;

                case 'customer' :
                    $tag_id = $customers_tag_id;
                break;

                case 'subscribe' :
                    $tag_id = $subscribers_tag_id;
                break;
            }

            $insert_values .= '(' . $tag_id . ', "' . $contact->hashkey . '" ),';
        }

        $q = "INSERT INTO $wpdb->li_tag_relationships ( tag_id, contact_hashkey ) VALUES " . rtrim($insert_values, ',');
        $wpdb->query($q);

        if ( is_multisite() )
        {
           $q = $wpdb->prepare("INSERT $wpdb->li_leads SELECT * FROM li_leads WHERE li_leads.blog_id = %d", $blog->blog_id);
           $wpdb->query($q);

           $q = $wpdb->prepare("INSERT $wpdb->li_pageviews SELECT * FROM li_pageviews WHERE li_pageviews.blog_id = %d", $blog->blog_id);
           $wpdb->query($q);

           $q = $wpdb->prepare("INSERT $wpdb->li_submissions SELECT * FROM li_submissions WHERE li_submissions.blog_id = %d", $blog->blog_id);
           $wpdb->query($q);
        }
    }

    leadout_update_option('leadin_options', 'converted_to_tags', 1);
}


/** 
 * Retroactively add the names to contacts based on past form submissions for 2.2.3 upgrade
 *
 */
function leadout_set_names_retroactively ( )
{
    global $wpdb;

    $q = "
        SELECT 
            ls_1.* 
        FROM 
            li_submissions ls_1
        INNER JOIN
        (
            SELECT 
                MAX(form_date) max_form_date, lead_hashkey
            FROM 
                li_submissions
            WHERE 
                LOWER(form_fields) LIKE '%name%'
                AND form_deleted = 0
            GROUP BY lead_hashkey
        ) ls_2
        ON 
            ls_1.lead_hashkey = ls_2.lead_hashkey AND 
            ls_1.form_date = ls_2.max_form_date
        ORDER BY
            ls_1.form_date DESC";

    $submissions = $wpdb->get_results($q);

    if ( count($submissions) )
    {
        foreach ( $submissions as $submission )
        {
            $contact_first_name = '';
            $contact_last_name  = '';
            $fields = json_decode(stripslashes($submission->form_fields), TRUE);
            if ( count($fields) )
            {
                foreach ( $fields as $key => $field )
                {
                    $lower_label_text = strtolower($field['label']);
                    if ( $lower_label_text == 'first' || $lower_label_text == 'first name' || $lower_label_text == 'name' || $lower_label_text == 'your name' || $lower_label_text == 'your first name' )
                        $contact_first_name = $field['value'];

                    if ( $lower_label_text == 'last' || $lower_label_text == 'last name' || $lower_label_text == 'your last name' || $lower_label_text == 'surname' )
                        $contact_last_name = $field['value'];
                }
            }

            if ( $contact_first_name || $contact_last_name )
            {
                $q = $wpdb->prepare("UPDATE $wpdb->li_leads SET lead_first_name = %s, lead_last_name = %s WHERE hashkey = %s", $contact_first_name, $contact_last_name, $submission->lead_hashkey);
                $wpdb->query($q);
            }
        }
    }

    leadout_update_option('leadin_options', 'names_added_to_contacts', 1);
}

/**
 * Sorts the powerups into a predefined order in leadin.php line 416
 *
 * @param   array
 * @param   array
 * @return  array
 */
function leadout_sort_power_ups ( $power_ups, $ordered_power_ups ) 
{ 
    $ordered = array();
    $i = 0;
    foreach ( $ordered_power_ups as $key )
    {
        if ( in_array($key, $power_ups) )
        {
            array_push($ordered, $key);
            $i++;
        }
    }

    return $ordered;
}

/**
 * Encodes special HTML quote characters into utf-8 safe entities
 *
 * @param   string
 * @return  string
 */
function leadout_encode_quotes ( $string ) 
{ 
    $string = str_replace(array("’", "‘", '&#039;', '“', '”'), array("'", "'", "'", '"', '"'), $string);
    return $string;
}

/**
 * Converts all carriage returns into HTML line breaks 
 *
 * @param   string
 * @return  string
 */
function leadout_html_line_breaks ( $string ) 
{
    return stripslashes(str_replace('\n', '<br>', $string));
}

/**
 * Strip url get parameters off a url and return the base url
 *
 * @param   string
 * @return  string
 */
function leadout_strip_params_from_url ( $url ) 
{ 
    $url_parts = parse_url($url);
    $base_url = ( isset($url_parts['host']) ? 'http://' . rtrim($url_parts['host'], '/') : '' ); 
    $base_url .= ( isset($url_parts['path']) ? '/' . ltrim($url_parts['path'], '/') : '' ); 
    
    if ( isset($url_parts['path'] ) )
        ltrim($url_parts['path'], '/');

    $base_url = urldecode(ltrim($base_url, '/'));

    return strtolower($base_url);
}

/**
 * Search an object by for a value and return the associated index key
 *
 * @param   object 
 * @param   string
 * @param   string
 * @return  key for array index if present, false otherwise
 */
function leadout_search_object_by_value ( $haystack, $needle, $search_key )
{
   foreach ( $haystack as $key => $value )
   {
      if ( $value->$search_key === $needle )
         return $key;
   }

   return FALSE;
}

/**
 * Check if date is a weekend day
 *
 * @param   string
 * @return  bool
 */
function leadout_is_weekend ( $date )
{
    return (date('N', strtotime($date)) >= 6);
}

/**
 * Tie a tag to a contact in li_tag_relationships
 *
 * @param   int 
 * @param   int
 * @param   int
 * @return  bool    successful insert
 */
function leadout_apply_tag_to_contact ( $tag_id, $contact_hashkey, $form_hashkey )
{
    global $wpdb;

    $q = $wpdb->prepare("SELECT tag_id FROM $wpdb->li_tag_relationships WHERE tag_id = %d AND contact_hashkey = %s", $tag_id, $contact_hashkey);
    $exists = $wpdb->get_var($q);

    if ( ! $exists )
    {
        $q = $wpdb->prepare("INSERT INTO $wpdb->li_tag_relationships ( tag_id, contact_hashkey, form_hashkey ) VALUES ( %d, %s, %s )", $tag_id, $contact_hashkey, $form_hashkey);
        return $wpdb->query($q);
    }
}


/**
 * Check multidimensional arrray for an existing value
 *
 * @param   string 
 * @param   array
 * @return  bool
 */
function leadout_in_array_deep ( $needle, $haystack ) 
{
    if ( in_array($needle, $haystack) )
        return TRUE;

    foreach ( $haystack as $element ) 
    {
        if ( is_array($element) && leadout_in_array_deep($needle, $element) )
            return TRUE;
    }

    return FALSE;
}

/**
 * Check multidimensional arrray for an existing value
 *
 * @param   string      needle 
 * @param   array       haystack
 * @return  string      key if found, null if not
 */
function leadout_array_search_deep ( $needle, $array, $index ) 
{
    foreach ( $array as $key => $val ) 
    {
        if ( $val[$index] == $needle )
            return $key;
    }

   return NULL;
}

/**
 * Creates a list of filtered contacts into a comma separated string of hashkeys
 * 
 * @param object
 * @return string    sorted array
 */
function leadout_merge_filtered_contacts ( $filtered_contacts, $all_contacts = array() )
{
    if ( ! count($all_contacts) )
        return $filtered_contacts;

    if ( count($filtered_contacts) )
    {
        foreach ( $all_contacts as $key => $contact )
        {
            if ( ! leadout_in_array_deep($contact['lead_hashkey'], $filtered_contacts) )
                unset($all_contacts[$key]);
        }

        return $all_contacts;
    }
    else
        return FALSE;
}

/**
 * Creates a list of filtered contacts into a comma separated string of hashkeys
 * 
 * @param object
 * @return string    sorted array
 */
function leadout_explode_filtered_contacts ( $contacts )
{
    if ( count($contacts) )
    {
        $contacts = array_values($contacts);

        $hashkeys = '';
        for ( $i = 0; $i < count($contacts); $i++ )
            $hashkeys .= "'" . $contacts[$i]['lead_hashkey'] . "'" . ( $i != (count($contacts) - 1) ? ', ' : '' );

        return $hashkeys;
    }
    else
        return FALSE;
}

/**
 * Sets the wpdb tables to the current blog
 * 
 */
function leadout_set_wpdb_tables ()
{
    global $wpdb;

    $wpdb->li_submissions       = ( is_multisite() ? $wpdb->prefix . 'li_submissions' : 'li_submissions' );
    $wpdb->li_pageviews         = ( is_multisite() ? $wpdb->prefix . 'li_pageviews' : 'li_pageviews' );
    $wpdb->li_leads             = ( is_multisite() ? $wpdb->prefix . 'li_leads' : 'li_leads' );
    $wpdb->li_tags              = ( is_multisite() ? $wpdb->prefix . 'li_tags' : 'li_tags' );
    $wpdb->li_tag_relationships = ( is_multisite() ? $wpdb->prefix . 'li_tag_relationships' : 'li_tag_relationships' );
}

/**
 * Calculates the hour difference between MySQL timestamps and the current local WordPress time
 * 
 */
function leadout_set_mysql_timezone_offset ()
{
    global $wpdb;

    $mysql_timestamp = $wpdb->get_var("SELECT CURRENT_TIMESTAMP");
    $diff = strtotime($mysql_timestamp) - strtotime(current_time('mysql'));
    $hours = $diff / (60 * 60);

    $wpdb->db_hour_offset = $hours;
}


/**
 * Gets current URL with parameters
 * 
 */
function leadout_get_current_url ( )
{
    return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}


/**
 * Returns the user role for the current user
 * 
 */
function leadout_get_user_role ()
{
    global $current_user;

    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);

    return $user_role;
}

/**
 * Checks whether or not to ignore the logged in user in the LeadOut tracking scripts
 * 
 */
function leadout_ignore_logged_in_user ()
{
    // ignore logged in users if defined in settings
    if ( is_user_logged_in() )
    {
        if ( array_key_exists('li_do_not_track_' . leadout_get_user_role(), get_option('leadin_options')) )
            return TRUE;
        else
            return FALSE;
    }
    else
        return FALSE;
}

function leadout_safe_social_profile_url ( $url )
{
    $url = str_replace('∖', '/', $url);
    return $url;
}

function leadout_check_missing_options ( $options )
{
    $default_options = array(
        'li_installed'              => 1,
        'leadout_version'            => LEADOUT_PLUGIN_VERSION,
        'li_db_version'             => LEADOUT_DB_VERSION,
        'li_email'                  => get_bloginfo('admin_email'),
        'li_updates_subscription'   => 1,
        'onboarding_step'           => 1,
        'onboarding_complete'       => 0,
        'ignore_settings_popup'     => 0,
        'data_recovered'            => 1,
        'delete_flags_fixed'        => 1,
        'converted_to_tags'         => 1,
        'names_added_to_contacts'   => 1
    );

    // Add the Pro flag if this is a pro installation
    if ( ( defined('LEADOUT_UTM_SOURCE') && LEADOUT_UTM_SOURCE != 'leadin%20repo%20plugin') || ! defined('LEADOUT_UTM_SOURCE') )
        $default_options['pro'] = 1;

    $update_option = FALSE;
    if ( count($options) && is_array($options) )
    {
        foreach ( $default_options as $key => $value )
        {
            if ( ! array_key_exists($key, $options) )
            {
                $options[$key] = $value;
                $update_option = TRUE;
            }
        }
    }

    if ( $update_option )
        update_option('leadin_options', $options);

    return $options;
}

/**
 * Checks to see if an installation is LeadOut Pro enabled
 * 
 * @return bool
 */
function leadout_check_pro_user ( )
{
    $options = get_option('leadin_options');
    if ( isset($options['pro']) && $options['pro'])
        return TRUE;
    else
        return FALSE;
}

/**
 * Checks the first entry in the pageviews table 
 *
 */
function leadout_check_first_pageview_data ( )
{
    global $wpdb;

    $q = "SELECT pageview_date FROM $wpdb->li_pageviews ORDER BY pageview_date ASC LIMIT 1";
    $date = $wpdb->get_var($q);

    if ( $date )
    {
        if ( strtotime($date) < strtotime('-30 days') )
            return TRUE;
        else
            return FALSE;
    }
    else
    {
       return FALSE;
    }
}

function leadout_check_tables_exist ()
{
    global $wpdb;

    $q = "SELECT table_name FROM information_schema.tables WHERE table_name = '" . $wpdb->li_leads . "' OR table_name = '" . $wpdb->li_submissions . "' OR table_name = '" . $wpdb->li_pageviews . "' OR table_name = '" . $wpdb->li_tags . "' OR table_name = '" . $wpdb->li_tag_relationships . "'";
    $li_tables = $wpdb->get_results($q);

    return $li_tables;
}

?>