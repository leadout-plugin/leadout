<?php

//=============================================
// Include Needed Files
//=============================================


//=============================================
// WPLeadOutAdmin Class
//=============================================
class WPLeadOutSubscribeAdmin extends WPLeadOutAdmin {
    
    var $power_up_settings_section = 'leadin_subscribe_options_section';
    var $power_up_icon;
    var $options;

    /**
     * Class constructor
     */
    function __construct (  $power_up_icon_small )
    {
        //=============================================
        // Hooks & Filters
        //=============================================
        
        if ( is_admin() )
        {
            $this->power_up_icon = $power_up_icon_small;
            add_action('admin_init', array($this, 'leadout_subscribe_build_settings_page'));
            $this->options = get_option('leadin_subscribe_options');
        }
    }

    //=============================================
    // Settings Page
    //=============================================

    /**
     * Creates settings options
     */
    function leadout_subscribe_build_settings_page ()
    {
        register_setting('leadout_settings_options', 'leadin_subscribe_options', array($this, 'sanitize'));

        add_settings_section(
            $this->power_up_settings_section,
            $this->power_up_icon . 'Pop-up Form',
            array($this, 'print_hidden_settings_fields'),
            LEADOUT_ADMIN_PATH
        );

        add_settings_field(
            'li_subscribe_vex_class',
            'Pop-up Location',
            array($this, 'li_subscribe_vex_class_callback'),
            LEADOUT_ADMIN_PATH,
            $this->power_up_settings_section
        );
        add_settings_field(
            'li_subscribe_heading',
            'Pop-up header text',
            array($this, 'li_subscribe_heading_callback'),
            LEADOUT_ADMIN_PATH,
            $this->power_up_settings_section
        );
        add_settings_field(
            'li_subscribe_text',
            'Description text',
            array($this, 'li_subscribe_text_callback'),
            LEADOUT_ADMIN_PATH,
            $this->power_up_settings_section
        );
        add_settings_field(
            'li_subscribe_btn_label',
            'Button text',
            array($this, 'li_subscribe_btn_label_callback'),
            LEADOUT_ADMIN_PATH,
            $this->power_up_settings_section
        );
        add_settings_field(
            'li_subscribe_btn_color',
            'Button color',
            array($this, 'li_subscribe_btn_color_callback'),
            LEADOUT_ADMIN_PATH,
            $this->power_up_settings_section
        );
        add_settings_field(
            'li_subscribe_additional_fields',
            'Also include fields for',
            array($this, 'li_subscribe_additional_fields_callback'),
            LEADOUT_ADMIN_PATH,
            $this->power_up_settings_section
        );

        add_settings_field( 
            'li_subscribe_templates', 
            'Show subscribe pop-up on', 
            array($this, 'li_subscribe_templates_callback'), 
            LEADOUT_ADMIN_PATH, 
            $this->power_up_settings_section
        );

        add_settings_field( 
            'li_subscribe_confirmation', 
            'Subscription confirmation', 
            array($this, 'li_subscribe_confirmation_callback'), 
            LEADOUT_ADMIN_PATH, 
            $this->power_up_settings_section
        );

         add_settings_field( 
            'li_subscribe_mobile_popup', 
            'Show on mobile?', 
            array($this, 'li_subscribe_mobile_popup_callback'), 
            LEADOUT_ADMIN_PATH, 
            $this->power_up_settings_section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize ( $input )
    {
        $new_input = array();

        if( isset( $input['li_susbscibe_installed'] ) )
            $new_input['li_susbscibe_installed'] = sanitize_text_field( $input['li_susbscibe_installed'] );

        if( isset( $input['li_subscribe_vex_class'] ) )
            $new_input['li_subscribe_vex_class'] = sanitize_text_field( $input['li_subscribe_vex_class'] );

        if( isset( $input['li_subscribe_heading'] ) )
            $new_input['li_subscribe_heading'] = sanitize_text_field( $input['li_subscribe_heading'] );

        if( isset( $input['li_subscribe_text'] ) )
            $new_input['li_subscribe_text'] = sanitize_text_field( $input['li_subscribe_text'] );

        if( isset( $input['li_subscribe_btn_label'] ) )
            $new_input['li_subscribe_btn_label'] = sanitize_text_field( $input['li_subscribe_btn_label'] );

        if( isset( $input['li_subscribe_btn_color'] ) )
            $new_input['li_subscribe_btn_color'] = sanitize_text_field( $input['li_subscribe_btn_color'] );

        if( isset( $input['li_subscribe_name_fields'] ) )
            $new_input['li_subscribe_name_fields'] = sanitize_text_field( $input['li_subscribe_name_fields'] );

        if( isset( $input['li_subscribe_phone_field'] ) )
            $new_input['li_subscribe_phone_field'] = sanitize_text_field( $input['li_subscribe_phone_field'] );

        if( isset( $input['li_subscribe_template_pages'] ) )
            $new_input['li_subscribe_template_pages'] = sanitize_text_field( $input['li_subscribe_template_pages'] );

        if( isset( $input['li_subscribe_template_posts'] ) )
            $new_input['li_subscribe_template_posts'] = sanitize_text_field( $input['li_subscribe_template_posts'] );
        
        if( isset( $input['li_subscribe_template_home'] ) )
            $new_input['li_subscribe_template_home'] = sanitize_text_field( $input['li_subscribe_template_home'] );

        if( isset( $input['li_subscribe_template_archives'] ) )
            $new_input['li_subscribe_template_archives'] = sanitize_text_field( $input['li_subscribe_template_archives'] );

        if( isset( $input['li_subscribe_confirmation'] ) )
            $new_input['li_subscribe_confirmation'] = sanitize_text_field( $input['li_subscribe_confirmation'] );
        else
            $new_input['li_subscribe_confirmation'] = '0';

        if( isset( $input['li_subscribe_mobile_popup'] ) )
            $new_input['li_subscribe_mobile_popup'] = sanitize_text_field( $input['li_subscribe_mobile_popup'] );

        return $new_input;
    }

    function print_hidden_settings_fields ()
    {
         // Hacky solution to solve the Settings API overwriting the default values
        $options = $this->options;
        $li_susbscibe_installed = ( $options['li_susbscibe_installed'] ? $options['li_susbscibe_installed'] : 1 );

        printf(
            '<input id="li_susbscibe_installed" type="hidden" name="leadin_subscribe_options[li_susbscibe_installed]" value="%d"/>',
            $li_susbscibe_installed
        );
    }
    /**
     * Prints subscribe location input for settings page
     */
    function li_subscribe_vex_class_callback ()
    {
        $options = $this->options;
        $li_subscribe_vex_class = ( $options['li_subscribe_vex_class'] ? $options['li_subscribe_vex_class'] : 'vex-theme-bottom-right-corner' ); // Get class from options, or show default

        echo '<select id="li_subscribe_vex_class" name="leadin_subscribe_options[li_subscribe_vex_class]">';
            echo '<option value="vex-theme-bottom-right-corner"' . ( $li_subscribe_vex_class == 'vex-theme-bottom-right-corner' ? ' selected' : '' ) . '>Bottom right</option>';
            echo '<option value="vex-theme-bottom-left-corner"' . ( $li_subscribe_vex_class == 'vex-theme-bottom-left-corner' ? ' selected' : '' ) . '>Bottom left</option>';
            echo '<option value="vex-theme-top"' . ( $li_subscribe_vex_class == 'vex-theme-top' ? ' selected' : '' ) . '>Top</option>';
            echo '<option value="vex-theme-default"' . ( $li_subscribe_vex_class == 'vex-theme-default' ? ' selected' : '' ) . '>Pop-over content</option>';
        echo '</select>';
    }

    /**
     * Prints subscribe heading input for settings page
     */
    function li_subscribe_heading_callback ()
    {
        $options = $this->options;
        $li_subscribe_heading = ( $options['li_subscribe_heading'] ? $options['li_subscribe_heading'] : 'Sign up for email updates' ); // Get header from options, or show default
        
        printf(
            '<input id="li_subscribe_heading" type="text" name="leadin_subscribe_options[li_subscribe_heading]" value="%s" size="50"/>',
            $li_subscribe_heading
        );
    }

    /**
     * Prints subscribe heading input for settings page
     */
    function li_subscribe_text_callback ()
    {
        $options = $this->options;
        $li_subscribe_text = ( $options['li_subscribe_text'] ? $options['li_subscribe_text'] : '' ); // Get header from options, or show default
        
        printf(
            '<input id="li_subscribe_text" type="text" name="leadin_subscribe_options[li_subscribe_text]" value="%s" size="50"/>',
            $li_subscribe_text
        );
    }

    /**
     * Prints button label
     */
    function li_subscribe_btn_label_callback ()
    {
        $options = $this->options;
        $li_subscribe_btn_label = ( $options['li_subscribe_btn_label'] ? $options['li_subscribe_btn_label'] : 'SUBSCRIBE' ); // Get button text from options, or show default
        
        printf(
            '<input id="li_subscribe_btn_label" type="text" name="leadin_subscribe_options[li_subscribe_btn_label]" value="%s" size="50"/>',
            $li_subscribe_btn_label
        );

    }

    /**
     * Prints button color
     */
    function li_subscribe_btn_color_callback ()
    {
        $options = $this->options;

        $li_subscribe_btn_color = ( isset($options['li_subscribe_btn_color']) ? $options['li_subscribe_btn_color'] : 'leadout-popup-color-blue' ); // Get button text from options, or show default

        echo '<fieldset><legend class="screen-reader-text"><span>Button Color</span></legend>';

        printf(
            '<label for="li_subscribe_btn_color_blue"><input id="li_subscribe_btn_color_blue" type="radio" name="leadin_subscribe_options[li_subscribe_btn_color]" value="leadout-popup-color-blue"' . checked( 'leadout-popup-color-blue', ( isset($options['li_subscribe_btn_color']) ? $options['li_subscribe_btn_color'] : 'leadout-popup-color-blue' ), false ) . '>' . 
            '<span class="color-swatch blue">Blue</span></label><br>'
        );

        printf(
            '<label for="li_subscribe_btn_color_red"><input id="li_subscribe_btn_color_red" type="radio" name="leadin_subscribe_options[li_subscribe_btn_color]" value="leadout-popup-color-red"' . checked( 'leadout-popup-color-red', ( isset($options['li_subscribe_btn_color']) ? $options['li_subscribe_btn_color'] : 'leadout-popup-color-red' ), false ) . '>' . 
            '<span class="color-swatch red">Red</span></label><br>'
        );

        printf(
            '<label for="li_subscribe_btn_color_green"><input id="li_subscribe_btn_color_green" type="radio" name="leadin_subscribe_options[li_subscribe_btn_color]" value="leadout-popup-color-green"' . checked( 'leadout-popup-color-green', ( isset($options['li_subscribe_btn_color']) ? $options['li_subscribe_btn_color'] : 'leadout-popup-color-green' ), false ) . '>' . 
            '<span class="color-swatch green">Green</span></label><br>'
        );

        printf(
            '<label for="li_subscribe_btn_color_yellow"><input id="li_subscribe_btn_color_yellow" type="radio" name="leadin_subscribe_options[li_subscribe_btn_color]" value="leadout-popup-color-yellow"' . checked( 'leadout-popup-color-yellow', ( isset($options['li_subscribe_btn_color']) ? $options['li_subscribe_btn_color'] : 'leadout-popup-color-yellow' ), false ) . '>' . 
            '<span class="color-swatch yellow">Yellow</span></label><br>'
        );

        printf(
            '<label for="li_subscribe_btn_color_purple"><input id="li_subscribe_btn_color_purple" type="radio" name="leadin_subscribe_options[li_subscribe_btn_color]" value="leadout-popup-color-purple"' . checked( 'leadout-popup-color-purple', ( isset($options['li_subscribe_btn_color']) ? $options['li_subscribe_btn_color'] : 'leadout-popup-color-purple' ), false ) . '>' . 
            '<span class="color-swatch purple">Purple</span></label><br>'
        );

        printf(
            '<label for="li_subscribe_btn_color_orange"><input id="li_subscribe_btn_color_orange" type="radio" name="leadin_subscribe_options[li_subscribe_btn_color]" value="leadout-popup-color-orange"' . checked( 'leadout-popup-color-orange', ( isset($options['li_subscribe_btn_color']) ? $options['li_subscribe_btn_color'] : 'leadout-popup-color-orange' ), false ) . '>' . 
            '<span class="color-swatch orange">Orange</span></label><br>'
        );

        echo '</fieldset>';

    }

    /**
     * Prints additional fields for first name, last name and phone number
     */
    function li_subscribe_additional_fields_callback ()
    {
        $options = $this->options;

        printf(
            '<p><input id="li_subscribe_name_fields" type="checkbox" name="leadin_subscribe_options[li_subscribe_name_fields]" value="1"' . checked( 1, ( isset($options['li_subscribe_name_fields']) ? $options['li_subscribe_name_fields'] : '0' ), false ) . '/>' . 
            '<label for="li_subscribe_name_fields">First + last name</label></p>'
        );

        printf(
            '<p><input id="li_subscribe_phone_field" type="checkbox" name="leadin_subscribe_options[li_subscribe_phone_field]" value="1"' . checked( 1, ( isset($options['li_subscribe_phone_field']) ? $options['li_subscribe_phone_field'] : '0' ), false ) . '/>' . 
            '<label for="li_subscribe_phone_field">Phone number</label></p>'
        );
    }

    /**
     * Prints the options for toggling the widget on posts, pages, archives and homepage
     */
    function li_subscribe_templates_callback ()
    {
        $options = $this->options;

        printf(
            '<p><input id="li_subscribe_template_posts" type="checkbox" name="leadin_subscribe_options[li_subscribe_template_posts]" value="1"' . checked( 1, ( isset($options['li_subscribe_template_posts']) ? $options['li_subscribe_template_posts'] : '0' ), false ) . '/>' . 
            '<label for="li_subscribe_template_posts">Posts</label></p>'
        );

        printf(
            '<p><input id="li_subscribe_template_pages" type="checkbox" name="leadin_subscribe_options[li_subscribe_template_pages]" value="1"' . checked( 1, ( isset($options['li_subscribe_template_pages']) ? $options['li_subscribe_template_pages'] : '0' ), false ) . '/>' . 
            '<label for="li_subscribe_template_pages">Pages</label></p>'
        );

        printf(
            '<p><input id="li_subscribe_template_archives" type="checkbox" name="leadin_subscribe_options[li_subscribe_template_archives]" value="1"' . checked( 1, ( isset($options['li_subscribe_template_archives']) ? $options['li_subscribe_template_archives'] : '0' ), false ) . '/>' . 
            '<label for="li_subscribe_template_archives">Archives</label></p>'
        );

        printf(
            '<p><input id="li_subscribe_template_home" type="checkbox" name="leadin_subscribe_options[li_subscribe_template_home]" value="1"' . checked( 1, ( isset($options['li_subscribe_template_home']) ? $options['li_subscribe_template_home'] : '0' ), false ) . '/>' . 
            '<label for="li_subscribe_template_home">Homepage</label></p>'
        );
    }

    /**
     * Prints the options for toggling the widget on posts, pages, archives and homepage
     */
    function li_subscribe_confirmation_callback ()
    {
        $options = $this->options;

        printf(
            '<p><input id="li_subscribe_confirmation" type="checkbox" name="leadin_subscribe_options[li_subscribe_confirmation]" value="1"' . checked( 1, ( isset($options['li_subscribe_confirmation']) ? $options['li_subscribe_confirmation'] : 0 ) , false ) . '/>' . 
            '<label for="li_subscribe_confirmation">Send contacts who filled out the popup form a confirmation email</label></p>'
        );
    }

    /**
     * Prints the options for toggling the widget on posts, pages, archives and homepage
     */
    function li_subscribe_mobile_popup_callback ()
    {
        $options = $this->options;

        $li_subscribe_mobile_popup = ( isset($options['li_subscribe_mobile_popup']) ? $options['li_subscribe_mobile_popup'] : '1' );

        echo '<select id="li_subscribe_mobile_popup" name="leadin_subscribe_options[li_subscribe_mobile_popup]">';
            echo '<option value="1"' . ( $li_subscribe_mobile_popup == '1' ? ' selected' : '' ) . '>Yes</option>';
            echo '<option value="0"' . ( $li_subscribe_mobile_popup == '0' ? ' selected' : '' ) . '>No</option>';
        echo '</select>';
    }
}

?>