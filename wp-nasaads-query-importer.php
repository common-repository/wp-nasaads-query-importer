<?php
/**
 * Plugin Name: WP Nasa/ADS Query Importer
 * Version:     1.0
 * Plugin URI:  http://wordpress.org/extend/plugins/wp-nasaads-query-importer/
 * Description: Include queries to NASA/ADS in any Wordpress blog.
 * Author:      Matthias Bissinger and Giovanni Di Milia for the ADS
 * Author URI:  http://adsabs.harvard.edu
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 Copyright 2020 The SAO/NASA Astrophysics Data System

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or (at
 your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

function wp_nasaads_query_importer_not_validated() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p>The plugin <a href="options-general.php?page=wp_nasaads_query_importer">WP Nasa/ADS Query Importer</a> reported that your access token is not valid yet!</p>
    </div>
    <?php
}
if (get_option('wp_nasaads_query_importer-valid_token') != 1) {
    add_action('admin_notices', 'wp_nasaads_query_importer_not_validated');
}

// require files containing the actual plugin implementation
foreach (array('settings', 'query', 'shortcodes') as $basename) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . $basename . '.php';
}
?>
