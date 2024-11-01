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


// return HTML code to display an error message inside a box
function wp_nasaads_query_importer_shortcode_error($msg) {
    return '<div style="border-left: solid 4px; border-left-color: #dc3232; padding-left: 5px"><p><b>WP NASA/ADS Query Importer error: </b>' . $msg . '</p></div>';
}


// Format a record (array(placeholder => value, ...)) based on the
// given HTML template. The placeholders in the template will be
// replaced by the record's value filtered through wordpress'
// apply_filters('wp_nasaads_query_importer-format_[placeholder_name]')
function wp_nasaads_query_importer_format_record($record, $template, $atts) {
    $html = "$template";
    foreach ($record as $field => $value) {

        $value = trim(apply_filters(
            'wp_nasaads_query_importer-format_' . $field, $value, $value, $atts));
        $html = preg_replace(
            '/(\[([^\]\[]*?))?%' . $field . '(([^\]\[]*?)\])?/',
            $value == '' ? '' : '${2}' . $value . '${4}',
            $html);
    }
    return $html;
}


// default format filters
function wp_nasaads_query_importer_format_author($html, $value, $atts) {
    $mx = $atts['max_authors'] > 0 ? $atts['max_authors'] : -1;
    $authors = implode('; ', array_slice($value, 0, $mx));
    if ($mx > -1 && sizeof($value) > $mx) {
        $authors .= sprintf('; <i>and %d coauthor%s</i>',
            sizeof($value) - $mx, sizeof($value) - $mx == 1 ? '' : 's');
    }
    return $authors;
}
add_filter(
    'wp_nasaads_query_importer-format_author', 'wp_nasaads_query_importer_format_author', 1, 3);

function wp_nasaads_query_importer_format_month($html, $value, $atts) {
    return date('M', strtotime($value));
}
add_filter(
    'wp_nasaads_query_importer-format_month', 'wp_nasaads_query_importer_format_month', 1, 3);

function wp_nasaads_query_importer_format_bibstem($html, $value, $atts) {
    return $value[0];
}
add_filter(
    'wp_nasaads_query_importer-format_bibstem',
    'wp_nasaads_query_importer_format_bibstem', 1, 3);

function wp_nasaads_query_importer_format_adsurl($html, $value, $atts) {
    return 'https://ui.adsabs.harvard.edu/abs/' . $value . '/abstract';
}
add_filter(
    'wp_nasaads_query_importer-format_adsurl', 'wp_nasaads_query_importer_format_adsurl', 1, 3);


// a filter for apply_filter('wp_nasaads_query_importer-API_value') which reduces
// some API value, which seem to be an array with just one element,
// to a single element
function wp_nasaads_query_importer_reduce_array_value($value, $field) {
    if (in_array($field, array('title', 'page', 'bibstem'))
        && is_array($value) && sizeof($value) == 1) {
        return $value[0];
    }
    return $value;
}
add_filter(
    'wp_nasaads_query_importer-API_value', 'wp_nasaads_query_importer_reduce_array_value', 1, 2);


// Define the default mapping of NASA/ADS API field names onto
// placeholder names. The keys of the returned associative array have
// to be the placeholder names and their values the correponding API
// field names. The value of the associative array may also be an
// array of field names, which are then passed as an array to any
// defined format filter function. The mapping is filtered below
// through wordpress' apply_filters('wp_nasaads_query_importer-record_mapping')
// such that the mapping can be extended/modified by third party
// plugins.
function wp_nasaads_query_importer_record_mapping() {
    return array(
        'author' => 'author',
        'aff' => 'aff',
        'title' => 'title',
        'year' => 'year',
        'month' => 'date',
        'bibstem' => 'bibstem',
        'pub' => 'pub',
        'page' => 'page',
        'volume' => 'volume',
        'adsurl' => 'bibcode'
    );
}


// check the given attribute value on integer type and its allowed range.
// convert to integer if possible ($atts is passed as reference!)
function wp_nasaads_query_importer_shortcode_int_check(&$atts, $name, $mn, $mx, $map = null) {
    if (! is_numeric($atts[$name])) {
        if (is_null($map)) {
            return wp_nasaads_query_importer_shortcode_error(
                $name . ' has to be an integer');
        }
        // map string into number
        foreach ($map as $k => $v) {
            if ($atts[$name] == $k) {
                $atts[$name] = $v;
                return true;
            }
        }
        return wp_nasaads_query_importer_shortcode_error(
            'invalid value "' . $atts[$name] . '" for ' . $name);
    }
    if (isset($value) and ! is_int($value)) { $atts[$name] = (int) $atts[$name]; }
    if ($atts[$name] < $mn || $atts[$name] > $mx) {
        return wp_nasaads_query_importer_shortcode_error(
            $mn . ' <= ' . $name . ' <= ' . $mx . ' required');
    }
    return true;
}


