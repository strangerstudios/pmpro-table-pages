<?php
/*
Plugin Name: Paid Memberships Pro - Table Layout Plugin Pages
Plugin URI: https://www.paidmembershipspro.com/add-ons/table-layout-plugin-pages/
Description: An archived version of the table-based layouts for default Paid Memberships Pro pages, including the Checkout, Billing, and Confirmation pages
Version: .1
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/

/*
	Unless, there is a template in a theme directory, use the template from this plugin's templates folder
*/
function pmprotc_pmpro_pages_custom_template_path($templates, $page_name, $type, $where, $ext) {
	//if there is a template for this page inside of the theme, don't add our path so that is used instead
	$parent_theme_template = get_template_directory() . "/paid-memberships-pro/{$type}/{$page_name}.{$ext}";
	$child_theme_template = get_stylesheet_directory() . "/paid-memberships-pro/{$type}/{$page_name}.{$ext}";

	if(!file_exists($parent_theme_template) && 
	   !file_exists($child_theme_template) && 
	   file_exists(plugin_dir_path(__FILE__) . 'templates/' . $page_name . '.' . $ext))
		$templates[] = plugin_dir_path(__FILE__) . 'templates/' . $page_name . '.' . $ext;

	return $templates;
}
add_filter('pmpro_pages_custom_template_path', 'pmprotc_pmpro_pages_custom_template_path', 10, 5);

/*
	Unless, there is a frontend.css in a theme directory, use the css file from this plugin's css folder
*/
function pmprotc_wp_enqueue_scripts() {
	global $wp_styles;

	$style = wp_styles()->query( 'pmpro_frontend' );
	if(empty($style) || $style->src == PMPRO_URL . '/css/frontend.css') {
		wp_dequeue_style('pmpro_frontend');
		wp_enqueue_style('pmpro_frontend-tables', plugins_url('css/frontend.css', __FILE__), array(), PMPRO_VERSION . '-tables', "screen");
	}
}
add_action('wp_enqueue_scripts', 'pmprotc_wp_enqueue_scripts', 99);

/*
	Update Stripe and Braintree to use our method to draw the payment fields
*/
function pmprotc_init_update_gateways() {
	$default_gateway = pmpro_getOption('gateway');
	$current_gateway = pmpro_getGateway();
	if( ($default_gateway == "stripe" || $current_gateway == "stripe") && empty($_REQUEST['review'] ) )	{
		remove_filter('pmpro_include_payment_information_fields', array('PMProGateway_stripe', 'pmpro_include_payment_information_fields'));
		add_filter('pmpro_include_payment_information_fields', 'pmprotc_include_payment_information_fields_stripe');
	}

	if( ($default_gateway == "braintree" || $current_gateway == "braintree") && empty($_REQUEST['review'] ) )	{
		remove_filter('pmpro_include_payment_information_fields', array('PMProGateway_braintree', 'pmpro_include_payment_information_fields'));
		add_filter('pmpro_include_payment_information_fields', 'pmprotc_include_payment_information_fields_braintree');
	}
}
add_action('init', 'pmprotc_init_update_gateways', 15);

