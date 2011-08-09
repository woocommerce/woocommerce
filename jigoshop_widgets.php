<?php
foreach(glob( dirname(__FILE__)."/widgets/*.php" ) as $filename) include_once($filename);

function jigoshop_register_widgets() {
	register_widget('Jigoshop_Widget_Recent_Products');
	register_widget('Jigoshop_Widget_Featured_Products');
	register_widget('Jigoshop_Widget_Product_Categories');
	register_widget('Jigoshop_Widget_Tag_Cloud');
	register_widget('Jigoshop_Widget_Cart');
	register_widget('Jigoshop_Widget_Layered_Nav');
	register_widget('Jigoshop_Widget_Price_Filter');
	register_widget('Jigoshop_Widget_Product_Search');
}
add_action('widgets_init', 'jigoshop_register_widgets');