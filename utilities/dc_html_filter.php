<?php

/**
 * Retorna el HTML del formulario
 * Requiere 2 variables
 * $taxonomies es un array de arrays donde vada array contiene la key 'slug' y 'name'
 * Siendo estas keies el slug de la taxonomía y el nombre que quiero que se muestre de la taxonomía
 */
function dc_html_filter_form($taxonomies, $form_ID)
{
  $html = "
  <div class='dc__content-filter'>
    <form id='dc__form-$form_ID'>";
  foreach ($taxonomies as $taxonomy) :
    if ($terms = dc_filter_options($taxonomy['slug'])) $html .= dc_html_filter_select($taxonomy['slug'], $taxonomy['name'], $terms);
  endforeach;
  $html .= "<button type='button' id='dc__button-$form_ID' class='filter-buton'>Filter</button>";
  $html .= "</form></div>";
  return $html;
}

/**
 * Retorna los valores de todas las taxonomías
 * Se le debe pasar como variable el slug de la taxonomia
 */
function dc_filter_options($taxonomy)
{
  return get_terms(
    array(
      'taxonomy'    => $taxonomy,
      'orderby'     => 'name',
      'hide_empty'  => false
    )
  );
}

/**
 * Retorna el html del select
 * Requiere de las variables de slug del cpt, nombre del cpt y los terms
 * El atributo $terms son los terms retornados de la función dc_filter_options()
 */
function dc_html_filter_select($cpt_slug, $cpt_name, $terms)
{
  $html = "<select class='member-select-filter' name='{$cpt_slug}' id='{$cpt_slug}'>
    <option value='' selected>{$cpt_name}</option>";
  foreach ($terms as $term) :
    $html .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
  endforeach;
  $html .= "</select>";
  return $html;
}
