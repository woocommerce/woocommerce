<?php
/**
 * Template Name: Gatsby Page
 */

get_header(); ?>

<?php 

$request_url = $_SERVER['REQUEST_URI'];
$url_parts = parse_url($request_url);
$path = $url_parts['path'];
$segments = explode('/', $path);

array_shift($segments);
array_shift($segments);

$template_path = implode('/', $segments);

?>

<div id="gatsby" class="site-content">
  <?php 
      $tag = 'static_file_' . str_replace( '/', '_', $template_path ) . 'index.html';
      $tag_id = get_term_by('name', $tag, 'post_tag')->term_id;
      
      // Query posts by tag ID
      $args = array(
          'tag__in' => array($tag_id),
          'post_type' => 'page',
          'posts_per_page' => 1
      );
      
      $query = new WP_Query($args);

      // Render the post content
      if ($query->have_posts()) {
        while ($query->have_posts()) {
          $query->the_post();              
          the_content();
        }
      }

      // Restore original post data
      wp_reset_postdata();
    ?>
</div>

<?php get_footer(); ?>
