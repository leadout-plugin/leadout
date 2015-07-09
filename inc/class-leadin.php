<?php

//=============================================
// WPLeadOut Class
//=============================================
class WPLeadOut {

    var $power_ups;
    /**
     * Class constructor
     */
    function __construct ()
    {
        global $pagenow;

        leadout_set_wpdb_tables();
        leadout_set_mysql_timezone_offset();

        $this->power_ups = self::get_available_power_ups();

        add_action('admin_bar_menu', array($this, 'add_leadout_link_to_admin_bar'), 999);
 
        if ( is_admin() )
        {
            if ( ! defined('DOING_AJAX') || ! DOING_AJAX )
                $li_wp_admin = new WPLeadOutAdmin($this->power_ups);
        }
        else
        {
            add_action('wp_footer', array($this, 'append_leadout_version_number'));

            // Adds the leadout-tracking script to wp-login.php page which doesnt hook into the enqueue logic
            if ( in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')) )
                add_action('login_enqueue_scripts', array($this, 'add_leadout_frontend_scripts'));
            else
                add_action('wp_enqueue_scripts', array($this, 'add_leadout_frontend_scripts'));
        }
    }

    //=============================================
    // Scripts & Styles
    //=============================================

    /**
     * Adds front end javascript + initializes ajax object
     */
    function add_leadout_frontend_scripts ()
    {
        wp_register_script('leadout-tracking', LEADOUT_PATH . '/assets/js/build/leadout-tracking.min.js', array ('jquery'), FALSE, TRUE);
        wp_enqueue_script('leadout-tracking');
        
        // replace https with http for admin-ajax calls for SSLed backends 
        $admin_url = admin_url('admin-ajax.php');
        wp_localize_script(
            'leadout-tracking', 
            'li_ajax', 
            array('ajax_url' => ( is_ssl() ? str_replace('http:', 'https:', $admin_url) : str_replace('https:', 'http:', $admin_url) ))
        );
    }

    /**
     * Adds LeadOut link to top-level admin bar
     */
    function add_leadout_link_to_admin_bar ( $wp_admin_bar )
    {
        global $wp_version;

        if ( ! current_user_can('activate_plugins') )
        {
            if ( ! array_key_exists('li_grant_access_to_' . leadout_get_user_role(), get_option('leadin_options') ) )
                return FALSE;
        }

        $args = array(
            'id'     => 'leadout-admin-menu',
            'title'  => '<span class="ab-icon" '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? ' style="margin-top: 3px;"' : '' ) . '>' . '</span><span class="ab-label">LeadOut</span>', // alter the title of existing node
            'parent' => FALSE,   // set parent to false to make it a top level (parent) node
            'href' => get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_stats',
            'meta' => array('title' => 'LeadOut')
        );

        $wp_admin_bar->add_node( $args );
    }

    /**
     * Adds LeadOut version number to the source code for debugging purposes
     */
    function append_leadout_version_number ( )
    {
        echo "\n\n<!-- This site is collecting contacts with LeadOut v" . LEADOUT_PLUGIN_VERSION . "--> \n";
    }

    /**
     * List available power-ups
     */
    public static function get_available_power_ups ( $min_version = FALSE, $max_version = FALSE ) 
    {
        static $power_ups = null;

        if ( ! isset( $power_ups ) ) {
            $files = self::glob_php( LEADOUT_PLUGIN_DIR . '/power-ups' );

            $power_ups = array();

            foreach ( $files as $file ) {

                if ( ! $headers = self::get_power_up($file) ) {
                    continue;
                }

                $power_up = new $headers['class']($headers['activated']);
                $power_up->power_up_name    = $headers['name'];
                $power_up->menu_text        = $headers['menu_text'];
                $power_up->menu_link        = $headers['menu_link'];
                $power_up->slug             = $headers['slug'];
                $power_up->link_uri         = $headers['uri'];
                $power_up->description      = $headers['description'];
                $power_up->icon             = $headers['icon'];
                $power_up->activated        = $headers['activated'];
                $power_up->permanent        = ( $headers['permanent'] == 'Yes' ? 1 : 0 );
                $power_up->auto_activate    = ( $headers['auto_activate'] == 'Yes' ? 1 : 0 );
                $power_up->hidden           = ( $headers['hidden'] == 'Yes' ? 1 : 0 );
                $power_up->curl_required    = ( $headers['curl_required'] == 'Yes' ? 1 : 0 );
                $power_up->pro_only         = ( $headers['pro_only'] == 'Yes' ? 1 : 0 );
                
                // Set the small icons HTML for the settings page
                if ( strstr($headers['icon_small'], 'dashicons') )
                    $power_up->icon_small = '<span class="dashicons ' . $headers['icon_small'] . '"></span>';
                else
                    $power_up->icon_small = '<img src="' . LEADOUT_PATH . '/images/' . $headers['icon_small'] . '.png" class="power-up-settings-icon"/>';

                array_push($power_ups, $power_up);
            }
        }

        return $power_ups;       
    }

    /**
     * Extract a power-up's slug from its full path.
     */
    public static function get_power_up_slug ( $file ) {
        return str_replace( '.php', '', basename( $file ) );
    }

