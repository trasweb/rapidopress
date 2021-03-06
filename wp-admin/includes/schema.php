<?php
/**
 * WordPress Administration Scheme API
 *
 * Here we keep the DB structure and option values.
 *
 * @package WordPress
 * @subpackage Administration
 */

// Declare these as global in case schema.php is included from a function.
global $wpdb, $wp_queries, $charset_collate;

/**
 * The database character collate.
 * @var string
 * @global string
 * @name $charset_collate
 */
$charset_collate = $wpdb->get_charset_collate();

/**
 * Retrieve the SQL for creating database tables.
 *
 * @since 3.3.0
 *
 * @param string $scope Optional. The tables for which to retrieve SQL. Can be all, global, ms_global, or blog tables. Defaults to all.
 * @param int $blog_id Optional. The blog ID for which to retrieve SQL. Default is the current blog ID.
 * @return string The SQL needed to create the requested tables.
 */
function wp_get_db_schema( $scope = 'all', $blog_id = null ) {
	global $wpdb;

	$charset_collate = '';

	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";

	if ( $blog_id && $blog_id != $wpdb->blogid )
		$old_blog_id = $wpdb->set_blog_id( $blog_id );

	/*
	 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 */
	$max_index_length = 191;

	// Blog specific tables.
	$blog_tables = "CREATE TABLE $wpdb->terms (
 term_id bigint(20) unsigned NOT NULL auto_increment,
 name varchar(200) NOT NULL default '',
 slug varchar(200) NOT NULL default '',
 term_group bigint(10) NOT NULL default 0,
 PRIMARY KEY  (term_id),
 KEY slug (slug($max_index_length)),
 KEY name (name($max_index_length))
) $charset_collate;
CREATE TABLE $wpdb->term_taxonomy (
 term_taxonomy_id bigint(20) unsigned NOT NULL auto_increment,
 term_id bigint(20) unsigned NOT NULL default 0,
 taxonomy varchar(32) NOT NULL default '',
 description longtext NOT NULL,
 parent bigint(20) unsigned NOT NULL default 0,
 count bigint(20) NOT NULL default 0,
 PRIMARY KEY  (term_taxonomy_id),
 UNIQUE KEY term_id_taxonomy (term_id,taxonomy),
 KEY taxonomy (taxonomy)
) $charset_collate;
CREATE TABLE $wpdb->term_relationships (
 object_id bigint(20) unsigned NOT NULL default 0,
 term_taxonomy_id bigint(20) unsigned NOT NULL default 0,
 term_order int(11) NOT NULL default 0,
 PRIMARY KEY  (object_id,term_taxonomy_id),
 KEY term_taxonomy_id (term_taxonomy_id)
) $charset_collate;
CREATE TABLE $wpdb->commentmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  comment_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY comment_id (comment_id),
  KEY meta_key (meta_key($max_index_length))
) $charset_collate;
CREATE TABLE $wpdb->comments (
  comment_ID bigint(20) unsigned NOT NULL auto_increment,
  comment_post_ID bigint(20) unsigned NOT NULL default '0',
  comment_author tinytext NOT NULL,
  comment_author_email varchar(100) NOT NULL default '',
  comment_author_url varchar(200) NOT NULL default '',
  comment_author_IP varchar(100) NOT NULL default '',
  comment_date datetime NOT NULL default '0000-00-00 00:00:00',
  comment_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  comment_content text NOT NULL,
  comment_karma int(11) NOT NULL default '0',
  comment_approved varchar(20) NOT NULL default '1',
  comment_agent varchar(255) NOT NULL default '',
  comment_type varchar(20) NOT NULL default '',
  comment_parent bigint(20) unsigned NOT NULL default '0',
  user_id bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (comment_ID),
  KEY comment_post_ID (comment_post_ID),
  KEY comment_approved_date_gmt (comment_approved,comment_date_gmt),
  KEY comment_date_gmt (comment_date_gmt),
  KEY comment_parent (comment_parent),
  KEY comment_author_email (comment_author_email(10))
) $charset_collate;
CREATE TABLE $wpdb->options (
  option_id bigint(20) unsigned NOT NULL auto_increment,
  option_name varchar(64) NOT NULL default '',
  option_value longtext NOT NULL,
  autoload varchar(20) NOT NULL default 'yes',
  PRIMARY KEY  (option_id),
  UNIQUE KEY option_name (option_name)
) $charset_collate;
CREATE TABLE $wpdb->postmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  post_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY post_id (post_id),
  KEY meta_key (meta_key($max_index_length))
) $charset_collate;
CREATE TABLE $wpdb->posts (
  ID bigint(20) unsigned NOT NULL auto_increment,
  post_author bigint(20) unsigned NOT NULL default '0',
  post_date datetime NOT NULL default '0000-00-00 00:00:00',
  post_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  post_content longtext NOT NULL,
  post_title text NOT NULL,
  post_excerpt text NOT NULL,
  post_status varchar(20) NOT NULL default 'publish',
  comment_status varchar(20) NOT NULL default 'open',
  ping_status varchar(20) NOT NULL default 'open',
  post_name varchar(200) NOT NULL default '',
  post_modified datetime NOT NULL default '0000-00-00 00:00:00',
  post_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  post_content_filtered longtext NOT NULL,
  post_parent bigint(20) unsigned NOT NULL default '0',
  guid varchar(255) NOT NULL default '',
  menu_order int(11) NOT NULL default '0',
  post_type varchar(20) NOT NULL default 'post',
  post_mime_type varchar(100) NOT NULL default '',
  comment_count bigint(20) NOT NULL default '0',
  PRIMARY KEY  (ID),
  KEY post_name (post_name($max_index_length)),
  KEY type_status_date (post_type,post_status,post_date,ID),
  KEY post_parent (post_parent),
  KEY post_author (post_author)
) $charset_collate;\n";

	// Single site users table. The multisite flavor of the users table is handled below.
	$users_single_table = "CREATE TABLE $wpdb->users (
  ID bigint(20) unsigned NOT NULL auto_increment,
  user_login varchar(60) NOT NULL default '',
  user_pass varchar(64) NOT NULL default '',
  user_nicename varchar(50) NOT NULL default '',
  user_email varchar(100) NOT NULL default '',
  user_url varchar(100) NOT NULL default '',
  user_registered datetime NOT NULL default '0000-00-00 00:00:00',
  user_activation_key varchar(60) NOT NULL default '',
  user_status int(11) NOT NULL default '0',
  display_name varchar(250) NOT NULL default '',
  PRIMARY KEY  (ID),
  KEY user_login_key (user_login),
  KEY user_nicename (user_nicename)
) $charset_collate;\n";

	// Multisite users table
	$users_multi_table = "CREATE TABLE $wpdb->users (
  ID bigint(20) unsigned NOT NULL auto_increment,
  user_login varchar(60) NOT NULL default '',
  user_pass varchar(64) NOT NULL default '',
  user_nicename varchar(50) NOT NULL default '',
  user_email varchar(100) NOT NULL default '',
  user_url varchar(100) NOT NULL default '',
  user_registered datetime NOT NULL default '0000-00-00 00:00:00',
  user_activation_key varchar(60) NOT NULL default '',
  user_status int(11) NOT NULL default '0',
  display_name varchar(250) NOT NULL default '',
  spam tinyint(2) NOT NULL default '0',
  deleted tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (ID),
  KEY user_login_key (user_login),
  KEY user_nicename (user_nicename)
) $charset_collate;\n";

	// Usermeta.
	$usermeta_table = "CREATE TABLE $wpdb->usermeta (
  umeta_id bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (umeta_id),
  KEY user_id (user_id),
  KEY meta_key (meta_key($max_index_length))
) $charset_collate;\n";

	// Global tables
		$global_tables = $users_single_table . $usermeta_table;

	// Multisite global tables.
	$ms_global_tables = "CREATE TABLE $wpdb->blogs (
  blog_id bigint(20) NOT NULL auto_increment,
  site_id bigint(20) NOT NULL default '0',
  domain varchar(200) NOT NULL default '',
  path varchar(100) NOT NULL default '',
  registered datetime NOT NULL default '0000-00-00 00:00:00',
  last_updated datetime NOT NULL default '0000-00-00 00:00:00',
  public tinyint(2) NOT NULL default '1',
  archived tinyint(2) NOT NULL default '0',
  mature tinyint(2) NOT NULL default '0',
  spam tinyint(2) NOT NULL default '0',
  deleted tinyint(2) NOT NULL default '0',
  lang_id int(11) NOT NULL default '0',
  PRIMARY KEY  (blog_id),
  KEY domain (domain(50),path(5)),
  KEY lang_id (lang_id)
) $charset_collate;
CREATE TABLE $wpdb->blog_versions (
  blog_id bigint(20) NOT NULL default '0',
  db_version varchar(20) NOT NULL default '',
  last_updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (blog_id),
  KEY db_version (db_version)
) $charset_collate;
CREATE TABLE $wpdb->registration_log (
  ID bigint(20) NOT NULL auto_increment,
  email varchar(255) NOT NULL default '',
  IP varchar(30) NOT NULL default '',
  blog_id bigint(20) NOT NULL default '0',
  date_registered datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY IP (IP)
) $charset_collate;
CREATE TABLE $wpdb->site (
  id bigint(20) NOT NULL auto_increment,
  domain varchar(200) NOT NULL default '',
  path varchar(100) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY domain (domain(140),path(51))
) $charset_collate;
CREATE TABLE $wpdb->sitemeta (
  meta_id bigint(20) NOT NULL auto_increment,
  site_id bigint(20) NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY meta_key (meta_key($max_index_length)),
  KEY site_id (site_id)
) $charset_collate;
CREATE TABLE $wpdb->signups (
  signup_id bigint(20) NOT NULL auto_increment,
  domain varchar(200) NOT NULL default '',
  path varchar(100) NOT NULL default '',
  title longtext NOT NULL,
  user_login varchar(60) NOT NULL default '',
  user_email varchar(100) NOT NULL default '',
  registered datetime NOT NULL default '0000-00-00 00:00:00',
  activated datetime NOT NULL default '0000-00-00 00:00:00',
  active tinyint(1) NOT NULL default '0',
  activation_key varchar(50) NOT NULL default '',
  meta longtext,
  PRIMARY KEY  (signup_id),
  KEY activation_key (activation_key),
  KEY user_email (user_email),
  KEY user_login_email (user_login,user_email),
  KEY domain_path (domain(140),path(51))
) $charset_collate;";

	switch ( $scope ) {
		case 'blog' :
			$queries = $blog_tables;
			break;
		case 'global' :
			$queries = $global_tables;
		case 'ms_global' :
			$queries = $ms_global_tables;
			break;
		case 'all' :
		default:
			$queries = $global_tables . $blog_tables;
			break;
	}

	if ( isset( $old_blog_id ) )
		$wpdb->set_blog_id( $old_blog_id );

	return $queries;
}

