<?php
/*
Plugin Name: Paid Memberships Pro - Table Layout Plugin Pages
Plugin URI: http://www.paidmembershipspro.com/add-ons/table-pages/
Description: An archived version of the table-based layouts for default Paid Memberships Pro pages, including the Checkout, Billing, and Confirmation pages
Version: .1
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/

//use our billing template
function pmprotc_pmpro_pages_shortcode_billing($content)
{
	ob_start();
	include(plugin_dir_path(__FILE__) . 'templates/billing.php');
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_filter('pmpro_pages_shortcode_billing', 'pmprotc_pmpro_pages_shortcode_billing');

//use our checkout template
function pmprotc_pmpro_pages_shortcode_checkout($content)
{
	ob_start();
	include(plugin_dir_path(__FILE__) . 'templates/checkout.php');
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_filter('pmpro_pages_shortcode_checkout', 'pmprotc_pmpro_pages_shortcode_checkout');

//use our confirmation template
function pmprotc_pmpro_pages_shortcode_confirmation($content)
{
	ob_start();
	include(plugin_dir_path(__FILE__) . 'templates/confirmation.php');
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_filter('pmpro_pages_shortcode_confirmation', 'pmprotc_pmpro_pages_shortcode_confirmation');

/*
Function to add links to the plugin row meta
*/
function pmprotc_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-table-pages.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/table-pages/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprotc_plugin_row_meta', 10, 2);