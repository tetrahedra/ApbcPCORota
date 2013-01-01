<?php
/*
WordPress options page for the ApbcPCORota plugin
*/

require_once "common.php";

//add sub-menu to the Options menu
add_action('admin_menu', 'pco_menu_fn');
add_action('admin_init', 'pco_options_init_fn' );

// Add sub page to the Settings Menu
function pco_menu_fn() {
	add_options_page('PCO Options Page', 'PCO Options', 'administrator', __FILE__, 'pco_options_page_fn');
}

function pco_options_page_fn() {

?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Planning Center Online Options Page</h2>
		<p>These options store your security keys for using Planning Center Online.</p>
		<p>See <a href="">Planning Center API documentation</a> for more details.</p>
		<p>As mentioned in the API documentation, the API is accessed through a normal Planning Center account. No special API account is needed to 
		access the API but you might want to create an API person solely to access the API. This person should at least be a Viewer.</p>
		<p>Planning Center Online uses OAuth 1.0a for authentication. To request a <strong>Consumer Key and Secret</strong> please e-mail 
		<a href="mailto:support@planningcenteronline.com">support@planningcenteronline.com</a> with your product name, description, 
		company name and contact information.</p>
		
<?php
		//check if we already have keys
		$options = get_option('pco_plugin_options');
	
		if (!$options['pco_key']) {
			echo "<div class='error'>";
			echo "<p>You don't have any authentication keys...</p>";
			echo "<p>You can get PCO authentication keys by emailing <a href='mailto:support@planningcenteronline.com'>support@planningcenteronline.com</a>, then enter the keys in the boxes below.</p>";
			echo "</div>";
		}
		elseif (!$options['pco_tokenkey']) {
			
			$request_options = get_option('pco_request');
			$oauth_verifier = $_GET['oauth_verifier'];
			
			if ($request_options['pco_oauth_token'] && $oauth_verifier) {
			
				echo "<div class='updated'>";
				echo "<p>Getting and setting the Access token...</p>";
				echo "</div>";
				getAndSetAccessToken($options,$request_options);
				
				//test to see if it has worked
				$options = get_option('pco_plugin_options');
				if ($options['pco_tokenkey']) {
					//reload the page
					?>
					<script type="text/javascript" language="javascript">
						window.location.reload();
					</script>
					<?php
				}
			}
			else {
				//post a warning
				echo "<div class='error'>";
				echo "<p>You don't have the tokens required, you will need to authorise your application. Click on the Authorize button below.</p>";
				echo "<p>This will open a new window. If you're not already logged into Planning Center, you will be asked to log in and 
						then authorise this plugin to use your account. You will be redirected back to this page once this has been
						completed.</p>";
				echo "</div>";
				
				//get the request token
				$auth_url = getAndSetRequestToken($options);
				//echo "Auth_url = " . $auth_url;
				//show the authorize button
				?>
				<p>
				<script type="text/javascript" language="javascript">
					/**
					 * Display the PCO authorize url, hide the authorize button and then show the continue button.
					 * @param url
					 */
					function pco_authorize(url) {
						window.open(url);
						window.close();
					}
				</script>
				<form id="pco_continue" name="pco_continue"
					  action="options-general.php?page=ApbcPCORota/pco_options.php" method="post">
					<input type="button" name="authorize" id="authorize" class="button-primary" value="<?php _e('Authorize', 'wpbtd'); ?>"
						   onclick="window.location.href='<?php echo $auth_url ?>'"/><br/>
				</form>
				</p>
				<?php
			}
		}
		else {
			echo "<div class='updated'>";
			echo "<p>That's it! You have all the keys you need...you're ready to use the plugin and widget!</p>";
			echo "</div>";
		}
		
?>
		<form action="options.php" method="post">
			<?php settings_fields('pco_plugin_options'); ?>
			<?php do_settings_sections(__FILE__); ?>
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
			</p>
		</form>
		
		<h3>Acknowledgements</h3>
		<p>This plugin is based on <a href="https://github.com/ministrycentered/planningcenteronline-php-api-example">sample PHP 
		code</a> developed by Planning Center Online, and on 
		<a href="http://mg.mkgarrison.com/2010/08/using-planningcenteronlinecoms-api-with.html">work by Michael Garrison</a>.</p>
	</div>
<?php
}

// Register our settings. Add the settings section, and settings fields
function pco_options_init_fn(){
	register_setting('pco_plugin_options', 'pco_plugin_options', 'pco_plugin_options_validate' );
	add_settings_section('main_section', 'Main Settings', 'pco_section_text_fn', __FILE__);
	add_settings_field('pco_key', 'PCO Consumer Key', 'setting_pco_key_fn', __FILE__, 'main_section');
	add_settings_field('pco_secret', 'PCO Consumer Secret', 'setting_pco_secret_fn', __FILE__, 'main_section');
	//add_settings_field('pco_tokenkey', 'PCO Token Key', 'setting_pco_tokenkey_fn', __FILE__, 'main_section');
	//add_settings_field('pco_tokensecret', 'PCO Token Secret', 'setting_pco_tokensecret_fn', __FILE__, 'main_section');
}


// Callback functions
// Section HTML, displayed before the first option
function  pco_section_text_fn() {
	echo '<p>Enter your key and secret below.</p>';
}

// TEXTBOX - Name: plugin_options[text_string]
function setting_pco_key_fn() {
	$options = get_option('pco_plugin_options');
	echo "<input id='pco_key' name='pco_plugin_options[pco_key]' size='50' type='text' value='{$options['pco_key']}' />";
}

function setting_pco_secret_fn() {
	$options = get_option('pco_plugin_options');
	echo "<input id='pco_secret' name='pco_plugin_options[pco_secret]' size='50' type='text' value='{$options['pco_secret']}' />";
}

function setting_pco_tokenkey_fn() {
	$options = get_option('pco_plugin_options');
	echo "<input id='pco_tokenkey' name='pco_plugin_options[pco_tokenkey]' size='50' type='text' value='{$options['pco_tokenkey']}' disabled />";
}

function setting_pco_tokensecret_fn() {
	$options = get_option('pco_plugin_options');
	echo "<input id='pco_tokensecret' name='pco_plugin_options[pco_tokensecret]' size='50' type='text' value='{$options['pco_tokensecret']}' disabled />";
}

//Validation options

// Validate user data for some/all of your input fields
function pco_plugin_options_validate($input) {
	//firstly, remove existing options to clear the database. 
	//This avoids forces reauthorisation on every save and avoids getting things in a twist
	delete_option('pco_plugin_options');
	delete_option('pco_request');
	
	// Check our textbox option field contains no HTML tags - if so strip them out
	$input['pco_key'] =  wp_filter_nohtml_kses($input['pco_key']);	
	$input['pco_secret'] =  wp_filter_nohtml_kses($input['pco_secret']);	
	//$input['pco_tokenkey'] =  wp_filter_nohtml_kses($input['pco_tokenkey']);	
	//$input['pco_tokensecret'] =  wp_filter_nohtml_kses($input['pco_tokensecret']);	
	return $input; // return validated input
}

?>