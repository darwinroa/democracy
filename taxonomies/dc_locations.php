<?php
function cptui_register_my_taxes_locations()
{

  /**
   * Taxonomy: Locations.
   */

  $labels = [
    "name" => esc_html__("Locations", "hello-elementor-child"),
    "singular_name" => esc_html__("Location", "hello-elementor-child"),
  ];


  $args = [
    "label" => esc_html__("Locations", "hello-elementor-child"),
    "labels" => $labels,
    "public" => true,
    "publicly_queryable" => true,
    "hierarchical" => true,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => ['slug' => 'locations', 'with_front' => true,],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "show_tagcloud" => false,
    "rest_base" => "locations",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "rest_namespace" => "wp/v2",
    "show_in_quick_edit" => false,
    "sort" => false,
    "show_in_graphql" => false,
  ];
  register_taxonomy("locations", ["case_studies"], $args);
}
add_action('init', 'cptui_register_my_taxes_locations');
