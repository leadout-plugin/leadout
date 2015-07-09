<?php
//=============================================
// LI_Viewers Class
//=============================================
class LI_Viewers {
	
	/**
	 * Variables
	 */
	var $viewers;
	var $submissions;

	/**
	 * Class constructor
	 */
	function __construct ()
	{

	}

	/**
     * Get identified readers from a url
     * @param string
     * @return array
     */
	function get_identified_viewers ( $pageview_url )
	{
		global $wpdb;
		$q = $wpdb->prepare(
			"SELECT 
				ll.lead_email, ll.lead_id, MAX(lpv.pageview_date) AS pageview_date
			FROM 
				$wpdb->li_leads ll, $wpdb->li_pageviews lpv
			WHERE 
				lpv.pageview_url = %s AND 
				lpv.lead_hashkey = ll.hashkey AND 
				ll.lead_deleted = 0 AND 
				ll.lead_email != '' 
			GROUP BY 
				ll.lead_id
			ORDER BY 
				pageview_date DESC", $pageview_url);
		$this->viewers = $wpdb->get_results($q);
		return $this->viewers;
	}

	/**
     * Get identified readers from a url
     * @param string
     * @return array
     */
	function get_submissions ( $pageview_url )
	{
		global $wpdb;
		$q = $wpdb->prepare(
			"SELECT
				ll.lead_email, ll.lead_id, MAX(ls.form_date) AS form_date
			FROM 
				$wpdb->li_leads ll, $wpdb->li_submissions ls
			WHERE 
				ls.form_page_url = %s AND 
				ls.lead_hashkey = ll.hashkey AND 
				ll.lead_deleted = 0 AND 
				ls.form_deleted = 0 
			GROUP BY 
				ll.lead_id
			ORDER BY 
				form_date DESC", $pageview_url);
		$this->submissions = $wpdb->get_results($q);
		return $this->submissions;
	}
}