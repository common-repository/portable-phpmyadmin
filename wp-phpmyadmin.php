<?php
/*
Plugin Name: Portable phpMyAdmin
Plugin URI: https://getbutterfly.com/wordpress-plugins/
Description: Portable phpMyAdmin allows a user to access the phpMyAdmin section straight from the Dashboard.
Version: 1.5.0
Author: Ciprian Popescu
Author URI: https://getbutterfly.com/
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Copyright 2009-2017 Ciprian Popescu (email: getbutterfly@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

phpMyAdmin is licensed under the terms of the GNU General Public License
version 3, as published by the Free Software Foundation.
*/
define('PORTABLE_PHPMYADMIN_VERSION', '1.5.0');

//
define('PMA_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('PMA_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));
//

include PMA_PLUGIN_PATH . '/inc/pma-db.php';
include PMA_PLUGIN_PATH . '/wp-info-mod/mod_general.php';

add_action('admin_menu', 'add_option_page_portable_phpmyadmin');

function add_option_page_portable_phpmyadmin() {
	//add_menu_page('Portable phpMyAdmin', 'Portable phpMyAdmin', 'manage_options', __FILE__, 'option_page_portable_phpmyadmin', 'dashicons-randomize');
    add_options_page('Portable phpMyAdmin', 'Portable phpMyAdmin', 'manage_options', 'portable-phpmyadmin', 'option_page_portable_phpmyadmin');
}

function get_ppma_filesize($fileSize) {
    $bytes = array('KB', 'KB', 'MB', 'GB', 'TB');

    if ($fileSize < 1024) {
        $fileSize = 1;
    }

    for ($i = 0; $fileSize > 1024; $i++) {
        $fileSize /= 1024;
    }

    $fileSizeInfo['size'] = round($fileSize, 3);
    $fileSizeInfo['type'] = $bytes[$i];

    return $fileSizeInfo;
}

function get_ppma_db_size() {
    global $wpdb;

    $dbSize = 0;

    $rows = $wpdb->get_results("SHOW table STATUS", ARRAY_A);

    foreach ($rows as $row) {
        $dbSize += $row['Data_length'] + $row['Index_length'];
    }

    $dbSize = file_size_info($dbSize);

    return $dbSize['size'] . ' ' . $dbSize['type'];
}

function option_page_portable_phpmyadmin() {
?>
<div class="wrap">
	<h2>Portable phpMyAdmin</h2>

    <div class="notice notice-warning is-dismissible">
        <p><strong>Important:</strong> Always have a backup of your database before modifying any data! You should also make your blog inaccessible during database editing by activating the maintenance mode!</p>
    </div>
    <div class="notice notice-error is-dismissible">
        <p><strong>Note:</strong> This plugin should only be used for development purposes or by experienced users. If more users have access to the administration section, you should consider using the plugin <em>only when necessary</em>.</p>
    </div>

    <?php pma_manage_table_list(); ?>

    <table class="widefat">
		<thead>
			<tr>
				<th>Variable Name</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Database host</td>
				<td><code><?php echo DB_HOST;?></code></td>
			</tr>
			<tr>
				<td>Database size</td>
				<td><code><?php echo get_ppma_db_size(); ?></code></td>
			</tr>
			<tr>
				<td><strong>Portable phpMyAdmin</strong> plugin version</td>
				<td><code><?php echo PORTABLE_PHPMYADMIN_VERSION; ?></code></td>
			</tr>
		</tbody>
	</table>

    <?php get_portable_serverinfo(); ?>

    <hr>
    <p>Check the <a href="https://getbutterfly.com" rel="external">official homepage</a> for feedback and support, or rate it on <a href="https://wordpress.org/plugins/portable-phpmyadmin/" rel="external">WordPress.org plugin repository.</a></p>
</div>
<?php
}
