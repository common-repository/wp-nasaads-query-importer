=== WP Nasa/ADS Query Importer ===
Contributors: mbissinger, giodimilia
Plugin URI: http://wordpress.org/extend/plugins/wp-nasaads-query-importer/
Author URI: http://adsabs.harvard.edu
Tags: ads, article, astronomy, NASA
Requires at least: 4.7
Tested up to: 5.4
Stable tag: trunk
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fetch bibliographic records from [The SAO/NASA Astrophysics Data System (ADS)](https://ui.adsabs.harvard.edu/) and include a list of these records in your posts using shortcodes.

== Requirements ==
The plugin needs your personalized NASA ADS API token in order to fetch records from the database and, thus, to work correctly! You need to [register an account](https://ui.adsabs.harvard.edu/user/account/register) at NASA ADS or [login](https://ui.adsabs.harvard.edu/user/account/login) and [generate your token](https://ui.adsabs.harvard.edu/user/settings/token) in the account settings.

== Description ==
[The SAO/NASA Astrophysics Data System (ADS)](https://ui.adsabs.harvard.edu/) (NASA ADS) is a digital library portal for researchers in astronomy and physics. Complex literature searches can be performed such that compiling a list of relevant papers for a specific topic can be done easily. This plugin provides an interface to the [NASA ADS API](http://adsabs.github.io/help/api/) in order to include lists of records in your posts using shortcodes.

With this plugin you are able to
- easily include simple ADS queries without having to learn the NASA ADS API.
- include complex ADS queries by providing the full GET method URL (see the *Shortcodes* section below).
- customize the format and the displayed information of the returned list of records at the shortcode level if needed.

Note: this code is not backward compatible with version 0.3 and older.

= Shortcodes =
The shortcode `wp_nasaads_query_importer` can be used to query the NASA ADS and output the returned list of records. It can be inserted into any post with or without providing enclosed content:
**[wp_nasaads_query_importer *attributes*]**
or
**[wp_nasaads_query_importer *attributes*] *format* [/wp_nasaads_query_importer]**

Here, *attributes* are the shortcode's attributes and it is mandatory to either provide
- at least one of the query attributes described below,
- the *library* attribute (and combined optionally with query attributes),
- or the *query* attribute.

In case of enclosed content the *format* is used to format the list of the records. See the *Format* section below.

Please note that for all of the following query attributes the [NASA ADS search syntax](http://adsabs.github.io/help/search/search-syntax) is applied, i.e., the shortcode's attribute values are submitted unaltered to the API (with the exception of *author* and *title* as described below). This enables more complex searches, for instance when combined with logical operators like AND, OR, or NOT (read the search syntax for details).

- **author**: search for certain author(s). In case a single author is given the name of the author, i.e., the attribute's value will be submitted with surrounding double quotes (e.g., `Hawking, S.` will be submitted as `"Hawking, S."`).  Technically, a value is considered to be a single author if the string contains no AND, OR, double quotes or parentheses. If you need to include double quotes in the author field, please use single quotes around the attribute's value (see the examples below).
- **aff**: search for author's affiliations which contain the given string.
- **bibstem**: search for articles published in specific journal(s) given by the acronym(s) of the journal(s).
- **year**: search for articles published in a certain year given in the format `YYYY`. Articles within a certain period can be searched by `YYYY-YYYY` or `YYYY TO YYYY`.
- **title**: search for articles whose title contains the given string. The title will be submitted with surrounding double quotes under the same conditions as described for the *author* attribute.
- **property**: filter the records on specific properties like `refereed`. Read the *Properties* section of the search syntax for a list of all available properties.

Showing the records within an [ADS user library](http://adsabs.github.io/help/libraries/creating-libraries) is supported by the

- **library** attribute: the ID of the user-library.

For more complex ADS searches, which are not supported by using the attributes of the shortcode, you may specify the
- **query** attribute: the GET method's URL to the API without the base path, i.e., https://api.adsabs.harvard.edu/v1/  is added automatically to the URL.

Finally,the following optional attributes can be used to control the output:
- **sort**: sort the list of records. The value has to be in the format `field+direction` where `field` is the record field name to sort on and `direction` is either `desc` or `asc`. The default is `date desc,bibcode desc`.
- **max_rec**: the maximum number of records to show. The default is set to `25`.
- **max_authors**: the maximum number of authors to print, which is set to `3` by default. The remaining number of authors are appended to the printed author list.
- **notify_empty_list**: overrides the global plugin option "Notify empty list". Possible values are `true` or `false`. The default is `true`.
- **show_num_rec**: overrides the global plugin option "Show number of records". Allowed values are `never`, `always`, or `depends` (read the description in the plugin's settings page for details). The default is set to `depends`.

= Format =
How the list of records is formatted and inserted into your post can be defined in the plugin option "Content template" for all shortcodes. In case a shortcode is inserted into a post with enclosed content, i.e., `[wp_nasaads_query_importer] ...  [/wp_nasaads_query_importer]` then the content within the shortcode tags is used as the template and, thus, overrides the plugin's global option.

The template is applied to each record in the list and may contain HTML entities to style the output. The data of the record is inserted by the following placeholders: %author, %affil, %title, %year, %month, %bibstem, %pub, %page, %volume, and %adsurl. See the [NASA ADS search syntax](https://adsabs.github.io/help/search/search-syntax) for the definition of the record's fields with the exception of %year and %month which are derived from *date*, and %adsurl which is derived from *bibcode*.

Ultimate full control over the output and field records can be gained by new WordPress filters added by the plugin (see the *WordPress filter* section below).

= WordPress filter =
This plugins adds a few [filters](https://developer.wordpress.org/reference/functions/add_filter/) to WordPress, which can be used by third party plugins to further control the output and fetched record fields.

`apply_filters( 'wp_nasaads_query_importer-record_mapping', array $mapping )`
Defines which record fields are fetched from NASA ADS and on which placeholders they are mapped. The keys of the associative array `$mapping` are the placeholder names (without the leading %) and their values the API fields names. The default definition can be found in the source code. ([Source code](https://github.com/adsabs/wp-nasaads-query-importer))

`apply_filters( 'wp_nasaads_query_importer-API_value', mixed $value, string $field )`
Filters the `$value` of a fetched API `$field` before it is inserted into the record's data. ([Source code](https://github.com/adsabs/wp-nasaads-query-importer))

`apply_filters( 'nasa_das_query-format_[placeholder]', mixed $current_value, mixed $original_value, array $atts )`
The final value returned by this filter will be the replacement for the placeholder (without the leading %). The `$original_value` is that returned by the NASA ADS API while `$current_value` is the value already modified by filter functions of higher priority. The shortcode attributes are passed as the associative array `$atts`. See the source code of the `author filter` for an example. ([Source code](https://github.com/adsabs/wp-nasaads-query-importer))

== Examples ==
Show a list of refereed papers with Stephen Hawking among the list of authors:
`[wp_nasaads_query_importer author="Hawking, S." property="refereed"]`

Same as before but where Hawking was first author:
`[wp_nasaads_query_importer author="^Hawking, S." property="refereed"]`

List all articles by Ejnar Hertzsprung published in the *Astronomische Nachrichten*:
`[wp_nasaads_query_importer author="Hertzsprung, E." bibstem="AN"]`

List all articles by Ejnar Hertzsprung and Henry Norris Russell. Note that the author string is surrounded by single quotes while the author names are surrounded by double quotes in order to preserve their last and first names. Also the search by both authors is logically combined by AND due to the space between their names:
`[wp_nasaads_query_importer author='"Hertzsprung, E." "Russell, H.N."']`

Load a user ADS library and filter the list of papers on a year range and exclude a certain journal:
`[wp_nasaads_query_importer library="GdEguHJfSGCrYKKqJM-5XA" year="1911-1914" bibstem="(-SciAm)" max_rec=40]`

Same as before but only show the title by customizing the record template:
```
<ul>
[wp_nasaads_query_importer library="GdEguHJfSGCrYKKqJM-5XA" year="1911-1914" bibstem="(-SciAm)" max_rec=40]
<li><a href="%adsurl">%title</a></li>
[/wp_nasaads_query_importer]
</ul>
```

== Installation ==
1. Get a copy of the plugin by one of the following options:
	- from the plugin manager in your WordPress admin dashboard search for "WP Nasa/ADS Query Importer" and hit the "Install now" button.
	- download the ZIP-file from http://wordpress.org/extend/plugins/wp-nasaads-query-importer/ and extract its content into your Wordpress plugin folder located at /wp-content/plugins/.
	- clone the GIT repository at https://github.com/adsabs/wp-nasaads-query-importer/ into your WordPress plugin folder located at /wp-content/plugins/ and checkout the master branch.
2. Activate the plugin using the plugin manager of your WordPress admin dashboard.
3. Follow the *Settings* link in the plugin manager or navigate to *Settings* -> *WP Nasa/ADS Query Importer* in your admin dashboard. Then insert your NASA ADS API access token into the *Token* field. If you do not have a personalized token yet read the *Requirements* section above.

You are then ready to insert the plugin's shortcodes into any of your posts.

== Frequently Asked Questions ==

= I do not want to create an account at NASA ADS. How can I still use the plugin? =
Unfortunately, there is no way to query the NASA ADS API without providing a token. Since a token is personalized to an account at NASA ADS and they recommend to "keep your API key secret to protect it from abuse".

= Is my token kept secret by the WP Nasa/ADS Query Importer plugin? =
Don't worry. This plugin does not distribute your token any further! It will be used only for each query defined by the shortcodes in your blog! In doubts checkout the source code.

Note, however, that your token is saved unencrypted as a plugin option in the database attached to your WordPress website. If your database or WordPress blog gets hacked then your token might get stolen. The token cannot be saved encrypted since the encryption method could be looked up in the source code easily. Thus, make sure to have the latest versions of WordPress and of database software installed in order to close any security issues!

In case of a security problem you can simply generate a new token in your NASA ADS account settings. In this case your old token can no longer be used.

= Can I still use the *ads_query_url* shortcode parameter from old versions of the plugin? =
No, unfortunately this code is not backward compatible with version 0.3 or older since it has migrated to the more modern NASA ADS API.

= Can you implement a feature I'd like to have? =
That depends. Create an issue at [GitHub](https://github.com/adsabs/wp-nasaads-query-importer/issues) with your requested feature and the developers will investigate whether the feature is of general interest (and there is a developer with same spare time to implement it).

= I think I have discovered a bug! =
In case you have discovered a bug please create an issue at [GitHub](https://github.com/adsabs/wp-nasaads-query-importer/issues).

= Can I contribute to the plugin? =
Sure, thanks! Simply fork the GIT repository from [GitHub](https://github.com/adsabs/wp-nasaads-query-importer) and create a pull request of your feature branch once your code works.

== Changelog ==

= 1.0.3 =
* Maintenance release (sync with svn)

= 1.0.2 =
* Fixes incompatibilities with PHP8 and Wordpress 6

= 1.0.1 =
* Bug fix: non-existent referred variable

= 1.0 =
* Entire revision of the plugin, breaks backward compatibility
* Migration to the more modern NASA ADS API

= 0.3 =
* Updated ADS XML Namespace compatibility

= 0.2 =
* List of mirrors updated 

= 0.1 =
* First public release
