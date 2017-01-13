<?php

/** Redirect traffic from a bad domain to a good domain **/
if ( ! strstr( $_SERVER['REQUEST_URI'], 'yourdomain.com' ) ) {
	header( 'HTTP/1.1 301 Moved Permanently' );
	header( 'Location: http://yourdomain.com' );
	die();
}

/** Hard unset doing_wp_cron before WP loads, if you have an unconventional cron setup **/
if ( isset( $_GET['doing_wp_cron'] ) ) {
	unset( $_GET['doing_wp_cron'] );
}

/** WordPress pre-load API for tools like error mailers, memcached stuff, etc **/
require_once( 'wp-pre-load.php' );

/** SSL load balancer fix **/
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

/** Memcached servers for batcache **/
$memcached_servers = array( 
	'10.0.2.100:11211', 
	'10.0.2.101:11211', 
	'10.0.2.102:11211', 
	'10.0.2.103:11211', 
	'10.0.2.104:11211', 
	'10.0.2.105:11211', 
	'10.0.2.106:11211', 
	'10.0.2.107:11211', 
	'10.0.2.108:11211', 
	'10.0.2.109:11211', 
);

/** Enable WordPress persistent cache **/
define( 'WP_CACHE', true );

/** Perhaps to allow wp-admin only through a proxy? You be the judge. **/
define( 'AUTHORIZED_PROXY_IP', '123.45.67.89' );

/** Security and performance tweaks **/
define( 'FORCE_SSL_ADMIN', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_MEMORY_LIMIT', '128M' );
define( 'DISABLE_WP_CRON', true );

/** Hard-set home and siteurl in case the DB gets wonky **/
define( 'WP_HOME', 'http://yourdomain.com' );
define( 'WP_SITEURL', 'http://yourdomain.com' );
define( 'WPLANG', '' );

/** MySQL database connection parameters **/
$table_prefix  = 'wp_';
define( 'DB_NAME', 'yourdbname' );
define( 'DB_USER', 'yourdbuser' );
define( 'DB_PASSWORD', 'yourdbpass' );
define( 'DB_HOST', 'youdbhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

/** WPMU settings **/
define( 'WP_ALLOW_MULTISITE', true );
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', true );
define( 'DOMAIN_CURRENT_SITE', 'yourdomain.com' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );
define( 'SUNRISE', 'on' );

/** NoSQL data storage node(s) **/
define( 'MONGO_ENDPOINT', 'https://yourmongohost.com' );

/** Elasticsearch API hosts **/
define( 'EP_HOST', 'https://yourelasticsearchhost.com' );
define( 'ES_ENDPOINT', 'https://yourelasticsearchhost.com' );

/** Move mu-plugins if sandboxed **/
if ( ( isset( $_GET['sandbox'] ) && '1' == $_GET['sandbox'] ) || isset( $_COOKIE['sandbox'] ) && '1' == $_COOKIE['sandbox'] ) {
	define( 'WPMU_PLUGIN_DIR', '/path/to/webroot/wp-content/mu-plugins-staged' );
	define( 'WPMU_PLUGIN_URL', '//yourdomain.com/wp-content/mu-plugins-staged' );
	define( 'MUPLUGINDIR' 'wp-content/mu-plugins-staged' );
	define( 'WP_DEBUG', true );
}

/** Make sure debugging is off **/
define( 'WP_DEBUG', false );

/** Authentication Unique Keys **/
define( 'AUTH_KEY',         '4<3DmKagQ~iX{9r,:aaaaALnbYd tb+PlJZQ|qD>3hapiLtes3L9a$G-a`G#q3L-' );
define( 'SECURE_AUTH_KEY',  'S@GxvihaNOIkLj`7}Ra{Ht3=[p>O2{RvsdaH5s9EY{6[S;z4$Gvav&[$(dEez<Hy' );
define( 'LOGGED_IN_KEY',    'T)qMhMVPa9k9eWjN=6BMa ;.>7{m;a9dab~Zor{g_tK#)pH;2ua4.wiF5UG$~rx4' );
define( 'NONCE_KEY',        'c9g*Y(qyraGvFGD-6;Ea{a^2xB0aadduNR%-97&kDxd-J23%`?a&-v`^f{[A03<1' );
define( 'AUTH_SALT',        'XGt.hk9]2Ia6T~Me{!gA-EawGkedda:nC>A7]9~H`Ap](<<f.-ayU<V 2uVQ&TZ=' );
define( 'SECURE_AUTH_SALT', 'ICOmKY?2|.|a-1`^$9aualGa,dd~8K>J)0]>e:4eL`s&tF^VTWbr6{46RM+R(&Zs' );
define( 'LOGGED_IN_SALT',   'D>*u2C^F@m|waB:3$aoC{a+ddraqK&:1E~n;$p)Lj/[8wmQkC[an/6OiE7N,</]-' );
define( 'NONCE_SALT',       'K2&.#$s]`qWS6a|-a_+WXddmacUa`9lxP<sQ{J[HWgM8HH,TK%aho8@vV2VSD><~' );

/** Define ABSPATH **/
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Load WordPress **/
require_once( ABSPATH . 'wp-settings.php' );

// omit