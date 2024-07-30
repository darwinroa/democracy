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

/**
 * Add Customs Styles and Scripts
 */
wp_enqueue_style('dc-custom-style', get_stylesheet_directory_uri() . '/inc/styles/dc_styles.css', array(), '1.0');
wp_enqueue_script('dc-custom-script', get_stylesheet_directory_uri() . '/inc/scripts/dc_scripts.js', array('jquery'), null, true);

///////////////////////////////////////////////////////////////////////
////////////////////////////SHORTCODES/////////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'shortcodes/members/dc_members.php'; // Members filter shortcode
require 'shortcodes/library/dc_library.php'; // Libraries filter shortcode
require 'shortcodes/team/dc_team.php'; // Team Pop Up shortcode
require 'shortcodes/tab9/dc_tab9.php'; // Tab 9 shortcode for the Deliberation on Difficult Issues page
require 'shortcodes/mapa-mundi/dc_mapa_mundi.php'; // Mapa mundi shortocode
require 'shortcodes/accordion/dc_accordion.php'; // Accordion
require 'shortcodes/play-video/dc_play_video.php'; // Accordion

///////////////////////////////////////////////////////////////////////
///////////////////////UTILIDADES GENRALES/////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'utilities/dc_html_filter.php'; // Filters HTML
require 'utilities/dc_load_more_button.php'; // Load more button HTML
require 'utilities/dc_utilities.php'; // Utilities functions
require 'utilities/dc_video_popup.php'; // Utilities functions

///////////////////////////////////////////////////////////////////////
////////////////////////////Post Types/////////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'post-types/dc_case_study.php'; // Case Stydies
require 'post-types/dc_our_reach.php'; // our Reach

///////////////////////////////////////////////////////////////////////
////////////////////////////Taxonomies/////////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'taxonomies/dc_locations.php'; // Case Studies

///////////////////////////////////////////////////////////////////////
///////////////////////////Custom Fields///////////////////////////////
///////////////////////////////////////////////////////////////////////
require 'custom-fields/dc_our_reach_fields.php'; // Our Reach
require 'custom-fields/dc_case_studies.php'; // Case Stydies
require 'custom-fields/dc_case_studies_and_our_reach.php'; // Case Stydies and Our Reach
require 'custom-fields/dc_accordion_popup.php'; // Accordion PopUp
require 'custom-fields/dc_projects.php'; // Projects
require 'custom-fields/dc_members.php'; // Projects