/*
	Old school Stripe payment information fields
*/
function pmprotc_include_payment_information_fields_stripe($include) {
	//global vars
	global $pmpro_requirebilling, $pmpro_show_discount_code, $discount_code, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
	
	//get accepted credit cards
	$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
	$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);
	$pmpro_accepted_credit_cards_string = pmpro_implodeToEnglish($pmpro_accepted_credit_cards);
	//include ours
	?>
	<table id="pmpro_payment_information_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0" <?php if(!$pmpro_requirebilling || apply_filters("pmpro_hide_payment_information_fields", false) ) { ?>style="display: none;"<?php } ?>>
	<thead>
		<tr>
			<th>
				<span class="pmpro_thead-name"><?php _e('Payment Information', 'paid-memberships-pro' );?></span>
				<span class="pmpro_thead-msg"><?php printf(__('We Accept %s', 'paid-memberships-pro' ), $pmpro_accepted_credit_cards_string);?></span>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr valign="top">
			<td>
				<?php
					$sslseal = pmpro_getOption("sslseal");
					if($sslseal)
					{
					?>
						<div class="pmpro_sslseal"><?php echo stripslashes($sslseal)?></div>
					<?php
					}
				?>
				<?php
					$pmpro_include_cardtype_field = apply_filters('pmpro_include_cardtype_field', false);
					if($pmpro_include_cardtype_field)
					{
					?>
					<div class="pmpro_payment-card-type">
						<label for="CardType"><?php _e('Card Type', 'paid-memberships-pro' );?></label>
						<select id="CardType" class=" <?php echo pmpro_getClassForField("CardType");?>">
							<?php foreach($pmpro_accepted_credit_cards as $cc) { ?>
								<option value="<?php echo $cc?>" <?php if($CardType == $cc) { ?>selected="selected"<?php } ?>><?php echo $cc?></option>
							<?php } ?>
						</select>
					</div>
				<?php
					}
					else
					{
					?>
					<input type="hidden" id="CardType" name="CardType" value="<?php echo esc_attr($CardType);?>" />
					<script>
						<!--
						jQuery(document).ready(function() {
								jQuery('#AccountNumber').validateCreditCard(function(result) {
									var cardtypenames = {
										"amex":"American Express",
										"diners_club_carte_blanche":"Diners Club Carte Blanche",
										"diners_club_international":"Diners Club International",
										"discover":"Discover",
										"jcb":"JCB",
										"laser":"Laser",
										"maestro":"Maestro",
										"mastercard":"Mastercard",
										"visa":"Visa",
										"visa_electron":"Visa Electron"
									}
									if(result.card_type)
										jQuery('#CardType').val(cardtypenames[result.card_type.name]);
									else
										jQuery('#CardType').val('Unknown Card Type');
								});
						});
						-->
					</script>
					<?php
					}
				?>

				<div class="pmpro_payment-account-number">
					<label for="AccountNumber"><?php _e('Card Number', 'paid-memberships-pro' );?></label>
                    <div id="AccountNumber"></div>
				</div>

                <div class="pmpro_checkout-field pmpro_payment-expiration">
                    <label for="Expiry"><?php _e( 'Expiration Date', 'paid-memberships-pro' ); ?></label>
                    <div id="Expiry"></div>
                </div>

				<?php
					$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
					if($pmpro_show_cvv)
					{							
				?>
                    <div>
                        <label for="CVV"><?php _ex('CVV', 'Credit card security code, CVV/CCV/CVV2', 'vibe');?></label>
                        <div id="CVV"></div>
                    </div>
				<?php
					}
				?>

				<?php if($pmpro_show_discount_code) { ?>
				<div class="pmpro_payment-discount-code">
					<label for="discount_code"><?php _e('Discount Code', 'paid-memberships-pro' );?></label>
					<input class="input <?php echo pmpro_getClassForField("discount_code");?>" id="discount_code" name="discount_code" type="text" size="20" value="<?php echo esc_attr($discount_code)?>" />
					<input type="button" id="discount_code_button" name="discount_code_button" value="<?php _e('Apply', 'paid-memberships-pro' );?>" />
					<p id="discount_code_message" class="pmpro_message" style="display: none;"></p>
				</div>
				<?php } ?>

			</td>
		</tr>
	</tbody>
	</table>
	<?php
	//don't include the default
	return false;
}

