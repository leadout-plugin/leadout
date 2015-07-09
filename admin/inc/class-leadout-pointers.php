<?php

if ( !defined('LEADOUT_PLUGIN_VERSION') ) 
{
    header('HTTP/1.0 403 Forbidden');
    die;
}

/**
 * This class handles the pointers used in the introduction tour.
 *
 * @todo Add an introdutory pointer on the edit post page too.
 */
class LI_Pointers {

	/**
	 * Class constructor.
	 */
	function __construct () 
	{
		//=============================================
		// Hooks & Filters
		//=============================================


		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue styles and scripts needed for the pointers.
	 */
	function enqueue () 
	{


		if ( !current_user_can( 'manage_options' ) )
			return;



		$options = get_option('leadin_options');

		if ( !isset($options['ignore_settings_popup']) || !$options['ignore_settings_popup'] )
		{
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_script( 'utils' );

			add_action('admin_print_footer_scripts', array( $this, 'li_settings_popup'));
		}
	}

	/**
	 * Shows a popup that asks for permission to allow tracking.
	 */
	function li_settings_popup() {
		$id    = '#toplevel_page_leadout_stats';
		$nonce = wp_create_nonce( 'wpseo_activate_tracking' );

		$content = '<h3>' . __( 'So close...', 'leadout' ) . '</h3>';
		$content .= '<p>' . __( 'LeadOut needs just a bit more info to get your contact tracking up and running. Click on \'Go to settings\' to complete the setup.', 'leadout' ) . '</p>';
		
		$opt_arr = array(
			'content'  => $content,
			'position' => array( 'edge' => 'left', 'align' => 'center' )
		);

		$function2 = 'li_redirect_to_settings()';

		$this->print_scripts($id, $opt_arr, 'Go to settings', FALSE, '', $function2);
	}

	/**
	 * Prints the pointer script
	 *
	 * @param string      $selector         The CSS selector the pointer is attached to.
	 * @param array       $options          The options for the pointer.
	 * @param string      $button1          Text for button 1
	 * @param string|bool $button2          Text for button 2 (or false to not show it, defaults to false) 
	 * @param string      $button2_function The JavaScript function to attach to button 2
	 * @param string      $button1_function The JavaScript function to attach to button 1
	 */
	function print_scripts( $selector, $options, $button1, $button2 = FALSE, $button2_function = '', $button1_function = '' ) 
	{
		?>
		<script type="text/javascript">
			//<![CDATA[
			(function ($) {

				var li_pointer_options = <?php echo json_encode( $options ); ?>, setup;

				function li_redirect_to_settings() {
					window.location.href = "<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin.php?page=leadout_settings";
				}
 
				li_pointer_options = $.extend(li_pointer_options, {
					buttons: function (event, t) {
						button = jQuery('<a id="pointer-close" style="margin-left:5px" class="button-secondary">' + '<?php echo $button1; ?>' + '</a>');
						button.bind('click.pointer', function () {
							window.location.href = "<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin.php?page=leadout_settings";
							//t.element.pointer('close');
						});
						return button;
					},
					close  : function () {
					}
				});

				setup = function () {
					$('<?php echo $selector; ?>').pointer(li_pointer_options).pointer('open');
				};

				if (li_pointer_options.position && li_pointer_options.position.defer_loading)
					$(window).bind('load.wp-pointers', setup);
				else
					$(document).ready(setup);
			})(jQuery);
			//]]>

		</script>
	<?php
	}
}
