<?php
/*
Plugin Name: Hover Pin-It
Plugin URI: http://www.fromideatoempire.com
Description: Places a Pinterest Pin It button directly over your images.
Author: Michelle MacPhearson
Author URI: http://www.fromideatoempire.com
Version: 1.1
*/

require_once(dirname( __FILE__ ) . '/lib/simple_html_dom.php');

add_action( 'wp_enqueue_scripts', 'pin_it_buttons_enqueue_script' );
function pin_it_buttons_enqueue_script()
{
	// Register the scripts
	wp_register_script( 'pinit_script', plugins_url('/hover-pin-it.js', __FILE__), array('jquery') );
	wp_enqueue_script( 'pinit_script' );

}

add_filter('the_content', 'pin_it_buttons_add');
function pin_it_buttons_add($content) {

	global $wp_query;

	$options    = get_option('pin_it_buttons_options');
	$post_url   = rawurlencode(get_permalink()); 
	$post_title = rawurlencode(get_the_title());
	$pin_count  = $options['pinit_pincount']; // Position of the pin counter
	//$pin_button    = '<span class="pinit-button-'.$pin_count.'"><a href="http://pinterest.com/pin/create/button/?url=%s&media=%s&description=%s" class="pin-it-button" count-layout="%s"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a></span>';

	// The shortcodes run after the filter. So we'll check for them in $content instead.
	$pinit = $options['pinit_pinit'];
	if ( strpos($content, '[nopinit]') )
			$pinit = false;
	if ( strpos($content, '[dopinit]') )
			$pinit = true;

	// First see if we want to show pins according to the settings.
	$options = get_option('pin_it_buttons_options');
	if( ( $options['pinit_show_on_frontpage'] == 0 && is_front_page() ) OR 
			( $options['pinit_show_on_frontpage'] == 0 && is_home() ) OR 
			( $options['pinit_show_on_pages'] == 0 && is_page() ) OR
			( $options['pinit_show_on_posts'] == 0 && is_single() ) OR 
			is_admin() OR ($pinit==false) ) 
					return $content;

	// Tag all images
	$html = str_get_html($content, true, true, DEFAULT_TARGET_CHARSET, false );

	foreach($html->find('img') as $e)
	    $e->class .= ' pin-it';

	$content = $html;

	//$html->clear(); 
	unset($html);

	return $content;

}

// Output the pin-it jQuery settings to the document head.
add_action('wp_head', 'pin_it_print_script', 100);
function pin_it_print_script() {

	$options      = get_option('pin_it_buttons_options');
	$pin_count    = $options['pinit_pincount']; 
	$pin_location = $options['pinit_location']; 
	$min_size     = $options['pinit_minsize']; 
	$pin_text     = $options['pinit_text'];
  
	echo '<script>
	jQuery(window).load(function(){
		jQuery().pinit({
			selector: ".pin-it",
			align: "'. $pin_location .'",
			minSize: "'. $min_size .'",
			fadeSpeed: 200,
			opacity: 1,
			pinCount: "'. $pin_count .'",
			pinText: "'. $pin_text .'",
			';
	echo ($pin_location == "bottomLeft" || $pin_location == "bottomRight") ? 'offsetBottom: "50"' : 'offsetBottom: "10"';
	echo '
		});
	});
</script>
';

}

add_shortcode( 'nopinit', 'pin_it_buttons_shortcode_nopinit');
function pin_it_buttons_shortcode_nopinit() {
}

add_shortcode( 'dopinit', 'pin_it_buttons_shortcode_dopinit');
function pin_it_buttons_shortcode_dopinit() {
}

// Set-up Action and Filter Hooks
register_activation_hook(__FILE__, 'pin_it_buttons_add_defaults');
register_uninstall_hook(__FILE__, 'pin_it_buttons_delete_plugin_options');
add_action('admin_init', 'pin_it_buttons_init' );
add_action('admin_menu', 'pin_it_buttons_add_options_page');
add_filter( 'plugin_action_links', 'pin_it_buttons_plugin_action_links', 10, 2 );