/*
	Old school Braintree payment information fields
*/
function pmprotc_include_payment_information_fields_braintree($include) {
	//global vars
	global $pmpro_requirebilling, $pmpro_show_discount_code, $discount_code, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
	
	//get accepted credit cards
	$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
	$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);
	$pmpro_accepted_credit_cards_string = pmpro_implodeToEnglish($pmpro_accepted_credit_cards);
	
	//include ours
	?>
	<table id="pmpro_payment_information_fields" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0" <?php if(!$pmpro_requirebilling || apply_filters("pmpro_hide_payment_information_fields", false) ) { ?>style="display: none;"<?php } ?>>
	<thead>
		<tr>
			<th>
				<span class="pmpro_thead-name"><?php _e('Payment Information', 'paid-memberships-pro' );?></span>
				<span class="pmpro_thead-msg"><?php printf(__('We Accept %s', 'paid-memberships-pro' ), $pmpro_accepted_credit_cards_string);?></span>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr valign="top">
			<td>
				<?php
					$sslseal = pmpro_getOption("sslseal");
					if($sslseal)
					{
					?>
						<div class="pmpro_sslseal"><?php echo stripslashes($sslseal)?></div>
					<?php
					}
				?>
				<?php
					$pmpro_include_cardtype_field = apply_filters('pmpro_include_cardtype_field', true);
					if($pmpro_include_cardtype_field)
					{
					?>
					<div class="pmpro_payment-card-type">
						<label for="CardType"><?php _e('Card Type', 'paid-memberships-pro' );?></label>
						<select id="CardType" name="CardType" class=" <?php echo pmpro_getClassForField("CardType");?>">
							<?php foreach($pmpro_accepted_credit_cards as $cc) { ?>
								<option value="<?php echo $cc?>" <?php if($CardType == $cc) { ?>selected="selected"<?php } ?>><?php echo $cc?></option>
							<?php } ?>
						</select>
					</div>
				<?php
					}
				?>
			
				<div class="pmpro_payment-account-number">
					<label for="AccountNumber"><?php _e('Card Number', 'paid-memberships-pro' );?></label>
					<input id="AccountNumber" name="AccountNumber" class="input <?php echo pmpro_getClassForField("AccountNumber");?>" type="text" size="25" value="<?php echo esc_attr($AccountNumber)?>" data-encrypted-name="number" autocomplete="off" />
				</div>
			
				<div class="pmpro_payment-expiration">
					<label for="ExpirationMonth"><?php _e('Expiration Date', 'paid-memberships-pro' );?></label>
					<select id="ExpirationMonth" name="ExpirationMonth" class=" <?php echo pmpro_getClassForField("ExpirationMonth");?>">
						<option value="01" <?php if($ExpirationMonth == "01") { ?>selected="selected"<?php } ?>>01</option>
						<option value="02" <?php if($ExpirationMonth == "02") { ?>selected="selected"<?php } ?>>02</option>
						<option value="03" <?php if($ExpirationMonth == "03") { ?>selected="selected"<?php } ?>>03</option>
						<option value="04" <?php if($ExpirationMonth == "04") { ?>selected="selected"<?php } ?>>04</option>
						<option value="05" <?php if($ExpirationMonth == "05") { ?>selected="selected"<?php } ?>>05</option>
						<option value="06" <?php if($ExpirationMonth == "06") { ?>selected="selected"<?php } ?>>06</option>
						<option value="07" <?php if($ExpirationMonth == "07") { ?>selected="selected"<?php } ?>>07</option>
						<option value="08" <?php if($ExpirationMonth == "08") { ?>selected="selected"<?php } ?>>08</option>
						<option value="09" <?php if($ExpirationMonth == "09") { ?>selected="selected"<?php } ?>>09</option>
						<option value="10" <?php if($ExpirationMonth == "10") { ?>selected="selected"<?php } ?>>10</option>
						<option value="11" <?php if($ExpirationMonth == "11") { ?>selected="selected"<?php } ?>>11</option>
						<option value="12" <?php if($ExpirationMonth == "12") { ?>selected="selected"<?php } ?>>12</option>
					</select>/<select id="ExpirationYear" name="ExpirationYear" class=" <?php echo pmpro_getClassForField("ExpirationYear");?>">
						<?php
							for($i = date_i18n("Y"); $i < date_i18n("Y") + 10; $i++)
							{
						?>
							<option value="<?php echo $i?>" <?php if($ExpirationYear == $i) { ?>selected="selected"<?php } ?>><?php echo $i?></option>
						<?php
							}
						?>
					</select>
				</div>
			
				<?php
					$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
					if($pmpro_show_cvv)
					{
				?>
				<div class="pmpro_payment-cvv">
					<label for="CVV"><?php _e('Security Code (CVC)', 'paid-memberships-pro' );?></label>
					<input class="input" id="CVV" name="cvv" type="text" size="4" value="<?php if(!empty($_REQUEST['CVV'])) { echo esc_attr(sanitize_text_field($_REQUEST['CVV'])); }?>" class=" <?php echo pmpro_getClassForField("CVV");?>" data-encrypted-name="cvv" />  <small>(<a href="javascript:void(0);" onclick="javascript:window.open('<?php echo pmpro_https_filter(PMPRO_URL)?>/pages/popup-cvv.html','cvv','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=600, height=475');"><?php _e("what's this?", 'paid-memberships-pro' );?></a>)</small>
				</div>
				<?php
					}
				?>
				
				<?php if($pmpro_show_discount_code) { ?>
				<div class="pmpro_payment-discount-code">
					<label for="discount_code"><?php _e('Discount Code', 'paid-memberships-pro' );?></label>
					<input class="input <?php echo pmpro_getClassForField("discount_code");?>" id="discount_code" name="discount_code" type="text" size="20" value="<?php echo esc_attr($discount_code)?>" />
					<input type="button" id="discount_code_button" name="discount_code_button" value="<?php _e('Apply', 'paid-memberships-pro' );?>" />
					<p id="discount_code_message" class="pmpro_message" style="display: none;"></p>
				</div>
				<?php } ?>
				
			</td>
		</tr>
	</tbody>
	</table>
	<?php
	
	//don't include the default
	return false;
}

/*
Function to add links to the plugin row meta
*/
function pmprotc_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-table-pages.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/table-layout-plugin-pages/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('https://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprotc_plugin_row_meta', 10, 2);