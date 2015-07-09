<?php
//=============================================
// LI_Emailer Class
//=============================================
class LI_Emailer {
    
    /**
     * Class constructor
     */
    function __construct () 
    {

    }

    /**
     * Sends the leads history email
     *
     * @param   string
     * @return  bool $email_sent    Whether the email contents were sent successfully. A true return value does not automatically mean that the user received the email successfully. It just only means that the method used was able to process the request without any errors.
     */
    function send_new_lead_email ( $hashkey ) 
    {
        $li_contact = new LI_Contact();
        $li_contact->hashkey = $hashkey;
        $li_contact->get_contact_history();
        $history = $li_contact->history;

        $body = "";

        $body = $this->build_body($li_contact);

        // Each line in an email can only be 998 characters long, so lines need to be broken with a wordwrap
        $body = wordwrap($body, 900, "\r\n");

        // Get email from plugin settings, if none set, use admin email
        $options = get_option('leadin_options');
        $to = ( $options['li_email'] ? $options['li_email'] : get_bloginfo('admin_email') ); // Get email from plugin settings, if none set, use admin email

        $tag_status = '';
        if ( count($history->lead->last_submission['form_tags']) )
            $tag_status = 'tagged as "' . $history->lead->last_submission['form_tags'][0]['tag_text'] . '" ';

        $return_status = ( $tag_status ? '' : ' ' );
        if ( $history->lead->total_visits > 1 )
            $return_status = 'by a returning visitor ';

        if ( $history->lead->total_submissions > 1 )
            $return_status = 'by a returning contact ';

        $subject = "Form submission " . $tag_status . $return_status . "on " . get_bloginfo('name') . " - " . $history->lead->lead_email;
        
        $headers = "From: LeadOut <notifications@" . str_replace(array("http://", "https://"), array('', ''), get_bloginfo('wpurl')) . ">\r\n";
        $headers .= "Reply-To: LeadOut <notifications@" . str_replace(array("http://", "https://"), array('', ''), get_bloginfo('wpurl')) . ">\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";

        $email_sent = wp_mail($to, $subject, $body, $headers);

        return $email_sent;
    }

    /**
     * Creates the contact identity section of the contact notification email
     *
     * @param   stdClass    LI_Contact
     * @return  string      concatenated string with HTML body
     */
    function build_body ( $li_contact ) 
    {
        $format = '<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"/><meta name="viewport" content="width=device-width"/></head><body style="width: 100%%;min-width: 100%%;-webkit-text-size-adjust: 100%%;-ms-text-size-adjust: 100%%;margin: 0;padding: 0;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;text-align: left;line-height: 19px;font-size: 14px;background-color: #f1f1f1;"><table class="body" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;height: 100%%;width: 100%%;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;background-color: #f1f1f1;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="center" align="center" valign="top" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: center;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><center style="width: 100%%;min-width: 580px;"><table class="container" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: inherit;width: 580px;margin: 0 auto;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;">%s%s%s%s</td></tr></table></center></td></tr></table></body></html>';

        $built_body = sprintf($format, $this->build_submission_details(get_bloginfo('url')), $this->build_contact_identity($li_contact->history->lead->lead_email), $this->build_sessions($li_contact->history), $this->build_footer($li_contact));

        return $built_body;
    }

    /**
     * Creates the contact identity section of the contact notification email
     *
     * @param   string    site URL
     * @return  string    concatenated string - New submission on [Site Name](linked to site URL)
     */
    function build_submission_details ( $url ) 
    {
        $format = '<table class="row submission-detail" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="twelve columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 580px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><h3 style="color: #666;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;margin: 0;text-align: left;line-height: 1.3;word-break: normal;font-size: 18px;">New submission on <a href="%s" style="color: #2ba6cb;text-decoration: none;">%s</a></h3></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>' . "\r\n";
        $built_submission_details = sprintf($format, $url, get_bloginfo('name'));

        return $built_submission_details;
    }

    /**
     * Creates the contact identity section of the contact notification email
     *
     * @param   string    email address from LI_Contact
     * @return  string    concatenated string with avatar + linked email address
     */
    function build_contact_identity ( $email ) 
    {
        $avatar_img = "https://api.hubapi.com/socialintel/v1/avatars?email=" . $email;
        
        $format = '<table class="row lead-identity" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><img height="60" width="60" src="%s" style="background-color:#F6601D;outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;width: auto;max-width: 100%%;float: left;clear: both;display: block;"/></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><a style="color: #2ba6cb;text-decoration: none;" href="mailto:%s"><h1 style="font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;margin: 0;text-align: left;line-height: 60px;word-break: normal;font-size: 26px;">%s</h1></a></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
        $built_identity = sprintf($format, $avatar_img, $email, $email);

        return $built_identity;
    }

