<?php
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

// initialize options with default values
function wp_nasaads_query_importer_install() {
    if (get_option('wp_nasaads_query_importer-token') == false) {
        add_option('wp_nasaads_query_importer-token', '');
        add_option('wp_nasaads_query_importer-template', "<li>\n<a href=\"%adsurl\">%title</a><br />\n%author<br />\n<small>%year %month, %bibstem[, %volume][, %page]</small>\n</li>");
        add_option('wp_nasaads_query_importer-template_start', '<ul>');
        add_option('wp_nasaads_query_importer-template_stop', '</ul>');
        add_option('wp_nasaads_query_importer-numrecords', '2');
        add_option('wp_nasaads_query_importer-empty_list', 'true');
    }
}
register_activation_hook(
    __DIR__ . '/wp-nasaads-query-importer.php', 'wp_nasaads_query_importer_install');


// initialize the options page
function wp_nasaads_query_importer_options_init() {
    register_setting(
        'nasa_ads_query', 'wp_nasaads_query_importer-token',
        'wp_nasaads_query_importer_validate_token');
    register_setting('nasa_ads_query', 'wp_nasaads_query_importer-template');
    register_setting('nasa_ads_query', 'wp_nasaads_query_importer-template_start');
    register_setting('nasa_ads_query', 'wp_nasaads_query_importer-template_stop');
    register_setting('nasa_ads_query', 'wp_nasaads_query_importer-numrecords');
    register_setting('nasa_ads_query', 'wp_nasaads_query_importer-empty_list');
    register_setting('nasa_ads_query', 'wp_nasaads_query_importer-acknowledge');

    foreach (array('token', 'numrecords', 'empty_list', 'template',
                   'template_start', 'template_stop', 'acknowledge')
             as $option) {
        add_settings_field(
            'wp_nasaads_query_importer-' . $option . '_field',
            null,
            'wp_nasaads_query_importer_options_display_' . $option . '_field',
            'nasa_ads_query',
            'wp_nasaads_query_importer-options_section');
    }
}
add_action('admin_init', 'wp_nasaads_query_importer_options_init');


function wp_nasaads_query_importer_options_display_template_start_field(){
	echo "<ul>";
}

function wp_nasaads_query_importer_options_display_template_stop_field(){
	echo "</ul>";
}

// display the token option
function wp_nasaads_query_importer_options_display_token_field() {
    ?>
    <h2>API access token</h2>
    <p style="max-width: 460px">In order to query data from <a href="https://ui.adsabs.harvard.edu/">NASA/ADS</a> a so-called access token has to be passed for each request to the <a href="https://github.com/adsabs/adsabs-dev-api#access">API</a>. Thus, a token has to be entered here before the plugin can work properly. You can <a href="https://ui.adsabs.harvard.edu/user/settings/token">generate your personalized token</a> once you are <a href="https://ui.adsabs.harvard.edu/user/account/login">signed into NASA/ADS</a>.</p>
    Token: <input name="wp_nasaads_query_importer-token" id="wp_nasaads_query_importer-token_field" type="text" value="<?php echo get_option('wp_nasaads_query_importer-token'); ?>" size="40"/>
    <p style="max-width: 460px">Note that NASA/ADS recommends to "<b>keep your API key secret to protect it from abuse</b>" (according to the <a href="https://ui.adsabs.harvard.edu/user/settings/token">token generation page</a>). This plugin <u>does not</u> distribute your token any further! It will be used <u>only</u> for each query defined by the shortcodes in your blog!</p>
    <?php
}


// validate the given token
function wp_nasaads_query_importer_validate_token($token) {
    // check length and characters
    if (! preg_match('/[a-zA-Z0-9]{40}/', $token)) {
        add_settings_error(
            'wp_nasaads_query_importer-token',
            'wp_nasaads_query_importer-token_field',
            'Token has to be 40 characters in length and may contain letters and numbers only!',
            'error');
        delete_option('wp_nasaads_query_importer-valid_token');
        return $token;
    }
    // try to access the API
    $access = wp_nasaads_query_importer_query('search/query', $token);
    if ($access == false) {
        $error = wp_nasaads_query_importer_get_error();
        if (strpos($error['msg'], 'The query is empty') === false) {
            $msg = 'The following error occurred: ' . $error['msg'];
            if ($error['type'] == 1 && $error['msg'] === 'Unauthorized') {
                $msg = 'Token could not be verified on NASA/ADS side!';
            }
            add_settings_error(
                'wp_nasaads_query_importer-token',
                'wp_nasaads_query_importer-token_field',
                $msg,
                'error');
            delete_option('wp_nasaads_query_importer-valid_token');
            return $token;
        }
    }
    // token passed
    add_option('wp_nasaads_query_importer-valid_token', '1');
    return $token;
}