// Populate for back compat.
$wp_queries = wp_get_db_schema( 'all' );

/**
 * Create WordPress options and set the default values.
 *
 * @since 1.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @uses $wp_db_version
 */
function populate_options() {
	global $wpdb, $wp_db_version, $wp_current_db_version;

	$guessurl = wp_guess_url();
	/**
	 * Fires before creating WordPress options and populating their default values.
	 *
	 * @since 2.6.0
	 */
	do_action( 'populate_options' );

	if ( ini_get('safe_mode') ) {
		// Safe mode can break mkdir() so use a flat structure by default.
		$uploads_use_yearmonth_folders = 0;
	} else {
		$uploads_use_yearmonth_folders = 1;
	}

	$template = WP_DEFAULT_THEME;
	// If default theme is a child theme, we need to get its template
	$theme = wp_get_theme( $template );
	if ( ! $theme->errors() )
		$template = $theme->get_template();

	$timezone_string = '';
	$gmt_offset = 0;
	/* translators: default GMT offset or timezone string. Must be either a valid offset (-12 to 14)
	   or a valid timezone string (America/New_York). See http://us3.php.net/manual/en/timezones.php
	   for all timezone strings supported by PHP.
	*/
	$offset_or_tz = _x( '0', 'default GMT offset or timezone string' );
	if ( is_numeric( $offset_or_tz ) )
		$gmt_offset = $offset_or_tz;
	elseif ( $offset_or_tz && in_array( $offset_or_tz, timezone_identifiers_list() ) )
			$timezone_string = $offset_or_tz;

	$options = array(
	'siteurl' => $guessurl,
	'blogname' => __('My Site'),
	/* translators: blog tagline */
	'blogdescription' => __('Just another RapidoPress site'),
	'users_can_register' => 0,
	'admin_email' => 'you@example.com',
	/* translators: default start of the week. 0 = Sunday, 1 = Monday */
	'start_of_week' => _x( '1', 'start of week' ),
	'require_name_email' => 1,
	'comments_notify' => 1,
	'default_category' => 1,
	'default_comment_status' => 'open',
	'posts_per_page' => 10,
	/* translators: default date format, see http://php.net/date */
	'date_format' => __('F j, Y'),
	/* translators: default time format, see http://php.net/date */
	'time_format' => __('g:i a'),
	/* translators: links last updated date format, see http://php.net/date */
	'links_updated_date_format' => __('F j, Y g:i a'),
	'comment_moderation' => 0,
	'moderation_notify' => 1,
	'permalink_structure' => '',
	'gzipcompression' => 0,
	'hack_file' => 0,
	'blog_charset' => 'UTF-8',
	'moderation_keys' => '',
	'active_plugins' => array(),
	'category_base' => '',
	'advanced_edit' => 0,
	'comment_max_links' => 2,
	'gmt_offset' => $gmt_offset,

	// 1.5
	'recently_edited' => '',
	'template' => $template,
	'stylesheet' => WP_DEFAULT_THEME,
	'comment_whitelist' => 1,
	'blacklist_keys' => '',
	'comment_registration' => 0,
	'html_type' => 'text/html',

	// 2.0
	'default_role' => 'subscriber',
	'db_version' => $wp_db_version,

	// 2.0.1
	'uploads_use_yearmonth_folders' => $uploads_use_yearmonth_folders,
	'upload_path' => '',

	// 2.1
	'blog_public' => '1',
	'show_on_front' => 'posts',

	// 2.2
	'tag_base' => '',

	// 2.5
	'show_avatars' => '1',
	'upload_url_path' => '',
	'thumbnail_size_w' => 150,
	'thumbnail_size_h' => 150,
	'thumbnail_crop' => 1,
	'medium_size_w' => 300,
	'medium_size_h' => 300,

	// 2.7
	'large_size_w' => 1024,
	'large_size_h' => 1024,
	'image_default_link_type' => 'file',
	'image_default_size' => '',
	'image_default_align' => '',
	'close_comments_for_old_posts' => 0,
	'close_comments_days_old' => 14,
	'thread_comments' => 1,
	'thread_comments_depth' => 5,
	'page_comments' => 0,
	'comments_per_page' => 50,
	'default_comments_page' => 'newest',
	'comment_order' => 'asc',
	'sticky_posts' => array(),
	'widget_categories' => array(),
	'widget_text' => array(),
	'uninstall_plugins' => array(),

	// 2.8
	'timezone_string' => $timezone_string,

	// 3.0
	'page_for_posts' => 0,
	'page_on_front' => 0,

	// 3.1
	'default_post_format' => 0,

	// RapidoPress 0.4
	'limit_revisions' => 2,
	'tracking_code' => ''
	);

	// 3.3

		$options['initial_db_version'] = ! empty( $wp_current_db_version ) && $wp_current_db_version < $wp_db_version
			? $wp_current_db_version : $wp_db_version;


	// Set autoload to no for these options
	$fat_options = array( 'moderation_keys', 'recently_edited', 'blacklist_keys', 'uninstall_plugins' );

	$keys = "'" . implode( "', '", array_keys( $options ) ) . "'";
	$existing_options = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name in ( $keys )" );

	$insert = '';
	foreach ( $options as $option => $value ) {
		if ( in_array($option, $existing_options) )
			continue;
		if ( in_array($option, $fat_options) )
			$autoload = 'no';
		else
			$autoload = 'yes';

		if ( is_array($value) )
			$value = serialize($value);
		if ( !empty($insert) )
			$insert .= ', ';
		$insert .= $wpdb->prepare( "(%s, %s, %s)", $option, $value, $autoload );
	}

	if ( !empty($insert) )
		$wpdb->query("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES " . $insert);

	// Delete unused options.
	$unusedoptions = array(
		'avatar_rating', 'avatar_default', 'blodotgsping_url', 'bodyterminator', 'emailtestonly', 'phoneemail_separator', 
		'subjectprefix', 'use_bbcode', 'use_blodotgsping', 'use_phoneemail', 'use_quicktags', 'use_weblogsping',
		'weblogs_cache_file', 'use_preview', 'use_htmltrans',  'fileupload_allowedusers',
		'use_phoneemail', 'default_post_status', 'default_post_category', 'archive_mode', 'time_difference',
		'links_minadminlevel', 'links_use_adminlevels', 'links_rating_type', 'links_rating_char',
		'links_rating_ignore_zero', 'links_rating_single_image', 'links_rating_image0', 'links_rating_image1',
		'links_rating_image2', 'links_rating_image3', 'links_rating_image4', 'links_rating_image5',
		'links_rating_image6', 'links_rating_image7', 'links_rating_image8', 'links_rating_image9',
		'links_recently_updated_time', 'links_recently_updated_prepend', 'links_recently_updated_append',
		'weblogs_cacheminutes', 'comment_allowed_tags', 'search_engine_friendly_urls', 'default_geourl_lat',
		'default_geourl_lon', 'use_default_geourl', 'weblogs_xml_url', 'new_users_can_blog', '_wpnonce',
		'_wp_http_referer', 'Update', 'action', 'rich_editing', 'autosave_interval', 'deactivated_plugins',
		'can_compress_scripts', 'page_uris', 'update_core', 'update_plugins', 'update_themes', 'doing_cron',
		'random_seed', 'rss_excerpt_length', 'secret', 'use_linksupdate', 'default_comment_status_page',
		'wporg_popular_tags', 'what_to_show', 'rss_language', 'language', 'enable_xmlrpc', 'enable_app',
		'embed_autourls', 'default_post_edit_rows',
	);
	foreach ( $unusedoptions as $option )
		delete_option($option);

	// Delete obsolete magpie stuff.
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name REGEXP '^rss_[0-9a-f]{32}(_ts)?$'");

	/*
	 * Deletes all expired transients. The multi-table delete syntax is used
	 * to delete the transient record from table a, and the corresponding
	 * transient_timeout record from table b.
	 */
	$time = time();
	$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
		AND b.option_value < %d";
	$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', $time ) );

	if ( is_main_site() && is_main_network() ) {
		$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
			AND b.option_value < %d";
		$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', $time ) );
	}
}

