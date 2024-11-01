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

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$wp_nasaads_query_importer_options = array(
    'wp_nasaads_query_importer-token',
    'wp_nasaads_query_importer-token',
    'wp_nasaads_query_importer-template',
    'wp_nasaads_query_importer-template_start',
    'wp_nasaads_query_importer-template_stop',
    'wp_nasaads_query_importer-numrecords',
    'wp_nasaads_query_importer-empty_list'
);
foreach ($wp_nasaads_query_importer_options as $option) {
    delete_option($option);
    delete_site_option($option);
}
?>
