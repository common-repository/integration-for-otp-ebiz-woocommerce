<?php

// If uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete admin notices
delete_metadata( 'user', 0, 'wc_ebiz_admin_notices', '', true );

//Delete options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wc\_ebiz\_%';");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_wc\_ebiz\_%';");