/**
 * Execute WordPress role creation for the various WordPress versions.
 *
 * @since 2.0.0
 */
function populate_roles() {
	populate_roles_160();
	populate_roles_210();
	populate_roles_230();
	populate_roles_250();
	populate_roles_260();
	populate_roles_270();
	populate_roles_280();
	populate_roles_300();
}

/**
 * Create the roles for WordPress 2.0
 *
 * @since 2.0.0
 */
function populate_roles_160() {
	// Add roles

	// Dummy gettext calls to get strings in the catalog.
	/* translators: user role */
	_x('Administrator', 'User role');
	/* translators: user role */
	_x('Editor', 'User role');
	/* translators: user role */
	_x('Author', 'User role');
	/* translators: user role */
	_x('Contributor', 'User role');
	/* translators: user role */
	_x('Subscriber', 'User role');

	add_role('administrator', 'Administrator');
	add_role('editor', 'Editor');
	add_role('author', 'Author');
	add_role('contributor', 'Contributor');
	add_role('subscriber', 'Subscriber');

	// Add caps for Administrator role
	$role = get_role('administrator');
	$role->add_cap('switch_themes');
	$role->add_cap('edit_themes');
	$role->add_cap('activate_plugins');
	$role->add_cap('edit_plugins');
	$role->add_cap('edit_users');
	$role->add_cap('edit_files');
	$role->add_cap('manage_options');
	$role->add_cap('moderate_comments');
	$role->add_cap('manage_categories');
	$role->add_cap('upload_files');
	$role->add_cap('unfiltered_html');
	$role->add_cap('edit_posts');
	$role->add_cap('edit_others_posts');
	$role->add_cap('edit_published_posts');
	$role->add_cap('publish_posts');
	$role->add_cap('edit_pages');
	$role->add_cap('read');
	$role->add_cap('level_10');
	$role->add_cap('level_9');
	$role->add_cap('level_8');
	$role->add_cap('level_7');
	$role->add_cap('level_6');
	$role->add_cap('level_5');
	$role->add_cap('level_4');
	$role->add_cap('level_3');
	$role->add_cap('level_2');
	$role->add_cap('level_1');
	$role->add_cap('level_0');

	// Add caps for Editor role
	$role = get_role('editor');
	$role->add_cap('moderate_comments');
	$role->add_cap('manage_categories');
	$role->add_cap('upload_files');
	$role->add_cap('unfiltered_html');
	$role->add_cap('edit_posts');
	$role->add_cap('edit_others_posts');
	$role->add_cap('edit_published_posts');
	$role->add_cap('publish_posts');
	$role->add_cap('edit_pages');
	$role->add_cap('read');
	$role->add_cap('level_7');
	$role->add_cap('level_6');
	$role->add_cap('level_5');
	$role->add_cap('level_4');
	$role->add_cap('level_3');
	$role->add_cap('level_2');
	$role->add_cap('level_1');
	$role->add_cap('level_0');

	// Add caps for Author role
	$role = get_role('author');
	$role->add_cap('upload_files');
	$role->add_cap('edit_posts');
	$role->add_cap('edit_published_posts');
	$role->add_cap('publish_posts');
	$role->add_cap('read');
	$role->add_cap('level_2');
	$role->add_cap('level_1');
	$role->add_cap('level_0');

	// Add caps for Contributor role
	$role = get_role('contributor');
	$role->add_cap('edit_posts');
	$role->add_cap('read');
	$role->add_cap('level_1');
	$role->add_cap('level_0');

	// Add caps for Subscriber role
	$role = get_role('subscriber');
	$role->add_cap('read');
	$role->add_cap('level_0');
}