    /**
     * Creates each session section separated by a spacer
     *
     * @param   stdClass    LI_contact
     * @return  string      concatenated string of sessions
     */
    function build_sessions ( $history ) 
    {
        $built_sessions = "";

        $sessions = $history->sessions;

        foreach ( $sessions as &$session ) 
        {
            $first_event = end($session['events']);
            $first_event_date = $first_event['activities'][0]['event_date'];
            $session_date = date('F j, Y, g:i a', strtotime($first_event_date)); 

            $last_event = array_values($session['events']);
            $last_event = $last_event[0];
            $last_activity = end($last_event['activities']);
            $session_end_time = date('g:i a', strtotime($last_activity['event_date']));

            $format = '<table class="row lead-timeline__date" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="twelve columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 580px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><h4 style="color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: bold;padding: 0;margin: 0;text-align: left;line-height: 1.3;word-break: normal;font-size: 14px;">%s - %s</h4></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
            $built_sessions .= sprintf($format, $session_date, $session_end_time);

            $events = $session['events'];

            foreach ( $events as &$event ) 
            {
                if ( $event['event_type'] == 'pageview' ) 
                {
                    $pageview = $event['activities'][0];
                    $pageview_time = date('g:ia', strtotime($pageview['event_date']));
                    $pageview_url = $pageview['pageview_url'];
                    $pageview_title = $pageview['pageview_title'];
                    $pageview_source = $pageview['pageview_source'];

                    $format = '<table class="row lead-timeline__event pageview" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #28c;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #1f6696;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #1f6696;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p><p class="lead-timeline__pageview-url" style="margin: 0;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;"><a href="%s" style="color: #999;text-decoration: none;">%s</a></p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
                    $built_sessions .= sprintf($format, $pageview_time, $pageview_title, $pageview_url, leadout_strip_params_from_url($pageview_url));

                    if ( $pageview['event_date'] == $first_event_date ) 
                    {
                        $format = '<table class="row lead-timeline__event traffic-source" style="margin-bottom: 20px;border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #99aa1f;border-bottom: 1px solid #dedede;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #727e14;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #727e14;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">Traffic Source: %s</p> %s </td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
                        $built_sessions .= sprintf($format, $pageview_time, ( $pageview_source ? '<a href="' . $pageview_source . '">' . leadout_strip_params_from_url($pageview_source) . '</a>' : 'Direct' ), $this->build_source_url_params($pageview_source));
                    }
                }
                else if ( $event['event_type'] == 'form' ) 
                {
                    $submission = $event['activities'][0];
                    $submission_Time = date('g:ia', strtotime($submission['event_date']));
                    $submission_url = $submission['form_page_url'];
                    $submission_page_title = $submission['form_page_title'];
                    $submission_form_fields = json_decode($submission['form_fields']);

                    $submission_tags = '';
                    if ( count($event['form_tags']) )
                    {
                        $submission_tags = ' and tagged as ';
                        for ( $i = 0; $i < count($event['form_tags']); $i++ )
                            $submission_tags .=  '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=leadout_contacts&contact_type=' . $event['form_tags'][$i]['tag_slug'] . '">' . $event['form_tags'][$i]['tag_text'] . '</a> ';
                    }
                    
                    $format = '<table class="row lead-timeline__event submission" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #f6601d;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">Filled out ' . $event['form_name'] . ' on page <a href="%s" style="color: #2ba6cb;text-decoration: none;">%s</a>%s</p> %s </td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
                    $built_sessions .= sprintf($format, $submission_Time, $submission_url, $submission_page_title, $submission_tags, $this->build_form_fields($submission_form_fields));
                }
            }
        }

        return $built_sessions;
    }

    /**
     * Creates the form fields event for contact notification email
     *
     * @param   object      json decoded set of form fields
     * @return  string      concatenated string of form fields
     */
    function build_form_fields ( $form_fields ) 
    {
        $built_form_fields = "";

        if ( count($form_fields) )
        {
            foreach ( $form_fields as $field )
            {
                $field->value =  str_replace("\n", "\\n", str_replace(array("\r\n"), "\n", $field->value));

                $format = '<p class="lead-timeline__submission-field" style="margin: 0;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;"><label class="lead-timeline__submission-label" style="text-transform: uppercase;font-size: 12px;color: #999;letter-spacing: 0.05em;">%s</label><br/>%s </p>';
                $built_form_fields .= sprintf($format, $field->label, leadout_html_line_breaks($field->value));
            }
        }
        
        return $built_form_fields;
    }

