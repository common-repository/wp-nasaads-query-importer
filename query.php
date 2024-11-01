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


// define the API url
function wp_nasaads_query_importer_url() {
    return 'https://api.adsabs.harvard.edu/v1/';
}


// functions for handling query errors
$wp_nasaads_query_importer_error = null;
function wp_nasaads_query_importer_throw($msg, $type = 0) {
    global $wp_nasaads_query_importer_error;
    $wp_nasaads_query_importer_error = array('msg' => $msg, 'type' => $type);
    return false;
}
function wp_nasaads_query_importer_get_error() {
    global $wp_nasaads_query_importer_error;
    return $wp_nasaads_query_importer_error;
}


// build the API query basend on the given attribute array
function wp_nasaads_query_importer_build_query($atts, $fetch) {
    if(array_key_exists('url', $atts )) {
    	$what = $atts['url'];
    	if (! is_null($what)) {
       	 return $what;
    	}
    }

    # search query given explicitly
    if (! is_null($atts['query'])) {
        return $atts['query'];
    }

    # build search query from given fields
    $fields = array();
    if (! is_null($atts['author'])) {
        # in case of "easy logic", i.e., there are no quotation marks,
        # OR, ANDs, or parentheses, then we assume a single author
        # and add quotation marks around her/his name
        if (! preg_match('/["()]+|( AND )+|( OR )+/i', $atts['author'])) {
            $atts['author'] = '"' . $atts['author'] . '"';
        }
        $fields[] = 'author:' . $atts['author'];
    }
    if (! is_null($atts['aff'])) {
        $fields[] = 'aff:' . $atts['aff'];
    }
    if (! is_null($atts['year'])) {
        if (preg_match('/( TO )/', $atts['year'])) {
            $atts['year'] = '[' . $atts['year'] . ']';
        }
        $fields[] = 'year:' . $atts['year'];
    }
    if (! is_null($atts['bibstem'])) {
        $fields[] = 'bibstem:' . str_replace('&', '%26', $atts['bibstem']);
    }
    if (! is_null($atts['title'])) {
        # "easy logic", see above
        if (! preg_match('/["()]+|( AND )+|( OR )+/i', $atts['title'])) {
            $atts['title'] = '"' . $atts['title'] . '"';
        }
        $fields[] = 'title:' . $atts['title'];
    }
    if (! is_null($atts['library'])) {
        $fields[] = sprintf('docs(library/%s)', $atts['library']);
    }
    $query = implode(' %2B', $fields);
    # sanity check for any request given (search query or library)
    if ($query === '' && is_null($atts['library'])) { return false; }

    # finalize and encode
    $what = 'search/query?';
    if ($query !== '') {
        $what .= sprintf('q=%s&', $query);
    }
    if (! is_null($atts['property'])) {
        $what .= sprintf('fq=property:(%s)&', $atts['property']);
    }
    $what .= sprintf('fl=%s&rows=%d&sort=%s',
        implode(',', $fetch), $atts['max_rec'], $atts['sort']);
    $what = str_replace(['"', '/', '[', ']', ' '],
    ['%22', '%2F', '%5B', '%5D', '+'], $what);

    return $what;
}


// query the API
function wp_nasaads_query_importer_query($what, $token = null) {
    if (is_null($token)) {
        $token = get_option('wp_nasaads_query_importer-token');
    }

    // query API
    $response = wp_remote_get(
        wp_nasaads_query_importer_url() . $what,
        array(
            'user-agent' => 'Mozilla/5.0 (NASA/ADS Query - WordPress Plugin)',
            'timeout' => 60,
            'headers' => array('Authorization' => 'Bearer ' . $token)));
    if (! is_array($response)) {
        return wp_nasaads_query_importer_throw('API response is not of expected type');
    }
    if (! array_key_exists('body', $response)) {
        return wp_nasaads_query_importer_throw(
            'API response does not contain expected keys');
    }

    $response = json_decode($response['body'], true);
    if(is_null($response)){
        return wp_nasaads_query_importer_throw(
            'Something went wrong. Response is null');
    }

    // check on any error returned by the API
    if (array_key_exists('error', $response)) {
        if (gettype($response['error']) === 'object') {
            return wp_nasaads_query_importer_throw($response['error']['msg']);
        }
        elseif (gettype($response['error']) === 'array') {
            if (array_key_exists('msg', $response['error'])) {
                return wp_nasaads_query_importer_throw(
                    $response['error']['msg']);
            }
            return wp_nasaads_query_importer_throw(
                print_r($response['error'], TRUE));
        }
        return wp_nasaads_query_importer_throw($response['error'], 1);
    }

    // select 'solr' which exists for library queries
    if (array_key_exists('solr', $response)) {
        $response = $response['solr'];
    }
    // check on existing documents
    if (! array_key_exists('response', $response)
        || ! array_key_exists('docs', $response['response'])) {
        return wp_nasaads_query_importer_throw(
            'API response does not contain any document keys');
    }

    return $response['response'];
}

?>
