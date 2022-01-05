<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mdc_reno' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'V*}*@MW8Yxpzd~g^%wdTO @Eh]%C=aqB #|[ssSpb,KNCJWOi:/2g2FJL^WSm)a`' );
define( 'SECURE_AUTH_KEY',  'E2wd9{0sUZU7U`|9fR>.MI*QJaw~K5_5.[5ub>S4VgiCTdJs|S/[0|*TW+1FDKmc' );
define( 'LOGGED_IN_KEY',    'M8tzS+ 5UK pmY~7@dgl?2o]tY!2,QMovaj[N_YC:[H!+aWnOT1$HU|QS)OC!0yh' );
define( 'NONCE_KEY',        'Q&#%WkTkJ~!]0KbxxaEt~?J1?KOV*<bNIDL>5rQ-,t[AfM!t_FmW[xL))@UNUu{$' );
define( 'AUTH_SALT',        'nmCDpj6ym D#IZ!o@hyY/a9a?LDE:{j~V+u<tj)>DSM[6`e0aC@c@<7QwTZ$KdpV' );
define( 'SECURE_AUTH_SALT', '^KvDcJ)hUN8$JB6VR*##5mmbKT}o`BNXdF02YC9to;fMS@1PHsQO*@O)H8%/`;9l' );
define( 'LOGGED_IN_SALT',   'k&*_b9)o>$Rx|3?d&YgJP_|U0v)m0}G*fU_3ikF[m-q|~o7(R UZsTU_?LiReXw`' );
define( 'NONCE_SALT',       '_aaHHI_qeC1}+JEE2sSb}^S&&P6vgD`E8cCkw7(nms6hDl!@{`kW,p2v;T`lEc&v' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