    /**
     * Creates the traffic source url params display for the contact notification email
     *
     * @param   object      string
     * @return  string      concatenated string of key value pairs for url params
     */
    function build_source_url_params ( $source_url ) 
    {
        $built_source_url_params = "";
        $url_parts = parse_url($source_url);

        if ( isset($url_parts['query']) )
        {
            parse_str($url_parts['query'], $url_vars);
            if ( count($url_vars) )
            {
                foreach ( $url_vars as $key => $value )
                {
                    $value =  str_replace("\n", "\\n", str_replace(array("\r\n"), "\n", $value));

                    if ( ! $value )
                        continue;

                    $format = '<p class="lead-timeline__submission-field" style="margin: 0;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;"><label class="lead-timeline__submission-label" style="text-transform: uppercase;font-size: 12px;color: #999;letter-spacing: 0.05em;">%s</label><br/>%s </p>';
                    $built_source_url_params .= sprintf($format, $key, leadout_html_line_breaks($value));
                }
            }
        }
        
        return $built_source_url_params;
    }

    /**
     * Creates the footer content for the contact notificaiton email
     *
     * @param   stdClass      history from LI_Contact
     * @return  string        footer content
     */
    function build_footer ( $li_contact ) 
    {
        $built_footer = "";
        $button_text = "View Contact Record";
        $contactViewUrl = get_bloginfo('wpurl') . "/wp-admin/admin.php?page=leadout_contacts&action=view&lead=" . $li_contact->history->lead->lead_id;
        
        $format = '<table class="row footer" style="margin-bottom: 20px;border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="eight columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 380px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td align="center" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><center style="width: 100%%;min-width: 380px;"><table class="button medium-button radius" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;width: 100%%;overflow: hidden;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 12px 0 10px;vertical-align: top;text-align: center;color: #ffffff;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;display: block;width: auto;background: #2ba6cb;border: 1px solid #2284a1;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;"><a href="%s" style="color: #ffffff;text-decoration: none;font-weight: bold;font-family: Helvetica, Arial, sans-serif;font-size: 20px;display: block;height: 100%%;width: 100%%;">%s</a></td></tr></table></center></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
        $built_footer .= sprintf($format, $contactViewUrl, $button_text);

        return $built_footer;
    }