// display the content template option
function wp_nasaads_query_importer_options_display_template_field() {
    ?>
    <h2>Content template</h2>
    <p style="max-width: 460px">Define a HTML template for a record in the list returned by the NASA/ADS API.<br />
    <textarea id="wp_nasaads_query_importer-template_field" name="wp_nasaads_query_importer-template" style="width: 100%" rows="4"><?php echo get_option('wp_nasaads_query_importer-template'); ?></textarea></p>
    <p style="max-width: 460px">Optional HTML template before the list.<br />
    <textarea id="wp_nasaads_query_importer-template_start_field" name="wp_nasaads_query_importer-template_start" style="width: 100%" rows="1"><?php echo get_option('wp_nasaads_query_importer-template_start'); ?></textarea></p>
    <p style="max-width: 460px">Optional HTML template after the list.<br />
    <textarea id="wp_nasaads_query_importer-template_stop_field" name="wp_nasaads_query_importer-template_stop" style="width: 100%" rows="1"><?php echo get_option('wp_nasaads_query_importer-template_stop'); ?></textarea></p>
    <p style="max-width: 460px">The following placeholders are defined and will be replaced by the corresponding field record:<br /><?php echo implode(', ', array_map(function($s) { return '%'.$s; }, array_keys(wp_nasaads_query_importer_record_mapping()))); ?></p>
    <?php
}


// display the show number of records option
function wp_nasaads_query_importer_options_display_numrecords_field() {
    ?>
    <h2>Show number of records</h2>
    <p style="max-width: 460px">Select in which case the total number of found records by the query and the number of actually listed records is added to the end of the record list. Can be overridden by the <i>show_num_rec</i> shortcode attribute.</p>
    <input name="wp_nasaads_query_importer-numrecords" id="wp_nasaads_query_importer-numrecords_field" type="radio" value="0"<?php if (get_option('wp_nasaads_query_importer-numrecords') == 0) { echo " checked"; } ?> /> Never<br />
    <input name="wp_nasaads_query_importer-numrecords" id="wp_nasaads_query_importer-numrecords_field" type="radio" value="1"<?php if (get_option('wp_nasaads_query_importer-numrecords') == 1) { echo " checked"; } ?> /> Always<br />
    <input name="wp_nasaads_query_importer-numrecords" id="wp_nasaads_query_importer-numrecords_field" type="radio" value="2"<?php if (get_option('wp_nasaads_query_importer-numrecords') == 2) { echo " checked"; } ?> /> Depends, i.e., only if more records are found than listed
    <?php
}


// display the notify empty list option
function wp_nasaads_query_importer_options_display_empty_list_field() {
    ?>
    <h2>Notify empty list</h2>
    <p style="max-width: 460px">
    <input name="wp_nasaads_query_importer-empty_list" id="wp_nasaads_query_importer-empty_list_field" type="checkbox"<?php if (get_option('wp_nasaads_query_importer-empty_list')) { echo " checked"; } ?> /> Print a notification in case a query returned an empty list. Can be overridden by the <i>notify_empty_list</i> shortcode attribute.</p>
    <?php
}


// display the acknowledge option
function wp_nasaads_query_importer_options_display_acknowledge_field() {
    ?>
    <h2>Acknowledge NASA/ADS</h2>
    <input name="wp_nasaads_query_importer-acknowledge" id="wp_nasaads_query_importer-acknowledge_field" type="checkbox"<?php if (get_option('wp_nasaads_query_importer-acknowledge')) { echo " checked"; } ?> /> Add an acknowledgement to NASA/ADS at the end of the record list.
    <?php
}


// display the options page
function wp_nasaads_query_importer_options_page_display() {
    // die if user may not manage options
    if (! current_user_can('manage_options')) {
        return;
    }
    // page code
    ?>
    <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields('nasa_ads_query');
        do_settings_fields('nasa_ads_query', 'wp_nasaads_query_importer-options_section');
        submit_button('Save Settings');
        ?>
    </form>
    </div>
    <?php
}


// register options page
function wp_nasaads_query_importer_options_page() {
    add_options_page(
        'WP Nasa/ADS Query Importer', 'WP Nasa/ADS Query Importer', 'manage_options',
        'wp_nasaads_query_importer', 'wp_nasaads_query_importer_options_page_display');
}
add_action('admin_menu', 'wp_nasaads_query_importer_options_page');


// add a link to options page in the plugins list
function wp_nasaads_query_importer_options_link($links, $file) {
    if ($file === 'wp-nasaads-query-importer/wp-nasaads-query-importer.php'
        && current_user_can('manage_options')) {
        $links[] = '<a href="options-general.php?page=wp_nasaads_query_importer">Settings</a>';
    }
    return $links;
}
add_filter('plugin_action_links', 'wp_nasaads_query_importer_options_link', 10, 2);
?>
