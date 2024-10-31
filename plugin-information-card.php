<?php
/*
Plugin Name: Plugin Information Card
Plugin URI: https://www.cmswp.jp/plugins/plugin_information_card/
Description: This plugin adds the functionality to output information about plugins in the WordPress plugin directory.
Author: Hiroaki Miyashita
Version: 1.0.1
Author URI: https://www.cmswp.jp/
Text Domain: plugin-information-card
*/

/*  Copyright 2018 Hiroaki Miyashita

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class plugin_information_card {

	function __construct() {
		register_activation_hook( __FILE__, array(&$this, 'plugin_information_card_register_activation_hook') );
		add_action( 'plugins_loaded', array(&$this, 'plugin_information_card_plugins_loaded') );
		add_action( 'admin_menu', array(&$this, 'plugin_information_card_admin_menu') );
		add_action( 'admin_init', array(&$this, 'plugin_information_card_admin_init') );
		add_shortcode( 'pic', array(&$this, 'plugin_information_card_add_shortcode') );
	}
	
	function plugin_information_card_register_activation_hook() {
		$pic_format = get_option('pic_format');
		if ( empty($pic_format) ) :
			$pic_format = '<div class="plugin-card plugin-card-%slug%">
<div class="plugin-card-top">
<div class="name column-name"><h3><a href="%plugin_link%">%name%<img src="%icon%" class="plugin-icon" alt="%name%"></a></h3></div>
<div class="desc column-description">%short_description%<p class="authors"><cite>'.__('Author', 'plugin-information-card').': %author%</cite></p></div>
</div>
<div class="plugin-card-bottom">
<div class="vers column-rating">
<div class="star-rating">%rating%</div><span class="num-ratings" aria-hidden="true">(%num_ratings%)</span>
</div>
<div class="column-updated">'.__('Last Updated', 'plugin-information-card').': %last_updated%</div>
<div class="column-downloaded">'.__('Active Installs', 'plugin-information-card').': %active_installs%</div>
<div class="column-compatibility">'.__('Downloaded', 'plugin-information-card').': %downloaded%</div>
</div>
</div>';
			$pic_css = '.plugin-card  { background: #32373c; color: #bbc8d4; }
.plugin-card a { color: #30ceff; }
.plugin-card-top { position: relative; padding: 20px 20px 10px; min-height: 135px; }
.plugin-card .desc, .plugin-card .name { margin-left: 148px; }
.plugin-card .plugin-icon { position: absolute; top: 20px; left: 20px; width: 128px; height: 128px; margin: 0 20px 20px 0; }
.plugin-card .authors cite { font-style: normal; }
.plugin-card-bottom { clear: both; padding: 12px 20px; background-color: #191f25; overflow: hidden; }
.plugin-card .star-rating .star { display: inline-block; width: 20px; height: 20px; -webkit-font-smoothing: antialiased; font-size: 20px; line-height: 1; font-family: dashicons; text-decoration: inherit; font-weight: 400; font-style: normal; vertical-align: top; transition: color .1s ease-in; text-align: center; color: #ffb900; }
.plugin-card .star-rating .star-half::before { content: "\f459"; }
.plugin-card .star-rating .star-full::before { content: "\f155"; }
.plugin-card .star-rating .star-empty:before { content: "\f154"; }
.plugin-card .column-downloaded, .plugin-card .column-rating { float: left; clear: left; max-width: 200px; }
.plugin-card-bottom .star-rating { display: inline; }
.plugin-card .column-compatibility, .plugin-card .column-updated { text-align: right; float: right; clear: right; width: 65%; width: calc(100% - 200px); }';
			update_option( 'pic_format', $pic_format );
			update_option( 'pic_css', $pic_css );
			update_option( 'pic_days', 7 );		
		endif;		
	}
	
	function plugin_information_card_plugins_loaded() {
		load_plugin_textdomain( 'plugin-information-card', false, dirname( plugin_basename(__FILE__) ) );
	}
	
	function plugin_information_card_admin_menu() {
		add_options_page(__('Plugin Information Card', 'plugin-information-card'), __('Plugin Information Card', 'plugin-information-card'), 'manage_options', basename(__FILE__), array(&$this, 'plugin_information_card_settings'));
	}
	
	function plugin_information_card_admin_init() {
		register_setting( 'plugin-information-card', 'pic_format' );
		register_setting( 'plugin-information-card', 'pic_css' );
		register_setting( 'plugin-information-card', 'pic_days' );
	}
	
	function plugin_information_card_add_shortcode( $atts ) {
		$pic_format = get_option('pic_format');
		$pic_css = get_option('pic_css');
		$pic_days = get_option('pic_days');
				
		if ( empty($atts['slug']) ) return;
		
		if ( false === ( $plugins = get_transient( 'pic_'.$atts['slug'] ) ) || $pic_days == 0 ) :
			require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
			$action = 'plugin_information';
			$args = array();
			$args['slug'] = $atts['slug'];
			$args['fields'] = array('downloaded' => true, 'active_installs' => true, 'short_description' => true, 'description' => true, 'compatibility' => true, 'icons' => true, 'group' => true);
			$plugins = plugins_api($action, $args);
			if ( $pic_days > 0 ) set_transient( 'pic_'.$atts['slug'], $plugins, $pic_days * DAY_IN_SECONDS );
		endif;
		
		if ( empty($plugins) ) return;
		wp_enqueue_style( 'dashicons' );
		
		if ( is_array($plugins->icons) ) :
			foreach ( $plugins->icons as $icon ) :
				$plugins->icon = $icon;
			endforeach;
		endif;

		if ( is_array($plugins->banners) ) :
			foreach ( $plugins->banners as $banner ) :
				$plugins->banner = $banner;
			endforeach;
		endif;

		if ( $plugins->active_installs >= 1000000 ) {
			$plugins->active_installs = __( '1+ Million', 'plugin-information-card' );
		} elseif ( 0 == $plugins->active_installs ) {
			$plugins->active_installs = __( 'Less Than 10', 'plugin-information-card' );
		} else {
			$plugins->active_installs = number_format_i18n( $plugins->active_installs ) . '+';
		}
		
		$plugins->downloaded = number_format_i18n( $plugins->downloaded );
		$plugins->short_description = strip_tags( $plugins->short_description );
		
		require_once(ABSPATH . 'wp-admin/includes/template.php');
		$sum_rating = $plugins->ratings[5]*5+$plugins->ratings[4]*4+$plugins->ratings[3]*3+$plugins->ratings[2]*2+$plugins->ratings[1]*1;
		$rating = $sum_rating>0 ? $sum_rating/$plugins->num_ratings : 0;
		$plugins->rating = wp_star_rating(array('rating' => $rating, 'number' => $plugins->num_ratings, 'echo' => false) );
		
		$plugins->plugin_link = 'https://wordpress.org/plugins/'.$plugins->slug.'/';

		if ( !empty($atts['field']) ) :
			if ( !is_array($plugins->{$atts['field']}) ) return $plugins->{$atts['field']};
			return;
		endif;
		
		$pic_format = preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($m) use ($plugins) { return (isset($plugins->{$m[1]}) && !is_array($plugins->{$m[1]})) ? $plugins->{$m[1]} : $m[0]; }, $pic_format);

		$css = '';
		if ( !empty($pic_css) ) :
			$css = '<style type="text/css">'."\n";
			$css .= $pic_css;
			$css .= '</style>'."\n";
		endif;
		
		return $pic_format.$css;
	}
	
	function plugin_information_card_settings() {
		$fields = array('name' => __('Plugin Name', 'plugin-information-card'),
						'slug' => __('Plugin Slug', 'plugin-information-card'),
						'version' => __('Latest Version', 'plugin-information-card'),
						'author' => __('Plugin Author', 'plugin-information-card'),
						'author_profile' => __('Plugin Profile', 'plugin-information-card'),
						'requires' => __('Required WordPress Version', 'plugin-information-card'),
						'tested' => __('Tested Compatible WordPress Version', 'plugin-information-card'),
						'requires_php' => __('Required PHP Version', 'plugin-information-card'),
						'rating' => __('Rating', 'plugin-information-card'),
						'num_ratings' => __('Number of Ratings', 'plugin-information-card'),
						'support_threads' => __('Support Threads', 'plugin-information-card'),
						'support_threads_resolved' => __('Support Threads Resolved', 'plugin-information-card'),
						'active_installs' => __('Active Installs', 'plugin-information-card'),
						'downloaded' => __('Downloaded', 'plugin-information-card'),
						'last_updated' => __('Last Updated', 'plugin-information-card'),
						'added' => __('Added Date', 'plugin-information-card'),
						'short_description' => __('Short Description', 'plugin-information-card'),
						'description' => __('Description', 'plugin-information-card'),
						'plugin_link' => __('Plugin URL', 'plugin-information-card'),
						'homepage' => __('Homepage URL', 'plugin-information-card'),
						'download_link' => __('Download URL', 'plugin-information-card'),
						'donate_link' => __('Donation URL', 'plugin-information-card'),
						'icon' => __('Icon URL', 'plugin-information-card'),
						'banner' => __('Banner URL', 'plugin-information-card'),
					   );
?>
<div class="wrap">
<h1><?php _e( 'Plugin Information Card', 'plugin-information-card' ); ?></h1>

<form method="post" action="options.php" novalidate="novalidate">
<?php settings_fields('plugin-information-card'); ?>
	
<table class="form-table">
<tr>
<th scope="row"><label for="pic_format"><?php _e( 'Format', 'plugin-information-card' ); ?></label><br />
<select id="pic_inserter">
<option value=""></option>
<?php
	foreach ( $fields as $key => $val ) :
?>
<option value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
	endforeach;
?>
</select>
<script type="text/javascript">
// <![CDATA[
jQuery('#pic_inserter').change( function() {
	if ( jQuery('#pic_format').val() != '' ) {
		var v= jQuery('#pic_format').val();
		var selin = jQuery('#pic_format').prop('selectionStart');
    	var selout = jQuery('#pic_format').prop('selectionEnd');
		var v1=v.substr(0,selin);
		var v2=v.substr(selout);
		var insert = '%'+jQuery(this).val()+'%';
	    jQuery('#pic_format').val(v1+insert+v2).prop({"selectionStart":selin+insert.length, "selectionEnd":selin+insert.length}).trigger("focus");
	}
} );
//-->
</script>
</th>
<td><textarea name="pic_format" rows="15" cols="50" id="pic_format" class="large-text"><?php echo esc_textarea( get_option( 'pic_format' ) ); ?></textarea></td>
</tr>
</th>
<tr>
<th scope="row"><label for="pic_css"><?php _e( 'CSS', 'plugin-information-card' ); ?></label></th>
<td><textarea name="pic_css" rows="15" cols="50" id="format" class="large-text"><?php echo esc_textarea( get_option( 'pic_css' ) ); ?></textarea></td>
</tr>
<tr>
<th scope="row"><label for="pic_days"><?php _e( 'Caching Days', 'plugin-information-card' ); ?></label></th>
<td><input name="pic_days" type="number" step="1" min="0" id="pic_days" value="<?php form_option( 'pic_days' ); ?>" class="small-text" /> <?php _e( 'days', 'plugin-information-card' ); ?></td>
</tr>
</table>

<?php do_settings_sections('plugin-information-card'); ?>

<?php submit_button(); ?>
</form>

<h2><?php _e( 'How to display the plugin information card', 'plugin-information-card' ); ?></h2>
<p><?php _e( 'Just use the &#91;pic&#93; shortcode with the plugin slug attribute like this:', 'plugin-information-card' ); ?> &#91;pic slug="plugin-information-card"&#93;</p>

</div>
<?php
	}
}
$plugin_information_card = new plugin_information_card();
?>