    /**
     * Sends the subscription confirmation email
     *
     * @param   object      history from get_lead_history()
     * @return  bool $email_sent    Whether the email contents were sent successfully. A true return value does not automatically mean that the user received the email successfully. It just only means that the method used was able to process the request without any errors.
     */
    function send_subscriber_confirmation_email ( $hashkey ) 
    {
        $li_contact = new LI_Contact();
        $li_contact->hashkey = $hashkey;
        $li_contact->get_contact_history();
        $history = $li_contact->history;

        // Get email from plugin settings, if none set, use admin email
        $options = get_option('leadin_options');
        $leadout_email = ( $options['li_email'] ? $options['li_email'] : get_bloginfo('admin_email') ); // Get email from plugin settings, if none set, use admin email
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('wpurl');

        // @EMAIL - Use this variable to concatenate your HTML
        $body = "";

        // Email Base open
        $body .= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'><html xmlns='http://www.w3.org/1999/xhtml' xmlns='http://www.w3.org/1999/xhtml'><head><meta http-equiv='Content-Type' content='text/html;charset=utf-8'/><meta name='viewport' content='width=device-width'/></head><body style='width: 100% !important;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;color: #222222;display: block;font-family: Helvetica, Arial, sans-serif;font-weight: normal;text-align: left;line-height: 19px;font-size: 14px;margin: 0;padding: 0;'><style type='text/css'>a:hover{color: #2795b6 !important;}a:active{color: #2795b6 !important;}a:visited{color: #2ba6cb !important;}h1 a:active{color: #2ba6cb !important;}h2 a:active{color: #2ba6cb !important;}h3 a:active{color: #2ba6cb !important;}h4 a:active{color: #2ba6cb !important;}h5 a:active{color: #2ba6cb !important;}h6 a:active{color: #2ba6cb !important;}h1 a:visited{color: #2ba6cb !important;}h2 a:visited{color: #2ba6cb !important;}h3 a:visited{color: #2ba6cb !important;}h4 a:visited{color: #2ba6cb !important;}h5 a:visited{color: #2ba6cb !important;}h6 a:visited{color: #2ba6cb !important;}.button:hover table td{background: #2795b6 !important;}.tiny-button:hover table td{background: #2795b6 !important;}.small-button:hover table td{background: #2795b6 !important;}.medium-button:hover table td{background: #2795b6 !important;}.large-button:hover table td{background: #2795b6 !important;}.button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.tiny-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.tiny-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.tiny-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.small-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.small-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.small-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.medium-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.medium-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.medium-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.large-button:hover{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.large-button:active{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.large-button:visited{color: white !important;font-family: Helvetica, Arial, sans-serif;text-decoration: none;}.secondary:hover table td{background: #d0d0d0 !important;}.success:hover table td{background: #457a1a !important;}.alert:hover table td{background: #970b0e !important;}@media only screen and (max-width: 600px){table[class='body'] img{width: auto !important;height: auto !important;}table[class='body'] .container{width: 95% !important;}table[class='body'] .row{width: 100% !important;display: block !important;}table[class='body'] .wrapper{display: block !important;padding-right: 0 !important;}table[class='body'] .columns{table-layout: fixed !important;float: none !important;width: 100% !important;padding-right: 0px !important;padding-left: 0px !important;display: block !important;}table[class='body'] .column{table-layout: fixed !important;float: none !important;width: 100% !important;padding-right: 0px !important;padding-left: 0px !important;display: block !important;}table[class='body'] .wrapper.first .columns{display: table !important;}table[class='body'] .wrapper.first .column{display: table !important;}table[class='body'] table.columns td{width: 100%;}table[class='body'] table.column td{width: 100%;}table[class='body'] td.offset-by-one{padding-left: 0 !important;}table[class='body'] td.offset-by-two{padding-left: 0 !important;}table[class='body'] td.offset-by-three{padding-left: 0 !important;}table[class='body'] td.offset-by-four{padding-left: 0 !important;}table[class='body'] td.offset-by-five{padding-left: 0 !important;}table[class='body'] td.offset-by-six{padding-left: 0 !important;}table[class='body'] td.offset-by-seven{padding-left: 0 !important;}table[class='body'] td.offset-by-eight{padding-left: 0 !important;}table[class='body'] td.offset-by-nine{padding-left: 0 !important;}table[class='body'] td.offset-by-ten{padding-left: 0 !important;}table[class='body'] td.offset-by-eleven{padding-left: 0 !important;}table[class='body'] .expander{width: 9999px !important;}table[class='body'] .hide-for-small{display: none !important;}table[class='body'] .show-for-desktop{display: none !important;}table[class='body'] .show-for-small{display: inherit !important;}table[class='body'] .hide-for-desktop{display: inherit !important;}table[class='body'] .container.main{width: 100% !important;}}</style><table class='body' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;height: 100%;width: 100%;padding: 0;'><tr align='left' style='vertical-align: top; text-align: left; padding: 0;'><td class='center' align='center' valign='top' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: center;padding: 0 0 20px;'><center style='width: 100%;'>";
        
        // Email Header open
        $body .= "<table class='row header' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;padding: 0px;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='center' align='center' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: center;padding: 0;' valign='top'><center style='width: 100%;'><table class='container' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: inherit;width: 580px;margin:0 auto 10px auto; padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 10px 0px 0px;' align='left' valign='top'><table class='twelve columns' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 580px;margin: 0 auto;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='two sub-columns' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;width: 100% !important;padding: 0px 0px 10px 0px;' align='left' valign='top'>";

        $body .= "<h1 class='lead-name' style='color: #222222; display: block; font-family: Helvetica, Arial, sans-serif; font-weight: bold; text-align: left; line-height: 1.3; word-break: normal; font-size: 20px; margin: 0; padding: 0;' align='left'>" . $site_name . "</h1>";

        // Email Header close
        $body .= "</td><td class='expander' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;visibility: hidden;width: 0px;padding: 0;' align='left' valign='top'></td></tr></table></td></tr></table></center></td></tr></table>";

        $body .= "<table class='row header' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;padding: 0px;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='center' align='center' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: center;padding: 0;' valign='top'><center style='width: 100%;'><table class='container' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: inherit;width: 580px;margin:0 auto 10px auto; padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 10px 0px 0px;' align='left' valign='top'><table class='twelve columns' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 580px;margin: 0 auto;padding: 0;'>";

            $body .= "<tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td>";
                $body .= "<td style='padding: 0px 0px 10px 0px;'>Your subscription to <i><a href='" . $site_url . "'>" . $site_name . "</a></i> has been confirmed.</td>";
            $body .= "</tr>";

            $body .= "<tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td>";
                $body .= "<td style='padding: 10px 0px 20px 0px;'>Just so you have it, here is a copy of the information you submitted to us...</td>";
            $body .= "</tr>";

        $body .= "</table>";

        // Main container open
        $body .= "<table class='container main' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: inherit;width: 580px;margin: 0 auto;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;padding: 0;' align='left' valign='top'>";
        
        // Form Submission section open
        $body .= "<table class='row section form-submission' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;display: block;background: #deedf8;padding: 0px;' bgcolor='#deedf8'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 0 0px 0 0;' align='left' valign='top'>";
        
        $submission = $history->submission;
        $submission_Time = date('g:ia', strtotime($submission['event_date']));
        $submission_url = $submission['form_page_url'];
        $submission_page_title = $submission['form_page_title'];
        $submission_form_fields = json_decode($submission['form_fields']);
        
        $format = '<table class="row lead-timeline__event submission" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%%;position: relative;display: block;background-color: #fff;border-top: 1px solid #dedede;border-right: 1px solid #dedede;border-left: 4px solid #f6601d;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="wrapper" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="two columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 80px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-time" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">%s</p></td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td><td class="wrapper last" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 10px 20px 0px 0px;vertical-align: top;text-align: left;position: relative;padding-right: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><table class="ten columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;margin: 0 auto;width: 480px;"><tr style="padding: 0;vertical-align: top;text-align: left;"><td class="text-pad" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0px 0px 10px;vertical-align: top;text-align: left;padding-left: 10px;padding-right: 10px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"><p class="lead-timeline__event-title" style="margin: 0;color: #b34a12;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding: 0;text-align: left;line-height: 19px;font-size: 14px;margin-bottom: 10px;">Filled out form on page <a href="%s" style="color: #2ba6cb;text-decoration: none;">%s</a></p> %s </td><td class="expander" style="word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;visibility: hidden;width: 0px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;margin: 0;line-height: 19px;font-size: 14px;"></td></tr></table></td></tr></table>';
        $built_sessions = sprintf($format, $submission_Time, $submission_url, $submission_page_title, $this->build_form_fields($submission_form_fields));

        $body .= $built_sessions;

        // Form Submission Section Close
        $body .= "</td></tr></table>";

        // Build [you may contact us at:] row
        $body .= "<table class='row section' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;position: relative;display: block;margin-top: 20px;padding: 0px;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td class='wrapper last' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;position: relative;padding: 0 0px 0 0;' align='left' valign='top'><table class='twelve columns' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 580px;margin: 0 auto;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;padding: 0px;' align='left' valign='top'><table class='button round' style='border-spacing: 0;border-collapse: collapse;vertical-align: top;text-align: left;width: 100%;overflow: hidden;padding: 0;'><tr style='vertical-align: top;text-align: left;padding: 0;' align='left'><td>";
            $body .="You may also contact us at:<br/><a href='mailto:" . $leadout_email . "'>" . $leadout_email . "</a>";
        $body .= "</td></tr></table></td><td class='expander' style='word-break: break-word;-webkit-hyphens: auto;-moz-hyphens: auto;hyphens: auto;border-collapse: collapse !important;vertical-align: top;text-align: left;visibility: hidden;width: 0px;padding: 0;border: 0;' align='left' valign='top'></td></tr></table></td></tr></table>";

        // @EMAIL - end form section

        // Email Base close
        $body .= '</center></td></tr></table></body></html>';
        $from = apply_filters( 'li_subscribe_from', $leadout_email );

        // Each line in an email can only be 998 characters long, so lines need to be broken with a wordwrap
        $body = wordwrap($body, 900, "\r\n");

        $headers = "From: LeadOut <" . $from . ">\r\n";
        $headers.= "Reply-To: LeadOut <" . $from . ">\r\n";
        $headers.= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers.= "MIME-Version: 1.0\r\n";
        $headers.= "Content-type: text/html; charset=utf-8\r\n";

        $subject = $site_name . ': Subscription Confirmed';

        $email_sent = wp_mail($history->lead->lead_email, $subject, $body, $headers);
        return $email_sent;
    }
}