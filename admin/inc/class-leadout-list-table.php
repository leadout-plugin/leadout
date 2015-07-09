<?php

//=============================================
// Include Needed Files
//=============================================

if ( !class_exists('WP_List_Table') )
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

require_once(LEADIN_PLUGIN_DIR . '/inc/leadout-functions.php');

//=============================================
// LI_List_Table Class
//=============================================
class LI_List_Table extends WP_List_Table {
    
    /**
     * Variables
     */
    public $data = array();
    private $current_view;
    public $view_label;
    private $view_count;
    private $views;
    private $total_contacts;
    private $total_filtered;
    public $tags;

    /**
     * Class constructor
     */
    function __construct () 
    {
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'contact',
            'plural'    => 'contacts',
            'ajax'      => false
        ));
    }

    /**
     * Prints text for no rows found in table
     */
    function no_items () 
    {
      _e('No contacts found.');
    }
    
    /**
     * Prints values for columns for which no column function has been defined
     *
     * @param   object
     * @param   string
     * @return  *           item value's type
     */
    function column_default ( $item, $column_name )
    {
        switch ( $column_name ) 
        {
            case 'email':

            case 'date':
                return $item[$column_name];
            case 'last_visit':
                return $item[$column_name];
            case 'submissions':
                return $item[$column_name];
            case 'pageviews':
                return $item[$column_name];
            case 'visits':
                return $item[$column_name];
            case 'source':
                return $item[$column_name];
            default:
                return print_r($item,true);
        }
    }
    
    /**
     * Prints text for email column
     *
     * @param   object
     * @return  string
     */
    function column_email ( $item )
    {
        //Build row actions
        $actions = array(
            'view'    => sprintf('<div style="clear:both; padding-top: 4px;"></div><a href="?page=%s&action=%s&lead=%s">View</a>',$_REQUEST['page'],'view',$item['ID']),
            'delete'  => sprintf('<a href="?page=%s&action=%s&lead=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID'])
        );
        
        //Return the title contents
        return sprintf('%1$s<br/>%2$s',
            /*$1%s*/ $item['email'],
            /*$2%s*/ $this->row_actions($actions)
        );
    }
    
    /**
     * Prints checkbox column
     *
     * @param   object
     * @return  string
     */
    function column_cb ( $item )
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],
            /*$2%s*/ $item['ID']
        );
    }
    
    /**
     * Get all the columns for the list table
     *
     * @param   object
     * @param   string
     * @return  array           associative array of columns
     */
    function get_columns () 
    {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'email'         => 'Email',
            'source'        => 'Original source',
            'visits'        => 'Visits',
            'pageviews'     => 'Page views',
            'submissions'   => 'Forms',
            'last_visit'    => 'Last visit',
            'date'          => 'Created on'
        );
        return $columns;
    }
    
    /**
     * Defines sortable columns for table
     *
     * @param   object
     * @param   string
     * @return  array           associative array of columns
     */
    function get_sortable_columns () 
    {
        $sortable_columns = array(
            'email'         => array('email',false), // presorted if true
            'pageviews'     => array('pageviews',false),
            'visits'        => array('visits',false),
            'submissions'   => array('submissions',false),
            'date'          => array('date',true),
            'last_visit'    => array('last_visit',false),
            'source'        => array('source',false)
        );
        return $sortable_columns;
    }
    
    /**
     * Get the bulk actions
     *
     * @return  array           associative array of actions
     */
    function get_bulk_actions ()
    {
        $contact_type   = strtolower($this->view_label);
        $filtered       =  ( isset($_GET['filter_action']) ? 'filtered ' : '' );
        $actions = array(
            'add_tag_to_all'             => 'Add a tag to every one tagged "' . $filtered . $contact_type . '"',
            'add_tag_to_selected'        => 'Add a tag to selected "' . $contact_type,
            'remove_tag_from_all'        => 'Remove a tag from every one tagged "' . $filtered . $contact_type . '"',
            'remove_tag_from_selected'   => 'Remove a tag from selected "' . $contact_type . '"',
            'delete_all'                 => 'Delete every one tagged "' . $contact_type . '" from LeadOut',
            'delete_selected'            => 'Delete selected ' . $contact_type . ' from LeadOut'
        );

        return $actions;
    }
    
    /**
     * Process bulk actions for deleting
     */
    function process_bulk_action ()
    {
        global $wpdb;

        $ids_for_action = '';
        $hashes_for_action = '';

        if ( strstr($this->current_action(), 'delete') )
        {
            if ( 'delete_selected' === $this->current_action() )
            {
                for ( $i = 0; $i < count($_GET['contact']); $i++ )
                {
                   $ids_for_action .= $_GET['contact'][$i];;

                   if ( $i != (count($_GET['contact'])-1) )
                        $ids_for_action .= ',';
                }
            }
            else if ( 'delete_all' === $this->current_action() )
            {
                $contacts = $this->get_contacts();
                foreach ( $contacts as $contact )
                    $ids_for_action .= $contact['ID'] . ',';

                $ids_for_action = rtrim($ids_for_action, ',');
            }
            else // default case for when it's not actually processing a bulk action
                return FALSE;

            $q = $wpdb->prepare("SELECT hashkey FROM $wpdb->li_leads WHERE lead_id IN ( " . $ids_for_action . " ) ", "");
            $hashes = $wpdb->get_results($q);

            if ( count($hashes) )
            {
                foreach ( $hashes as $hash )
                    $hashes_for_action .= "'" . $hash->hashkey . "',";

                $hashes_for_action = rtrim($hashes_for_action, ',');

                $q = $wpdb->prepare("UPDATE $wpdb->li_pageviews SET pageview_deleted  = 1 WHERE lead_hashkey IN (" . $hashes_for_action . ") ", "");
                $delete_pageviews = $wpdb->query($q);

                $q = $wpdb->prepare("UPDATE $wpdb->li_submissions SET form_deleted  = 1 WHERE lead_hashkey IN (" . $hashes_for_action . ") ", "");
                $delete_submissions = $wpdb->query($q);

                $q = $wpdb->prepare("UPDATE $wpdb->li_leads SET lead_deleted  = 1 WHERE lead_id IN (" . $ids_for_action . ") ", "");
                $delete_leads = $wpdb->query($q);

                $q = $wpdb->prepare("UPDATE $wpdb->li_tag_relationships SET tag_relationship_deleted = 1 WHERE contact_hashkey IN (" . $hashes_for_action . ") ", "");
                $delete_tags = $wpdb->query($q);
            }
        }
        
        if ( isset($_POST['bulk_edit_tags']) )
        {
            $q = $wpdb->prepare("SELECT tag_id FROM $wpdb->li_tags WHERE tag_slug = %s ", $_POST['bulk_selected_tag']);
            $tag_id = $wpdb->get_var($q);

            if ( empty($_POST['leadout_selected_contacts']) )
            {
                $contacts = $this->get_contacts();
                foreach ( $contacts as $contact )
                    $ids_for_action .= $contact['ID'] . ',';

                $ids_for_action = rtrim($ids_for_action, ',');
            }
            else
                $ids_for_action = $_POST['leadout_selected_contacts'];

            $q = $wpdb->prepare("
                SELECT 
                    l.hashkey, l.lead_email,
                    ( SELECT ltr.tag_id FROM $wpdb->li_tag_relationships ltr WHERE ltr.tag_id = %d AND ltr.contact_hashkey = l.hashkey GROUP BY ltr.contact_hashkey ) AS tag_set 
                FROM 
                    $wpdb->li_leads l
                WHERE 
                    l.lead_id IN ( " . $ids_for_action . " ) AND l.lead_deleted = 0 GROUP BY l.lead_id", $tag_id);

            $contacts = $wpdb->get_results($q);

            $insert_values          = '';
            $contacts_to_update     = '';

            if ( count($contacts) )
            {
                foreach ( $contacts as $contact )
                {
                    if ( $contact->tag_set === NULL )
                       $insert_values .= '(' . $tag_id . ', "' . $contact->hashkey . '"),';
                    else
                        $contacts_to_update .= "'" . $contact->hashkey . "',";
                }
            }

            if ( $_POST['bulk_edit_tag_action'] == 'add_tag' )
            {
                if ( $insert_values )
                {
                    $q = "INSERT INTO $wpdb->li_tag_relationships ( tag_id, contact_hashkey ) VALUES " . rtrim($insert_values, ',');
                    $wpdb->query($q);
                }

                if ( $contacts_to_update )
                {
                    // update the relationships for the contacts that exist already making sure to set all the tag_relationship_deleted = 0
                    $q = $wpdb->prepare("UPDATE $wpdb->li_tag_relationships SET tag_relationship_deleted = 0 WHERE tag_id = %d AND contact_hashkey IN ( " . rtrim($contacts_to_update, ',')  . ") ", $tag_id);
                    $wpdb->query($q);
                }

                // Bulk push all the email addresses for the tag to the MailChimp API
                $tagger = new LI_Tag_Editor($tag_id);
                $tagger->push_contacts_to_tagged_list($tag_id);
            }
            else
            {
                if ( $contacts_to_update )
                {
                    // "Delete" the existing tags only
                    $q = $wpdb->prepare("UPDATE $wpdb->li_tag_relationships SET tag_relationship_deleted = 1 WHERE tag_id = %d AND contact_hashkey IN ( " . rtrim($contacts_to_update, ',')  . ") ", $tag_id);
                    $wpdb->query($q);
                }
            }
        }
    }

    /**
     * Get the leads for the contacts table based on $GET_['contact_type'] or $_GET['s'] (search)
     *
     * @return  array           associative array of all contacts
     */
    function get_contacts ()
    {
        /*** 
            == FILTER ARGS ==
            - filter_action (visited)      = visited a specific page url (filter_action) 
            - filter_action (submitted)    = submitted a form on specific page url (filter_action) 
            - filter_content               = content for filter_action
            - filter_form                  = selector id/class
            - num_pageviews                = visited at least #n pages
            - s                            = search query on lead_email/lead_source
        */

        global $wpdb;

        $mysql_search_filter        = '';
        $mysql_contact_type_filter  = '';
        $mysql_action_filter        = '';
        $filter_action_set          = FALSE;

        // search filter
        if ( isset($_GET['s']) )
        {
            $escaped_query = '';
            if ( $wp_version >= 4 )
                $escaped_query = $wpdb->esc_like($_GET['s']);
            else
                $escaped_query = like_escape($_GET['s']);

            $search_query = $_GET['s'];
            $mysql_search_filter = $wpdb->prepare(" AND ( l.lead_email LIKE '%%%s%%' OR l.lead_source LIKE '%%%s%%' ) ", $escaped_query, $escaped_query);
        }
        
        $filtered_contacts = array();

        // contact type filter
        if ( isset($_GET['contact_type']) )
        {
            // Query for the tag_id, then find all hashkeys with that tag ID tied to them. Use those hashkeys to modify the query
            $q = $wpdb->prepare("
                SELECT 
                    DISTINCT ltr.contact_hashkey as lead_hashkey 
                FROM 
                    $wpdb->li_tag_relationships ltr, $wpdb->li_tags lt 
                WHERE 
                    lt.tag_id = ltr.tag_id AND 
                    ltr.tag_relationship_deleted = 0 AND  
                    lt.tag_slug = %s GROUP BY ltr.contact_hashkey",  $_GET['contact_type']);

            $filtered_contacts = $wpdb->get_results($q, 'ARRAY_A');
            $num_contacts = count($filtered_contacts);
        }

        if ( isset($_GET['filter_action']) && $_GET['filter_action'] == 'visited' )
        {
            if ( isset($_GET['filter_content']) && $_GET['filter_content'] != 'any page' )
            {
                $q = $wpdb->prepare("SELECT lead_hashkey FROM $wpdb->li_pageviews WHERE pageview_title LIKE '%%%s%%' GROUP BY lead_hashkey",  htmlspecialchars(urldecode($_GET['filter_content'])));
                $filtered_contacts = leadout_merge_filtered_contacts($wpdb->get_results($q, 'ARRAY_A'), $filtered_contacts);
                $filter_action_set = TRUE;
            }
        }
        
        // filter for a form submitted on a specific page
        if ( isset($_GET['filter_action']) && $_GET['filter_action'] == 'submitted' )
        {
            $filter_form = '';
            if ( isset($_GET['filter_form']) && $_GET['filter_form'] && $_GET['filter_form'] != 'any form' )
            {
                $filter_form = str_replace(array('#', '.'), '', htmlspecialchars(urldecode($_GET['filter_form'])));
                $filter_form_query = $wpdb->prepare(" AND ( form_selector_id LIKE '%%%s%%' OR form_selector_classes LIKE '%%%s%%' )", $filter_form, $filter_form);
            }

            $q = $wpdb->prepare("SELECT lead_hashkey FROM $wpdb->li_submissions WHERE form_page_title LIKE '%%%s%%' ", ( $_GET['filter_content'] != 'any page' ? htmlspecialchars(urldecode($_GET['filter_content'])): '' ));
            $q .= ( $filter_form_query ? $filter_form_query : '' );
            $q .= " GROUP BY lead_hashkey";
            $filtered_contacts = leadout_merge_filtered_contacts($wpdb->get_results($q, 'ARRAY_A'), $filtered_contacts);
            $filter_action_set = TRUE;
        }        

        $filtered_hashkeys = leadout_explode_filtered_contacts($filtered_contacts);

        $mysql_action_filter = '';
        if ( $filter_action_set ) // If a filter action is set and there are no contacts, do a blank
            $mysql_action_filter = " AND l.hashkey IN ( " . ( $filtered_hashkeys ? $filtered_hashkeys : "''" ) . " ) ";
        else
            $mysql_action_filter = ( $filtered_hashkeys ? " AND l.hashkey IN ( " . $filtered_hashkeys . " ) " : '' ); // If a filter action isn't set, use the filtered hashkeys if they exist, else, don't include the statement

        // There's a filter and leads are in it
        if ( ( isset($_GET['contact_type']) && ( $num_contacts || ! $_GET['contact_type'] ) ) || ! isset($_GET['contact_type']) )
        {
            $q =  $wpdb->prepare("
                SELECT 
                    l.lead_id AS lead_id, 
                    LOWER(DATE_SUB(l.lead_date, INTERVAL %d HOUR)) AS lead_date, l.lead_ip, l.lead_source, l.lead_email, l.hashkey, l.lead_first_name, l.lead_last_name,
                    COUNT(DISTINCT s.form_id) AS lead_form_submissions,
                    COUNT(DISTINCT p.pageview_id) AS lead_pageviews,
                    LOWER(DATE_SUB(MAX(p.pageview_date), INTERVAL %d HOUR)) AS last_visit,
                    ( SELECT COUNT(DISTINCT pageview_id) FROM $wpdb->li_pageviews WHERE lead_hashkey = l.hashkey AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS visits,
                    ( SELECT MIN(pageview_source) AS pageview_source FROM $wpdb->li_pageviews WHERE lead_hashkey = l.hashkey AND pageview_session_start = 1 AND pageview_deleted = 0 ) AS pageview_source 
                FROM 
                    $wpdb->li_leads l
                LEFT JOIN $wpdb->li_submissions s ON l.hashkey = s.lead_hashkey
                LEFT JOIN $wpdb->li_pageviews p ON l.hashkey = p.lead_hashkey 
                WHERE l.lead_email != '' AND l.lead_deleted = 0 AND l.hashkey != '' ", $wpdb->db_hour_offset, $wpdb->db_hour_offset);

            $q .= $mysql_contact_type_filter;
            $q .= ( $mysql_search_filter ? $mysql_search_filter : "" );
            $q .= ( $mysql_action_filter ? $mysql_action_filter : "" );
            $q .=  " GROUP BY l.hashkey";
            $leads = $wpdb->get_results($q);
        }
        else
        {
            $leads = array();
        }

        $all_leads = array();

        $contact_count = 0;

        if ( count($leads) )
        {
            foreach ( $leads as $key => $lead ) 
            {
                // filter for number of page views and skipping lead if it doesn't meet the minimum
                if ( isset($_GET['filter_action']) && $_GET['filter_action'] == 'num_pageviews' )
                {
                    if ( $lead->lead_pageviews < $_GET['filter_content'] )
                        continue;
                }

                $url = leadout_strip_params_from_url($lead->lead_source);

                $redirect_url = '';
                if ( isset($_GET['contact_type']) || isset($_GET['filter_action']) || isset($_GET['filter_form']) || isset($_GET['filter_content']) || isset($_GET['num_pageviews']) || isset($_GET['s']) || isset($_GET['paged']) )
                    $redirect_url = urlencode(leadout_get_current_url());

                $lead_array = array(
                    'ID' => $lead->lead_id,
                    'hashkey' => $lead->hashkey,
                    'email' => sprintf('<a href="?page=%s&action=%s&lead=%s%s">' . "<img class='pull-left leadout-contact-avatar leadout-dynamic-avatar_" . substr($lead->lead_id, -1) . "' src='https://api.hubapi.com/socialintel/v1/avatars?email=" . $lead->lead_email . "' width='35' height='35' style='margin-top: 2px;'/> " . '</a>', $_REQUEST['page'], 'view', $lead->lead_id, ( $redirect_url ? '&redirect_to=' .  $redirect_url : '' )) .  sprintf('<a href="?page=%s&action=%s&lead=%s%s">%s' . $lead->lead_email . '</a>', $_REQUEST['page'], 'view', $lead->lead_id, ( $redirect_url ? '&redirect_to=' .  $redirect_url : '' ), ( strlen($lead->lead_first_name) || strlen($lead->lead_last_name)? '<b>' . $lead->lead_first_name . ' ' . $lead->lead_last_name . '</b><br>' : '' )),
                    'visits' => ( !isset($lead->visits) ? 1 : $lead->visits ),
                    'submissions' => $lead->lead_form_submissions,
                    'pageviews' => $lead->lead_pageviews,
                    'date' => date('Y-m-d g:ia', strtotime($lead->lead_date)),
                    'source' => ( $lead->pageview_source ? "<a title='Visit page' href='" . $lead->pageview_source . "' target='_blank'>" . leadout_strip_params_from_url($lead->pageview_source) . "</a>" : 'Direct' ),
                    'last_visit' => date('Y-m-d g:ia', strtotime($lead->last_visit)),
                    'source' => ( $lead->lead_source ? "<a title='Visit page' href='" . $lead->lead_source . "' target='_blank'>" . leadout_strip_params_from_url($lead->lead_source) . "</a>" : 'Direct' )
                );
                
                array_push($all_leads, $lead_array);
                $contact_count++;
            }
        }

        $this->total_filtered = count($all_leads);

        return $all_leads;
    }

    /**
     * Gets the total number of contacts, comments and subscribers for above the table
     */
    function get_total_contacts ()
    {
        global $wpdb;

        $q = "
            SELECT 
                COUNT(DISTINCT hashkey) AS total_contacts
            FROM 
                $wpdb->li_leads
            WHERE
                lead_email != '' AND lead_deleted = 0 AND hashkey != '' ";

        $total_contacts = $wpdb->get_var($q);
        
        return $total_contacts;
    }

    /**
     * Gets the current view based off $_GET['contact_type']
     *
     * @return  string
     */
    function get_view ()
    {
        $current_contact_type = ( !empty($_GET['contact_type']) ? html_entity_decode($_GET['contact_type']) : 'contacts' );
        return $current_contact_type;
    }

    /**
     * Gets the current action filter based off $_GET['contact_type']
     *
     * @return  string
     */
    function get_filters ()
    {
        $current_filters = array();

        $current_filters['contact_type'] = ( !empty($_GET['contact_type']) ? html_entity_decode($_GET['contact_type']) : 'all' );
        $current_filters['action'] = ( !empty($_GET['filter_action']) ? html_entity_decode($_GET['filter_action']) : 'all' );
        $current_filters['content'] = ( !empty($_GET['filter_content']) ? html_entity_decode($_GET['filter_content']) : 'all' );

        return $current_filters;
    }
    
    /**
     * Get the contact tags
     *
     * @return  string
     */
    function get_tags ()
    {
        global $wpdb;

        $q = "
            SELECT 
                lt.tag_text, lt.tag_slug, lt.tag_synced_lists, lt.tag_form_selectors, lt.tag_order, lt.tag_id,
                ( SELECT COUNT(DISTINCT contact_hashkey) FROM $wpdb->li_tag_relationships ltr, $wpdb->li_leads ll WHERE ltr.tag_id = lt.tag_id AND ltr.tag_relationship_deleted = 0 AND ltr.contact_hashkey != '' AND ll.hashkey = ltr.contact_hashkey AND ll.hashkey != '' AND ll.lead_deleted = 0 GROUP BY tag_id ) AS tag_count
            FROM 
                $wpdb->li_tags lt
            WHERE 
                lt.tag_deleted = 0
            ORDER BY lt.tag_order ASC";

        return $wpdb->get_results($q);
    }

    /**
     * Prints contacts menu next to contacts table
     */
    function views ()
    {
        $this->tags = stripslashes_deep($this->get_tags());

        $current = ( !empty($_GET['contact_type']) ? html_entity_decode($_GET['contact_type']) : 'all' );
        $all_params = array( 'contact_type', 's', 'paged', '_wpnonce', '_wpreferrer', '_wp_http_referer', 'action', 'action2', 'filter_form', 'filter_action', 'filter_content', 'contact');
        
        $all_url = remove_query_arg($all_params);

        $this->total_contacts = $this->get_total_contacts();

        

        echo "<ul class='leadout-contacts__type-picker'>";
            echo "<li><a href='$all_url' class='" . ( $current == 'all' ? 'current' :'' ) . "'><span class='icon-user'></span>" . $this->total_contacts .  " Total</a></li>";
        echo "</ul>";

        if ( $current == "all" ) {
            $this->view_label = "Contacts";
            $this->view_count = $this->total_contacts;
        }

        if ( empty( $this->tags ) ) {
            echo "<h3 class='leadout-contacts__tags-header'>No Tags</h3>";
        }
        else {
            echo "<h3 class='leadout-contacts__tags-header'>Tags</h3>";
        }

        echo "<ul class='leadout-contacts__type-picker'>";
            foreach ( $this->tags as $tag ) {
                
                if ( $current == $tag->tag_slug ) {
                    $currentTag = true;
                    $this->view_label = $tag->tag_text;
                    $this->view_count = $tag->tag_count;
                } else {
                    $currentTag = false;
                }

                echo "<li><a href='" . $all_url . "&contact_type=" . $tag->tag_slug . "' class='" . ( $currentTag ? 'current' :'' ) . "''><span class='icon-tag'></span>" . ( $tag->tag_count ? $tag->tag_count : '0' ) . " " . $tag->tag_text . "</a></li>";
            }
        echo "</ul>";

        echo "<a href='" . get_bloginfo('wpurl') . "/wp-admin/admin.php?page=leadout_tags" . "' class='button'>Manage tags</a>";
    }


    /**
     * Prints contacts filter above contacts table
     */
    function filters ()
    {
        $filters = $this->get_filters();

        ?>
            <form id="leadout-contacts-filter" class="leadout-contacts__filter" method="GET">
                
                <h3 class="leadout-contacts__filter-text">
                    
                    <span class="leadout-contacts__filter-count"><?php echo ( $this->total_filtered != $this->view_count ? '<span id="contact-count">' . $this->total_filtered . '</span>' . '/' : '' ) . '<span id="contact-count">' . ( $this->view_count ? $this->view_count : '0' ) . '</span>' . ' ' . strtolower($this->view_label); ?></span> who 
                    
                    <select class="select2" name="filter_action" id="filter_action" style="width:125px">
                        <option value="visited" <?php echo ( $filters['action']=='visited' ? 'selected' : '' ) ?> >viewed</option>
                        <option value="submitted" <?php echo ( $filters['action']=='submitted' ? 'selected' : '' ) ?> >submitted</option>
                    </select>

                    <span id="form-filter-input" <?php echo ( ! isset($_GET['filter_form']) || ( isset($_GET['filter_action']) && $_GET['filter_action'] != 'submitted' ) ? 'style="display: none;"' : ''); ?>>
                        <input type="hidden" name="filter_form" class="bigdrop" id="filter_form" style="width:250px" value="<?php echo ( isset($_GET['filter_form']) ? stripslashes($_GET['filter_form']) : '' ); ?>"/> on 
                    </span>

                    <input type="hidden" name="filter_content" class="bigdrop" id="filter_content" style="width:250px" value="<?php echo ( isset($_GET['filter_content']) ? stripslashes($_GET['filter_content']) : '' ); ?>"/>
                    
                    <input type="submit" name="" id="leadout-contacts-filter-button" class="button action" value="Apply">

                    <?php if ( isset($_GET['filter_action']) || isset($_GET['filter_content']) ) : ?>
                        <a href="<?php echo get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_contacts' . ( isset($_GET['contact_type']) ? '&contact_type=' . $_GET['contact_type'] : '' ); ?>" id="clear-filter">clear filter</a>
                    <?php endif; ?>

                </h3>

                <?php if ( isset($_GET['contact_type']) ) : ?>
                    <input type="hidden" name="contact_type" value="<?php echo $_GET['contact_type']; ?>"/>
                <?php endif; ?>

                <input type="hidden" name="page" value="leadout_contacts"/>
            </form>
        <?php
    }

    /**
     * Gets + prepares the contacts for the list table
     */
    function prepare_items ()
    {
        $per_page = 10;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
                
        $orderby = ( !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'last_visit' );
        $order = ( !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'desc' );

        usort($this->data, array($this, 'usort_reorder'));

        $current_page = $this->get_pagenum();
        $total_items = count($this->data);
        $this->data = array_slice($this->data, (($current_page-1)*$per_page), $per_page);

        $this->items = $this->data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }

    /**
     * Sorting function for usort
     * 
     * @param array
     * @param array
     * @return array    sorted array
     */
    function usort_reorder ( $a, $b ) 
    {
        $orderby = ( !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'last_visit' );
        $order = ( !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'desc' );

        // Timestamp columns need to be convereted to integers to sort correctly
        $val_a = ( $orderby == 'last_visit' || $orderby == 'date' ? strtotime($a[$orderby]) : $a[$orderby] );
        $val_b = ( $orderby == 'last_visit' || $orderby == 'date' ? strtotime($b[$orderby]) : $b[$orderby] );

        if ( $val_a == $val_b )
            $result = 0;
        else if ( $val_a < $val_b )
            $result = -1;
        else
            $result = 1;

        return ( $order === 'asc' ? $result : -$result );
    }
}