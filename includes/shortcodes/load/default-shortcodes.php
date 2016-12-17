<?php
if(!defined('ABSPATH') ) die();

/**
 * Default Shortcodes
 *
 * @package RapidoPress
 * @subpackage Shortcodes
 */


add_shortcode( 'rapidopress_home', function() { return home_url(); });
add_shortcode( 'rapidopress_theme', function() { return get_template_directory_uri(); });



