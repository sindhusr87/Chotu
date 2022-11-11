<?php
if(!function_exists('chotu_generate_shop_feed_onboard')){
  //$captain_shop_category,$captain_pincode,$captain_lat_long
  function chotu_generate_shop_feed_onboard($csc_slug,$csc_term_id,$captain_pincode,$captain_lat_long){
    $shortcode  = chotu_set_featured_product($csc_slug);
    $shortcode .= chotu_set_cat_tag_shortcode($csc_term_id);
    //$shortcode .= '[chotu_product_categories parent="'.$csc_slug.'"],';
    $shortcode .= chotu_set_cat_sub_cat_shortcode($csc_term_id);
    return $shortcode;
  }
}
if(!function_exists('chotu_set_featured_product')){  
    /**
     * chotu_set_featured_product
     *
     * @param  mixed $cat_id
     * @return string
     */
    function chotu_set_featured_product($csc_slug){
        return '<h2>Top Products</h2>[featured_products category="'.$csc_slug.'"],';
      }
}
if(!function_exists('chotu_set_cat_tag_shortcode')){  
  /**
   * chotu_set_cat_tag_shortcode
   *
   * @param  mixed $cat_id
   * @return string
   */
  function chotu_set_cat_tag_shortcode($cat_id){
    $tags = get_category_tags(array('categories'=> $cat_id));
    $content = '';
    if(!empty($tags)){
      $ids = implode("|",array_column($tags,'tag_id'));
      $content .= '<h2>Product Tags</h2>';
      $content .= '[tagList tag_ids="'.$ids.'"],';
    }
    return $content;
  }
}
if(!function_exists('get_category_tags')){  
  /**
   * get_category_tags
   *
   * @param  mixed $args
   * @return array
   */
  function get_category_tags($args) {
    global $wpdb;
    $tags = $wpdb->get_results("
        SELECT DISTINCT terms2.term_id as tag_id, terms2.name as tag_name, terms2.slug as tag_slug, null as tag_link
        FROM
            wp_posts as p1
            LEFT JOIN wp_term_relationships as r1 ON p1.ID = r1.object_ID
            LEFT JOIN wp_term_taxonomy as t1 ON r1.term_taxonomy_id = t1.term_taxonomy_id
            LEFT JOIN wp_terms as terms1 ON t1.term_id = terms1.term_id,
  
            wp_posts as p2
            LEFT JOIN wp_term_relationships as r2 ON p2.ID = r2.object_ID
            LEFT JOIN wp_term_taxonomy as t2 ON r2.term_taxonomy_id = t2.term_taxonomy_id
            LEFT JOIN wp_terms as terms2 ON t2.term_id = terms2.term_id
        WHERE
            t1.taxonomy = 'product_cat' AND p1.post_status = 'publish' AND terms1.term_id IN (".$args['categories'].") AND 
            t2.taxonomy = 'product_tag' AND p2.post_status = 'publish'
            AND p1.ID = p2.ID
        ORDER by tag_name
    ");
  
    $count = 0;
  
    foreach ($tags as $tag) {
        $tags[$count]->tag_link = get_tag_link($tag->tag_id);
        $count++;
    }
    return $tags;
  }
}


if(!function_exists('chotu_set_cat_sub_cat_shortcode')){  
  /**
   * chotu_set_cat_sub_cat_shortcode
   *
   * @param  mixed $items
   * @return string
   */
  function chotu_set_cat_sub_cat_shortcode($term_id){
    $code = '';
    $taxonomy_name = 'product_cat';
    $items = get_terms( $taxonomy_name, array(
      'parent'    => $term_id,
      'hide_empty' => false
    ));
    
    foreach( $items as $item ){
      $code .= '<h2>' . $item->name.'</h2>';
      $children = get_term_children($item->term_id, $taxonomy_name );
        if (!empty( $children))
        {
          //$code .= '<h2>' . $item->name.'</h2>';
          //$code .='[chotu_product_categories slug="'.$item->slug.'"],';
          $code .='[chotu_product_categories parent="'.$item->slug.'"],';
          $code .= chotu_set_cat_sub_cat_shortcode($item->term_id);
        }else{
          //$code .= '<h2>' . $item->name.'</h2>';
          $code .='[products category="'.$item->slug.'" limit = "3" orderby = "menu_order"]<br/><a class="button alt" href="'.get_category_link( $item->term_id ).'">more</a>,';
        }
    }
    return $code;
  }
}
if(!function_exists('chotu_set_product_categories')){
  add_shortcode('chotu_product_categories','chotu_set_product_categories');
  /**
   * chotu_set_product_categories
   * convert product_categories to take slug instead of ID.
   * @param  mixed $atts
   * @return void
   */
  function chotu_set_product_categories($atts){
    if(isset($atts['parent']) && $atts['parent'] !=''){
      $category = get_term_by( 'slug', $atts['parent'], 'product_cat' );
      // dd($category);
      ob_start();
      if(!empty($category)){
        echo do_shortcode('[product_categories parent="'.$category->term_id.'"]');
      }
      $output = ob_get_clean();
      return $output;
    }
  }
}