/**
 * Create and modify WordPress roles for WordPress 2.1.
 *
 * @since 2.1.0
 */
function populate_roles_210() {
	$roles = array('administrator', 'editor');
	foreach ($roles as $role) {
		$role = get_role($role);
		if ( empty($role) )
			continue;

		$role->add_cap('edit_others_pages');
		$role->add_cap('edit_published_pages');
		$role->add_cap('publish_pages');
		$role->add_cap('delete_pages');
		$role->add_cap('delete_others_pages');
		$role->add_cap('delete_published_pages');
		$role->add_cap('delete_posts');
		$role->add_cap('delete_others_posts');
		$role->add_cap('delete_published_posts');
		$role->add_cap('delete_private_posts');
		$role->add_cap('edit_private_posts');
		$role->add_cap('read_private_posts');
		$role->add_cap('delete_private_pages');
		$role->add_cap('edit_private_pages');
		$role->add_cap('read_private_pages');
	}

	$role = get_role('administrator');
	if ( ! empty($role) ) {
		$role->add_cap('delete_users');
		$role->add_cap('create_users');
	}

	$role = get_role('author');
	if ( ! empty($role) ) {
		$role->add_cap('delete_posts');
		$role->add_cap('delete_published_posts');
	}

	$role = get_role('contributor');
	if ( ! empty($role) ) {
		$role->add_cap('delete_posts');
	}
}

