<?php

/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0');

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles()
{

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20);

///////////////////////////////////////////////////////////////////////
////////////////////////////SHORTCODES/////////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'shortcodes/members/dc_members.php'; // Members filter shortcode
require 'shortcodes/library/dc_library.php'; // Libraries filter shortcode
require 'shortcodes/team/dc_team.php'; // Team Pop Up shortcode
require 'shortcodes/tab9/dc_tab9.php'; // Tab 9 shortcode for the Deliberation on Difficult Issues page

///////////////////////////////////////////////////////////////////////
///////////////////////UTILIDADES GENRALES/////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'utilities/dc_html_filter.php'; // Filters HTML
require 'utilities/dc_load_more_button.php'; // Load more button HTML
require 'utilities/dc_utilities.php'; // Utilities functions

///////////////////////////////////////////////////////////////////////
////////////////////////////Post Types/////////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'post-types/dc_case_study.php'; // Case Stydies