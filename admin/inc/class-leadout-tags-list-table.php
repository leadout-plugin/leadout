<?php

//=============================================
// Include Needed Files
//=============================================

if ( !class_exists('WP_List_Table') )
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

require_once(LEADOUT_PLUGIN_DIR . '/inc/leadout-functions.php');

//=============================================
// LI_List_Table Class
//=============================================
class LI_Tags_Table extends WP_List_Table {
    
    /**
     * Variables
     */
    public $data = array();

    /**
     * Class constructor
     */
    function __construct () 
    {
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'tag',
            'plural'    => 'tags',
            'ajax'      => false
        ));
    }

    /**
     * Prints text for no rows found in table
     */
    function no_items () 
    {
      _e('No tags found.');
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
            //case 'tag_order':
                //return $item[$column_name];
            case 'tag_text':
                return $item[$column_name];
            case 'tag_count':
                return $item[$column_name];
            case 'tag_form_selectors':
                return $item[$column_name];
            case 'tag_synced_lists':
                return $item[$column_name];

            case 'reorder' :
                return '<span class="icon-mover"></span>';
                
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
    function column_tag_text ( $item )
    {
        //Build row actions
        $actions = array(
            'edit'    => sprintf('<div style="clear:both;"></div><a href="?page=%s&action=%s&tag=%s">Edit</a>', $_REQUEST['page'], 'edit_tag',$item['tag_id']),
            'delete'  => sprintf('<a href="?page=%s&action=%s&tag=%s">Delete</a>',$_REQUEST['page'],'delete_tag',$item['tag_id'])
        );
        
        //Return the title contents
        return sprintf('%1$s<br/>%2$s',
            /*$1%s*/ sprintf('<a class="row-title" href="?page=%s&action=edit_tag&tag=%s">%s</a>', $_REQUEST['page'], $item['tag_id'], $item['tag_text']),
            /*$2%s*/ $this->row_actions($actions)
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
            'tag_text'       => 'Tag',
            'tag_count'      => 'Contacts',
            'tag_form_selectors'     => 'CSS Selectors',
            'tag_synced_lists'  => 'Synced lists'
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
            'tag_text'              => array('tag_text',false), 
            'tag_count'             => array('tag_count',false),
            'tag_form_selectors'    => array('tag_form_selectors',false),
            'tag_synced_lists'      => array('tag_synced_lists',false)
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
        return array();
    }
    
    /**
     * Process bulk actions for deleting
     */
    function process_bulk_action ()
    {
        
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
                ( SELECT COUNT(DISTINCT contact_hashkey) FROM $wpdb->li_tag_relationships ltr, $wpdb->li_leads ll WHERE tag_id = lt.tag_id AND ltr.tag_relationship_deleted = 0 AND ltr.contact_hashkey != '' AND ll.hashkey = ltr.contact_hashkey AND ll.lead_deleted = 0 AND ll.hashkey != '' GROUP BY tag_id ) AS tag_count
            FROM 
                $wpdb->li_tags lt
            WHERE
                lt.tag_deleted = 0
            GROUP BY lt.tag_slug 
            ORDER BY lt.tag_order ASC";

        $tags = $wpdb->get_results($q);

        $all_tags = array();
        if ( count($tags) )
        {
            foreach ( $tags as $key => $tag ) 
            {
                $tag_synced_lists = '';
                if ( $tag->tag_synced_lists )
                {
                    foreach ( unserialize($tag->tag_synced_lists) as $list )
                        $tag_synced_lists .= $list['list_name'] . '<br/>';
                }

                $tag_array = array(
                    'tag_id' => $tag->tag_id,
                    'tag_count' => sprintf('<a href="?page=%s&contact_type=%s">%d</a>', 'leadout_contacts', $tag->tag_slug, ( $tag->tag_count ? $tag->tag_count : 0 )),
                    'tag_text' => $tag->tag_text,
                    'tag_slug' => $tag->tag_slug,
                    'tag_form_selectors' => str_replace(',', '<br/>', $tag->tag_form_selectors),
                    'tag_synced_lists' => $tag_synced_lists,
                    'tag_order' => $tag->tag_order
                );
                
                array_push($all_tags, $tag_array);
            }
        }

        return stripslashes_deep($all_tags);
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
        $orderby = ( !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'tag_order' );
        $order = ( !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'asc' );

        if ( $a[$orderby] == $b[$orderby] )
            $result = 0;
        else if ( $a[$orderby] < $b[$orderby] )
            $result = -1;
        else
            $result = 1;

        return ( $order === 'asc' ? $result : -$result );
    }
}