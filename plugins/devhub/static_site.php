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
  <?php include('site/public/' . $template_path . 'index.html'); ?>
</div>

<?php get_footer(); ?>