    /**
     * Generate a power-up's path from its slug.
     */
    public static function get_power_up_path ( $slug ) {
        return LEADOUT_PLUGIN_DIR . "/power-ups/$slug.php";
    }

    /**
     * Load power-up data from power-up file. Headers differ from WordPress
     * plugin headers to avoid them being identified as standalone
     * plugins on the WordPress plugins page.
     *
     * @param $power_up The file path for the power-up
     * @return $pu array of power-up attributes
     */
    public static function get_power_up ( $power_up )
    {
        $headers = array(
            'name'              => 'Power-up Name',
            'class'             => 'Power-up Class',
            'menu_text'         => 'Power-up Menu Text',
            'menu_link'         => 'Power-up Menu Link',
            'slug'              => 'Power-up Slug',
            'uri'               => 'Power-up URI',
            'description'       => 'Power-up Description',
            'icon'              => 'Power-up Icon',
            'icon_small'        => 'Power-up Icon Small',
            'introduced'        => 'First Introduced',
            'auto_activate'     => 'Auto Activate',
            'permanent'         => 'Permanently Enabled',
            'power_up_tags'     => 'Power-up Tags',
            'hidden'            => 'Hidden',
            'curl_required'     => 'cURL Required',
            'pro_only'          => 'Pro Only'
        );

        $file = self::get_power_up_path( self::get_power_up_slug( $power_up ) );
        if ( ! file_exists( $file ) )
            return FALSE;

        $pu = get_file_data( $file, $headers );

        if ( empty( $pu['name'] ) )
            return FALSE;

        $pu['activated'] = self::is_power_up_active($pu['slug']);

        return $pu;
    }

    /**
     * Returns an array of all PHP files in the specified absolute path.
     * Equivalent to glob( "$absolute_path/*.php" ).
     *
     * @param string $absolute_path The absolute path of the directory to search.
     * @return array Array of absolute paths to the PHP files.
     */
    public static function glob_php( $absolute_path ) {
        $absolute_path = untrailingslashit( $absolute_path );
        $files = array();
        if ( ! $dir = @opendir( $absolute_path ) ) {
            return $files;
        }

        while ( FALSE !== $file = readdir( $dir ) ) {
            if ( '.' == substr( $file, 0, 1 ) || '.php' != substr( $file, -4 ) ) {
                continue;
            }

            $file = "$absolute_path/$file";

            if ( ! is_file( $file ) ) {
                continue;
            }

            $files[] = $file;
        }

        $files = leadout_sort_power_ups($files, array(
            LEADOUT_PLUGIN_DIR . '/power-ups/contacts.php',
            LEADOUT_PLUGIN_DIR . '/power-ups/subscribe-widget.php', 
            LEADOUT_PLUGIN_DIR . '/power-ups/mailchimp-connect.php', 
            LEADOUT_PLUGIN_DIR . '/power-ups/constant-contact-connect.php',
            LEADOUT_PLUGIN_DIR . '/power-ups/aweber-connect.php',
            LEADOUT_PLUGIN_DIR . '/power-ups/campaign-monitor-connect.php',
            LEADOUT_PLUGIN_DIR . '/power-ups/getresponse-connect.php'
        ));

        closedir( $dir );

        return $files;
    }

    /**
     * Check whether or not a LeadOut power-up is active.
     *
     * @param string $power_up The slug of a power-up
     * @return bool
     *
     * @static
     */
    public static function is_power_up_active ( $power_up_slug )
    {
        return in_array($power_up_slug, self::get_active_power_ups());
    }

    /**
     * Get a list of activated modules as an array of module slugs.
     */
    public static function get_active_power_ups ()
    {
        $activated_power_ups = get_option('leadin_active_power_ups');
        if ( $activated_power_ups )
            return array_unique(unserialize($activated_power_ups));
        else
            return array();
    }

    public static function activate_power_up( $power_up_slug, $exit = TRUE )
    {
        if ( ! strlen( $power_up_slug ) )
            return FALSE;

        // If it's already active, then don't do it again
        $active = self::is_power_up_active($power_up_slug);
        if ( $active )
            return TRUE;

        $activated_power_ups = get_option('leadin_active_power_ups');
        
        if ( $activated_power_ups )
        {
            $activated_power_ups = unserialize($activated_power_ups);
            $activated_power_ups[] = $power_up_slug;
        }
        else
        {
            $activated_power_ups = array($power_up_slug);
        }

        update_option('leadin_active_power_ups', serialize($activated_power_ups));


        if ( $exit )
        {
            exit;
        }
    }

    public static function deactivate_power_up( $power_up_slug, $exit = TRUE )
    {
        if ( ! strlen( $power_up_slug ) )
            return FALSE;

        // If it's already active, then don't do it again
        $active = self::is_power_up_active($power_up_slug);
        if ( ! $active )
            return TRUE;

        $activated_power_ups = get_option('leadin_active_power_ups');
        
        $power_ups_left = leadout_array_delete(unserialize($activated_power_ups), $power_up_slug);
        update_option('leadin_active_power_ups', serialize($power_ups_left));
        
        if ( $exit )
        {
            exit;
        }

    }
}

//=============================================
// LeadOut Init
//=============================================

global $li_wp_admin;