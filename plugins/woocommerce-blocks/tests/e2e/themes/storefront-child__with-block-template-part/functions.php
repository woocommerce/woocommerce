<?php
add_action( 'after_setup_theme', 'add_block_template_part_support' );

function add_block_template_part_support() {
    add_theme_support( 'block-template-parts' );
}