// the shortcode
function wp_nasaads_query_importer_shortcode($atts = [], $template = '', $tag = '') {
    if (get_option('wp_nasaads_query_importer-valid_token') != 1) {
        return wp_nasaads_query_importer_shortcode_error('access token is not valid');
    }

    # default attributes
    # note: the filter called inside of wordpress' shortcode_atts
    # allows to modify the default values by third party plugins
    $atts = shortcode_atts(array(
        'query' => null, 'author' => null, 'aff' => null,
        'year' => null, 'bibstem' => null, 'title' => null,
        'property' => null, 'library' => null,
        'max_rec' => 25, 'sort' => 'date+desc,bibcode+desc',
        'max_authors' => 3,
        'notify_empty_list' => get_option('wp_nasaads_query_importer-empty_list')
                               ? 'true' : 'false',
        'show_num_rec' => get_option('wp_nasaads_query_importer-numrecords')
    ), array_change_key_case((array) $atts,  CASE_LOWER), $tag);

    # sanity checks of some attributes (and convert attributes to integers)
    if (is_string($err = wp_nasaads_query_importer_shortcode_int_check(
        $atts, 'show_num_rec', 0, 2, array(
            'never' => 0, 'always' => 1, 'depends' => 2)))) { return $err; }
    if (is_string($err = wp_nasaads_query_importer_shortcode_int_check(
        $atts, 'max_rec', 0, 2000))) { return $err; }
    if (is_string($err = wp_nasaads_query_importer_shortcode_int_check(
        $atts, 'max_authors', 0, 1000))) { return $err; }

    # default content from template
    $shorttemp = true;
    if ($template === '') {
        $shorttemp = false;
        $template = get_option('wp_nasaads_query_importer-template');
    }

    # check which of the defined placeholders (wp_nasaads_query_importer_record_mapping()
    # filtered through a wordpress filter) are used in the
    # template. Only those will be fetched from the API.
    $mapping = apply_filters(
        'wp_nasaads_query_importer-record_mapping', wp_nasaads_query_importer_record_mapping());
    $fetch = array(); # API field names
    $mapon = array(); # corresponding plugin's placeholder names
    foreach ($mapping as $field => $from) {
        # does the plugin field name exist in the template? -> fetch
        # note: will also add array of field names to $fetch
        if (! (strpos($template, '%' . $field) === false)) {
            $fetch[] = $from;
            $mapon[] = $field;
        }
    }

    # query URL
    # (flatten $fetch as it may contain arrays and remove duplicate entries)
    $what = wp_nasaads_query_importer_build_query($atts, array_unique(iterator_to_array(
        new RecursiveIteratorIterator(new RecursiveArrayIterator($fetch)),
        false)));
    if ($what === false) {
        return wp_nasaads_query_importer_shortcode_error('no query parameters given');
    }

    # query API
    $response = wp_nasaads_query_importer_query($what);
    if ($response === false) {
        return wp_nasaads_query_importer_shortcode_error(
            wp_nasaads_query_importer_get_error()['msg']);
    }

    # format HTML
    $html = array();
    foreach ($response['docs'] as $record) {
        # Insert fetched record into an associative array. The keys
        # are the plugin's placeholder names and the values are the
        # API value or values if an multiple fields are fetched for
        # a certain placeholder.
        $data = array();
        for ($i=0; $i<sizeof($fetch); $i++) {            
            $value = null;
            if (is_array($fetch[$i])) {
                # multiple API fields defined for this placeholder
                $value = array();
                foreach ($fetch[$i] as $f) {
                    $value[] = apply_filters(
                        'wp_nasaads_query_importer-API_value', $record[$ff], $fetch[$i]);
                }
            } else {
                $value = apply_filters(
                    'wp_nasaads_query_importer-API_value', $record[$fetch[$i]],
                    $fetch[$i]);
            }
            $data[$mapon[$i]] = $value;
        }
        $html[] = wp_nasaads_query_importer_format_record($data, $template, $atts);
    }
    if (sizeof($html) > 0) {
        $html = ($shorttemp ? '<p>'
                 : get_option('wp_nasaads_query_importer-template_start'))
              . implode("</p>\n<p>", $html)
              . ($shorttemp ? '</p>'
                 : get_option('wp_nasaads_query_importer-template_stop'));
    } else {
        $html = '';
        if ($atts['notify_empty_list'] === 'true')  {
            $html = '<p>NASA/ADS query returned an empty list.</p>';
        }
    }
    # end section
    $html .= '<div style="margin-top: 1em; font-size: 70%"><p>';
    # include number of found records
    if ($atts['show_num_rec'] == 1 || ($atts['show_num_rec'] == 2
        && $response['numFound'] > sizeof($response['docs']))) {
        $html .= sprintf('Query returned %d total number of records, %d are shown.</small><br />', $response['numFound'], sizeof($response['docs']));
    }
    # acknowledge ADS
    if (get_option('wp_nasaads_query_importer-acknowledge')) {
        $html .= 'Service offered by <a href="http://adsabs.harvard.edu/">The SAO/NASA Astrophysics Data System</a>.<br />';
    }
    $html .= '</div>';
    
    return $html;
}

// the old shortcode throws an error message
function wp_nasaads_query_importer_old_shortcode($atts = [], $template = '', $tag = '') {
    return wp_nasaads_query_importer_shortcode_error('this shortcde has been superseded by wp_nasaads_query_importer, see the documentation for its usage');
}

add_shortcode('wp_nasaads_query_importer', 'wp_nasaads_query_importer_shortcode');

add_shortcode('wp_nasaads_query_importer_full', 'wp_nasaads_query_importer_old_shortcode');
?>
