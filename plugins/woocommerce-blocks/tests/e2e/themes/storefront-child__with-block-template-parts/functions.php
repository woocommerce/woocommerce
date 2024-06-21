<?php
add_action( 'after_setup_theme', 'add_block_template_part_support' );

function add_block_template_part_support() {
    add_theme_support( 'block-template-parts' );
    
    error_log( 'From theme\'s functions.php:' );
    error_log( 'Theme: ' . wp_get_theme()->get('Name') );
    error_log( 'Supports BTP: ' . wc_bool_to_string( current_theme_supports( 'block-template-parts' ) ) );
    error_log( '-------------------------------' );

}