// --------------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: register_uninstall_hook(__FILE__, 'pin_it_buttons_delete_plugin_options')
// --------------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE USER DEACTIVATES AND DELETES THE PLUGIN. IT SIMPLY DELETES
// THE PLUGIN OPTIONS DB ENTRY (WHICH IS AN ARRAY STORING ALL THE PLUGIN OPTIONS).
// --------------------------------------------------------------------------------------

// Delete options table entries ONLY when plugin deactivated AND deleted
function pin_it_buttons_delete_plugin_options() {
	delete_option('pin_it_buttons_options');
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: register_activation_hook(__FILE__, 'pin_it_buttons_add_defaults')
// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE PLUGIN IS ACTIVATED. IF THERE ARE NO THEME OPTIONS
// CURRENTLY SET, OR THE USER HAS SELECTED THE CHECKBOX TO RESET OPTIONS TO THEIR
// DEFAULTS THEN THE OPTIONS ARE SET/RESET.
//
// OTHERWISE, THE PLUGIN OPTIONS REMAIN UNCHANGED.
// ------------------------------------------------------------------------------

// Define default option settings
function pin_it_buttons_add_defaults() {
	$tmp = get_option('pin_it_buttons_options');
    if(($tmp['pinit_default_options']=='1')||(!is_array($tmp))) {
		delete_option('pin_it_buttons_options'); 
		$arr = array(	"pinit_pinit" => "true",
						"pinit_show_on_pages" => "1",
						"pinit_show_on_posts" => "1",
						"pinit_minsize" => "150",
						"pinit_default_options" => "",
						"pinit_pincount" => "vertical",
						"pinit_location" => "topLeft",
						"pinit_text" => "doctitle"
		);
		update_option('pin_it_buttons_options', $arr);
	}
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_init', 'pin_it_buttons_init' )
// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_init' HOOK FIRES, AND REGISTERS YOUR PLUGIN
// SETTING WITH THE WORDPRESS SETTINGS API. YOU WON'T BE ABLE TO USE THE SETTINGS
// API UNTIL YOU DO.
// ------------------------------------------------------------------------------

// Init plugin options to white list our options
function pin_it_buttons_init(){
	register_setting( 'pin_it_buttons_plugin_options', 'pin_it_buttons_options', 'pin_it_buttons_validate_options' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_menu', 'pin_it_buttons_add_options_page');
// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_menu' HOOK FIRES, AND ADDS A NEW OPTIONS
// PAGE FOR YOUR PLUGIN TO THE SETTINGS MENU.
// ------------------------------------------------------------------------------

// Add menu page
function pin_it_buttons_add_options_page() {
	add_options_page('Hover Pin-It Options Page', 'Hover Pin-It', 'manage_options', __FILE__, 'pin_it_buttons_render_form');
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION SPECIFIED IN: add_options_page()
// ------------------------------------------------------------------------------
// THIS FUNCTION IS SPECIFIED IN add_options_page() AS THE CALLBACK FUNCTION THAT
// ACTUALLY RENDER THE PLUGIN OPTIONS FORM AS A SUB-MENU UNDER THE EXISTING
// SETTINGS ADMIN MENU.
// ------------------------------------------------------------------------------

// Render the Plugin options form
function pin_it_buttons_render_form() {
	?>
<div style="position:absolute; top:100px;right:25px; display:inline; margin:0 15px 10px 25px; padding:5px; background-color:#ffffcc; border:1px solid #ddddaa;">
			<img src="<?php echo plugins_url('/pinterest.png', __FILE__)  ?>" width="200px" height="50px"/>
			<p>Hover Pin-It</p>
			<p>Developed by <a href="http://www.fromideatoempire.com" target="_blank">Michelle MacPhearson</a></p>
			<hr>
			<h2>Contents</h2>
			<ul>
				<li><a href="#settings">Settings</a></li>
				<li><a href="#documentation">Documentation</a></li>
				<li><a href="#troubleshooting">Troubleshooting</a></li>
			</ul>
		</div>
	<a name="settings"></a><div class="wrap">
		<!-- Display Plugin Icon, Header, and Description -->
		<div id="icon-edit" class="icon32"><br></div>
		<h2>Hover Pin-It Settings</h2><br />

		<!-- Beginning of the Plugin Options Form -->
		<form method="post" action="options.php">
			<?php settings_fields('pin_it_buttons_plugin_options'); ?>
			<?php $options = get_option('pin_it_buttons_options'); ?>

			<!-- Table Structure Containing Form Controls -->
			<!-- Each Plugin Option Defined on a New Table Row -->
			<table class="form-table">

				<!-- Select Drop-Down Control -->
				<tr>
					<th scope="row">Pin Count position</th>
					<td>
						<select name='pin_it_buttons_options[pinit_pincount]' style='float:left'>
							<option value='vertical' <?php selected('vertical', $options['pinit_pincount']); ?>>Vertical</option>
							<option value='horizontal' <?php selected('horizontal', $options['pinit_pincount']); ?>>Horizontal</option>
							<option value='none' <?php selected('none', $options['pinit_pincount']); ?>>No Count</option>
						</select>
						<img style="height:80px;width:244px;float:left;margin:-20px 0 0 20px;" src="<?php echo plugins_url( 'pin-it-button-previews.png' , __FILE__ ); ?>">
					</td>
				</tr>

				<!-- Another Radio Button Group -->
				<tr valign="top">
					<th scope="row">Hover Pin-It Activation</th>
					<td>
						<label><input name="pin_it_buttons_options[pinit_pinit]" type="radio" value="true" <?php checked('true', $options['pinit_pinit']); ?> /> Enabled:</label> Override this setting with the <b>[nopinit]</b> <a href="#shortcodes">shortcode</a>.<br />
						<label><input name="pin_it_buttons_options[pinit_pinit]" type="radio" value="false" <?php checked('false', $options['pinit_pinit']); ?> /> Disabled:</label> Override this setting with the <b>[dopinit]</b> <a href="#shortcodes">shortcode</a>.<br />
					</td>
				</tr>

				<!-- Checkbox Buttons -->
				<tr valign="top">
					<th scope="row">Load Pin It Buttons</th>
					<td>
						<!-- First checkbox button -->
						<label><input name="pin_it_buttons_options[pinit_show_on_frontpage]" type="checkbox" value="1" <?php if (isset($options['pinit_show_on_frontpage'])) { checked('1', $options['pinit_show_on_frontpage']); } ?> /> on the Front page</label><br />

						<!-- Second checkbox button -->
						<label><input name="pin_it_buttons_options[pinit_show_on_posts]" type="checkbox" value="1" <?php if (isset($options['pinit_show_on_posts'])) { checked('1', $options['pinit_show_on_posts']); } ?> /> on posts</label><br />

						<!-- Third checkbox button -->
						<label><input name="pin_it_buttons_options[pinit_show_on_pages]" type="checkbox" value="1" <?php if (isset($options['pinit_show_on_pages'])) { checked('1', $options['pinit_show_on_pages']); } ?> /> on pages</label><br />
					</td>
				</tr>

				<!-- Another Radio Button Group -->
				<tr valign="top">
					<th scope="row">Pin It Location</th>
					<td>
						<label><input name="pin_it_buttons_options[pinit_location]" type="radio" value="topLeft" <?php checked('topLeft', $options['pinit_location']); ?> /> Top Left</label><br />
						<label><input name="pin_it_buttons_options[pinit_location]" type="radio" value="topRight" <?php checked('topRight', $options['pinit_location']); ?> /> Top Right</label><br />
						<label><input name="pin_it_buttons_options[pinit_location]" type="radio" value="bottomLeft" <?php checked('bottomLeft', $options['pinit_location']); ?> /> Bottom Left</label><br />
						<label><input name="pin_it_buttons_options[pinit_location]" type="radio" value="bottomRight" <?php checked('bottomRight', $options['pinit_location']); ?> /> Bottom Right</label>
					</td>
				</tr>
				
				<!-- Textbox Control -->
				<tr>
					<th scope="row">Pin images larger than</th>
					<td>
						<input type="text" size="8" name="pin_it_buttons_options[pinit_minsize]" value="<?php echo $options['pinit_minsize']; ?>" /> px
					</td>
				</tr>

				<!-- Another Radio Button Group -->
				<tr valign="top">
					<th scope="row">Pin It Text</th>
					<td>
						<label><input name="pin_it_buttons_options[pinit_text]" type="radio" value="doctitle" <?php checked('doctitle', $options['pinit_text']); ?> /> Page Title</label><br />
						<label><input name="pin_it_buttons_options[pinit_text]" type="radio" value="alttext" <?php checked('alttext', $options['pinit_text']); ?> /> Image Alt Text</label><br />
					</td>
				</tr>

				
				<tr><td colspan="2"><div style="margin-top:10px;"></div></td></tr>
				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row">Restore Options</th>
					<td>
						<label><input name="pin_it_buttons_options[pinit_default_options]" type="checkbox" value="1" <?php if (isset($options['pinit_default_options'])) { checked('1', $options['pinit_default_options']); } ?> /> Restore defaults upon plugin deactivation/reactivation</label>
						<br /><span style="color:#666666;margin-left:2px;">Only check this if you want to reset plugin settings upon Plugin reactivation</span>
					</td>
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
<hr />
	</div>
	
		<a name="documentation"></a><div class="wrap">
		<!-- Display Plugin Icon, Header, and Description -->
		<div id="icon-edit-pages" class="icon32"><br></div>
		<h2>Hover Pin-It Documentation</h2><br />

		<a name="shortcodes"></a><br /><h3>Shortcodes</h3>
		<p>The Hover Pin-It plugin recognizes two shortcodes. More information on the use of shortcodes in general can 
			be found on the <a href="http://codex.wordpress.org/Shortcode" target="_blank">WordPress website</a>.</p>
		<h4>[nopinit]</h4>
		<p>The [nopinit] shortcode can be used when the Hover Pin-It Activation setting is Enabled.<br />
			This shortcode ensures that no pin buttons are activated on the post or page where the shortcode is used.</p>
		<h4>[dopinit]</h4>
		<p>The [dopinit] shortcode can be used when the Hover Pin-It Activation setting is Disabled.<br />
			This shortcode overrides the Activation settings for the the post or page where the shortcode is used.</p>

		<h3>Settings</s3>
		
		<h4>Pin Count position</h4>
		<p>The Pin It button is made to look and feel like the Facebook and Twitter buttons your readers already use.
		<br />You can choose between three different button sizes: vertical, horizontal or a button without pin count.</p>

		<h4>Hover Pin-It Activation</h4>
		<p>This setting disabled pin buttons for all post and pages except where the [dopinit] shortcode is used.
		 Use this if you want to only want pin buttons activated on selected posts and pages. 
		<br />When pin buttons are activated with a shortcode all other settings apply.</p>
		
		<h4>Load Pin It Buttons</h4>
		<p>Select where you want the Pin It buttons to be displayed. 
			<br />Selecting Posts also causes the buttons to be displayed on
			the post archive pages.</p>
			
		<h4>Pin It Location</h4>
		<p>The Pin It button becomes visible when the user moves the mouse cursor over an image. 
			<br />Select in which corner of the image you want 
	the button to be displayed.</p>
		
		<h4>Pin images larger than</h4>
		<p>Set the minimum size for images to be pinned. The default is set to the default size of Wordpress
			 thumbnails to prevent thumbnails from being pinned. 
			 <br />Change this setting if you have a different thumbnail size. It's not recommended to set this smaller than 75px.</p>
			 
		<h4>Restore Options</h4>
		<p>Check this box if you wish to restore the default settings. Then save the settings and browse to the Plugins menu and 
		deactive the plugin. 
	<br>After reactivating the plugin the default settings will be restored.</p>
		<hr>

	</div>



		<a name="troubleshooting"></a><div class="wrap">
		<!-- Display Plugin Icon, Header, and Description -->
		<div id="icon-plugins" class="icon32"><br></div>
		<h2>Troubleshooting</h2><br>

		<h4>The pins are not showing on my images.</h4>
		<p>There can be several reasons why pins don't appear on your images. Here are the most common solutions.</p>
		<ol>
			<li>Outdated software: Hover Pin-It is compatible with WordPress version 3.4 or higher and PHP 5+. If you have an older version installed 
			please contact your hosting provider for an update.</li>
			<li>Conflict with another plugin: To function properly the plugin relies on jQuery v1.7.2 which is included in the WordPress installation. Some 
				plugins force jQuery to be replaced by an older version, which can cause other plugins to break. To test this deactivate any other plugins you have installed 
			and check if the pins are working. If this is the case contact the developer of the conflicting plugin for an update.</li>
			<li>Conflict with a custom theme: In some cases a custom theme can override the style of the hover pins, causing them to be repositioned or not visible at all. 
				You can test this by temporarily switching to the default WordPress theme. If your theme is the cause of the problem then remove the conflicting style from your theme's 
			stylesheet.</li>
			<li>Older browser or browser add-on: The plugin is compatible with all major browsers and their current version. For a complete list of compatible browsers, please check the 
				<a target="_blank" href="http://docs.jquery.com/Browser_Compatibility">jQuery Browser Compatibility page</a>. Also disable any browser add-ons which might block the use of 
				scripts.</li>
			<li>Hover Pin-It Settings: Check the settings above to be sure the pins are activated on the blog post or page your are viewing. The default setting will not show pins on the front 
			page or on images smaller than 150 pixels. Also be sure the pins haven't been disabled by the [nopinit] <a href="#shortcodes">shortcode</a>.</li>
		</ol>
		
		<h4>There are duplicate pins on a page.</h4>
		<p>Hover Pin-It adds a pin to each image in your page content which is larger than the minimum image size according to the settings. In certain cases where there are 
			overlapping or hidden images this can cause problems, such as an image gallery or a portfolio page. For these pages you can disable the plugin by adding the [nopinit] <a href="#shortcodes">shortcode</a>.
		</p>
		
		<h4>A form or button on my page stopped working.</h4>
		<p>If a form or button is placed over an image or if a background image is used to style a form, then the pin can cover the button or form controls which can cause them to stop working. 
			If possible replace the image with a css background image. Otherwise disable the plugin for that particular page by adding the [nopinit] <a href="#shortcodes">shortcode</a>.
		</p>

		<br /><hr/>
		</div>


	<?php	
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function pin_it_buttons_validate_options($input) {
	$input['pinit_minsize'] = trim($input['pinit_minsize']);
	if (substr($input['pinit_minsize'],0 ,2) == "px")
				$input['pinit_minsize'] = substr($input['pinit_minsize'],0 ,-2);
				
	$input['pinit_minsize'] = (int) $input['pinit_minsize'];

	if ($input['pinit_minsize'] < 0 )
				$input['pinit_minsize'] = 0;
	
	return $input;
}

// Display a Settings link on the main Plugins page
function pin_it_buttons_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$pin_it_buttons_links = '<a href="'.get_admin_url().'options-general.php?page=hover-pin-it/hover-pin-it.php">Settings</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $pin_it_buttons_links );
	}

	return $links;
}


?>