/**
 * Create and modify WordPress roles for WordPress 2.3.
 *
 * @since 2.3.0
 */
function populate_roles_230() {
	$role = get_role( 'administrator' );

	if ( !empty( $role ) ) {
		$role->add_cap( 'unfiltered_upload' );
	}
}

/**
 * Create and modify WordPress roles for WordPress 2.5.
 *
 * @since 2.5.0
 */
function populate_roles_250() {
	$role = get_role( 'administrator' );

	if ( !empty( $role ) ) {
		$role->add_cap( 'edit_dashboard' );
	}
}

/**
 * Create and modify WordPress roles for WordPress 2.6.
 *
 * @since 2.6.0
 */
function populate_roles_260() {
	$role = get_role( 'administrator' );

	if ( !empty( $role ) ) {
		$role->add_cap( 'update_plugins' );
		$role->add_cap( 'delete_plugins' );
	}
}

/**
 * Create and modify WordPress roles for WordPress 2.7.
 *
 * @since 2.7.0
 */
function populate_roles_270() {
	$role = get_role( 'administrator' );

	if ( !empty( $role ) ) {
		$role->add_cap( 'install_plugins' );
		$role->add_cap( 'update_themes' );
	}
}

/**
 * Create and modify WordPress roles for WordPress 2.8.
 *
 * @since 2.8.0
 */
function populate_roles_280() {
	$role = get_role( 'administrator' );

	if ( !empty( $role ) ) {
		$role->add_cap( 'install_themes' );
	}
}

/**
 * Create and modify WordPress roles for WordPress 3.0.
 *
 * @since 3.0.0
 */
function populate_roles_300() {
	$role = get_role( 'administrator' );

	if ( !empty( $role ) ) {
		$role->add_cap( 'update_core' );
		$role->add_cap( 'list_users' );
		$role->add_cap( 'remove_users' );

		/*
		 * Never used, will be removed. create_users or promote_users
		 * is the capability you're looking for.
		 */
		$role->add_cap( 'add_users' );

		$role->add_cap( 'promote_users' );
		$role->add_cap( 'edit_theme_options' );
		$role->add_cap( 'delete_themes' );
		$role->add_cap( 'export' );
	}
}

/**
 * Install Network.
 *
 * @since 3.0.0
 *
 */
if ( !function_exists( 'install_network' ) ) :
function install_network() {
	if ( ! defined( 'WP_INSTALLING_NETWORK' ) )
		define( 'WP_INSTALLING_NETWORK', true );

	dbDelta( wp_get_db_schema( 'global' ) );
}
endif;


