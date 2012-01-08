<?php get_header('shop'); ?>

<?php do_action('woocommerce_before_main_content'); ?>

<?php woocommerce_product_taxonomy_content(); ?>

<?php do_action('woocommerce_after_main_content'); ?>

<?php do_action('woocommerce_sidebar'); ?>

<?php get_footer('shop'); ?>