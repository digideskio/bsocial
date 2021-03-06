<?php
/*
 Plugin URI: http://wordpress.org/extend/plugins/opengraph
 Description: Adds Open Graph metadata to your pages
 Author: Will Norris, patches and updates by Casey Bisson
 Author URI: http://willnorris.com/
 Version: 1.0-trunk
 License: Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0.html)
 Text Domain: opengraph
 */

define('OPENGRAPH_NS_URI', 'http://ogp.me/ns#');
$opengraph_ns_set = false;


/**
 * Add Open Graph XML namespace to <html> element.
 */
function opengraph_add_namespace( $output ) {
	global $opengraph_ns_set;
	$opengraph_ns_set = true;

	$output .= ' prefix="og: ' . esc_attr(OPENGRAPH_NS_URI) . '"';
	return $output;
}
add_filter('language_attributes', 'opengraph_add_namespace');


/**
 * Get the Open Graph metadata for the current page.
 *
 * @uses apply_filters() Calls 'opengraph_{$name}' for each property name
 * @uses apply_filters() Calls 'opengraph_metadata' before returning metadata array
 */
function opengraph_metadata() {
	$metadata = array();

 	// defualt properties defined at http://opengraphprotocol.org/
	$properties = array(
		// required
		'title', 'type', 'image', 'url', 

		// optional
		'site_name', 'description', 

		// location
		'longitude', 'latitude', 'street-address', 'locality', 'region', 
		'postal-code', 'country-name',

		// contact
		'email', 'phone_number', 'fax_number',
	);

	foreach ($properties as $property) {
		$filter = 'opengraph_' . $property;
		$metadata["og:$property"] = apply_filters($filter, '');
	}

	return apply_filters('opengraph_metadata', $metadata);
}


/**
 * Register filters for default Open Graph metadata.
 */
function opengraph_default_metadata() {
	add_filter('opengraph_title', 'opengraph_default_title', 5);
	add_filter('opengraph_type', 'opengraph_default_type', 5);
	add_filter('opengraph_image', 'opengraph_default_image', 5);
	add_filter('opengraph_url', 'opengraph_default_url', 5);

	add_filter('opengraph_site_name', 'opengraph_default_sitename', 5);
	add_filter('opengraph_description', 'opengraph_default_description', 5);
}
add_filter('wp', 'opengraph_default_metadata');


/**
 * Default title property, using the page title.
 */
function opengraph_default_title( $title = '' )
{
	if ( is_singular() && empty($title) )
	{
		global $wp_query;
		$title = $wp_query->queried_object->post_title;
	}

	return $title;
}


/**
 * Default type property.
 */
function opengraph_default_type( $type = '' )
{
	if ( empty($type) ) $type = 'blog';
	return $type;
}


/**
 * Default image property, using the post-thumbnail.
 */
function opengraph_default_image( $image = '' )
{
	global $wp_query;
	if ( 
		is_singular() && // only operate on single posts or pages, not the front page of the site
		empty( $image ) && // don't replace the image if one is already set
		current_theme_supports( 'post-thumbnails' ) && // only attempt to get the post thumbnail if the theme supports them
		has_post_thumbnail( $wp_query->queried_object_id ) // only set the meta if we have a thumbnail
	)
	{
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $wp_query->queried_object_id ), 'post-thumbnail');
		if ($thumbnail)
		{
			$image = $thumbnail[0];
		}
	}

	return $image;
}


/**
 * Default url property, using the permalink for the page.
 */
function opengraph_default_url( $url = '' )
{
    if ( ! empty( $url )) return $url;

	if ( is_singular() )
	{
		global $wp_query;
		$url = get_permalink($wp_query->queried_object_id);
	}
	else
	{
		$url = trailingslashit( get_bloginfo('url' ));
	}

	return $url;
}


/**
 * Default site_name property, using the bloginfo name.
 */
function opengraph_default_sitename( $name = '' )
{
	if ( empty($name) ) $name = get_bloginfo('name');
	return $name;
}


/**
 * Default description property, using the bloginfo description.
 */
function opengraph_default_description( $description = '' )
{
    if ( !empty($description) ) return $description;

	// get blog description as default
    $description = get_bloginfo('description');

	// replace the description with a more specific one if available
    global $wp_query;
    if ( is_singular() )
		$description = wp_filter_nohtml_kses( apply_filters( 'the_excerpt' , empty( $wp_query->queried_object->post_excerpt ) ? wp_trim_words( strip_shortcodes( $wp_query->queried_object->post_content )) : $wp_query->queried_object->post_excerpt ));

    return $description;
}


/**
 * Output Open Graph <meta> tags in the page header.
 */
function opengraph_meta_tags()
{
	global $opengraph_ns_set;

	$xml_ns = '';
	if ( !$opengraph_ns_set )
		$xml_ns = 'xmlns:og="' . esc_attr(OPENGRAPH_NS_URI) . '" ';

	$metadata = opengraph_metadata();
	foreach ( $metadata as $key => $value ) {
		if ( empty($key) || empty($value) ) continue;
		echo '<meta ' . $xml_ns . 'property="' . esc_attr($key) . '" content="' . esc_attr($value) . '" />' . "\n";
	}
}
add_action('wp_head', 'opengraph_meta_tags');
