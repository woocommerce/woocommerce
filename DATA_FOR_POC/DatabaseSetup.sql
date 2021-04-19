INSERT INTO wp_terms (term_id, name, slug) VALUES
(25,'Red','red'), 
(26,'Blue','blue'), 
(27,'Green','green'), 
(28,'Brown','brown'), 
(29,'Black','black'), 
(30,'Small','small'), 
(31,'Medium','medium'), 
(32,'Large','large'), 
(34,'Washable','washable'), 
(35,'Elastic','elastic'), 
(36,'Ironable','ironable'), 
(37,'White','white'), 
(38,'Pink','pink');

INSERT INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, count) VALUES
(25,25,'pa_color','',4),
(26,26,'pa_color','',5),
(27,27,'pa_color','',3),
(28,28,'pa_color','',2),
(29,29,'pa_color','',7),
(30,30,'pa_size','',3),
(31,31,'pa_size','',4),
(32,32,'pa_size','',4),
(33,33,'post_tag','',2),
(34,34,'pa_features','',2),
(35,35,'pa_features','',2),
(36,36,'pa_features','',2),
(37,37,'pa_color','',4),
(38,38,'pa_color','',2);


CREATE TABLE wp_wc_product_attributes_lookup (
  product_id bigint(20) NOT NULL,
  product_or_parent_id bigint(20) NOT NULL,
  taxonomy varchar(32) NOT NULL,
  term_id bigint(20) NOT NULL,
  is_variation_attribute tinyint(1) NOT NULL,
  in_stock tinyint(1) NOT NULL
 );


INSERT INTO wp_wc_product_attributes_lookup ( product_id, product_or_parent_id, taxonomy, term_id, is_variation_attribute, in_stock ) VALUES

/* Elegant Shoes (238) */

( 239, 238, 'pa_color', 29, 1, 1 ), /* Black */
( 240, 238, 'pa_color', 26, 1, 1 ), /* Blue */
( 241, 238, 'pa_color', 28, 1, 1 ), /* Brown */
( 242, 238, 'pa_color', 37, 1, 0 ), /* White - NO STOCK */

/* Kid shoes (244) */

( 245, 244, 'pa_color', 26, 1, 0 ), /* Blue - NO STOCK */
( 246, 244, 'pa_color', 27, 1, 1 ), /* Green */
( 247, 244, 'pa_color', 38, 1, 1 ), /* Pink */
( 248, 244, 'pa_color', 25, 1, 1 ), /* Red */
( 249, 244, 'pa_color', 37, 1, 1 ), /* White */

( 244, 244, 'pa_features', 35, 0, 1 ), /* Elastic */
( 244, 244, 'pa_features', 34, 0, 1 ), /* Washable */

/* Elegant shirt (251) */

( 253, 251, 'pa_color', 29, 1, 1 ), /* BLACK, Large */
( 253, 251, 'pa_size', 32, 1, 1 ), /* Black, LARGE */
( 254, 251, 'pa_color', 37, 1, 1 ), /* WHITE, Large */
( 254, 251, 'pa_size', 32, 1, 1 ), /* White, LARGE */
( 255, 251, 'pa_color', 37, 1, 0 ), /* WHITE, Medium - NO STOCK */
( 255, 251, 'pa_size', 31, 1, 0 ), /* White, MEDIUM - NO STOCK */
( 256, 251, 'pa_color', 26, 1, 1 ), /* BLUE, (Any size) */
( 256, 251, 'pa_size', 32, 1, 1 ), /* Blue, LARGE */
( 256, 251, 'pa_size', 31, 1, 1 ), /* Blue, MEDIUM */

( 251, 251, 'pa_features', 36, 0, 1 ), /* Ironable */
( 251, 251, 'pa_features', 34, 0, 1 ), /* Washable */

/* MSX Cap (257) */

( 257, 257, 'pa_color', 29, 0, 1 ), /* Black */
( 257, 257, 'pa_color', 37, 0, 1 ), /* White */
( 257, 257, 'pa_color', 25, 0, 1 ), /* Red */

( 257, 257, 'pa_features', 35, 0, 1 ), /* Elastic */
( 257, 257, 'pa_features', 36, 0, 1 ), /* Ironable */

/* Gradius Jacket (259) */

( 260, 259, 'pa_color', 29, 1, 1 ), /* BLACK, (Any size) */
( 260, 259, 'pa_size', 31, 1, 1 ), /* Black, MEDIUM */
( 260, 259, 'pa_size', 30, 1, 1 ), /* Black, SMALL */
( 261, 259, 'pa_color', 26, 1, 1 ), /* BLUE, (Any size) */
( 261, 259, 'pa_size', 31, 1, 1 ), /* Blue, MEDIUM */
( 261, 259, 'pa_size', 30, 1, 1 ), /* Blue, SMALL */
( 262, 259, 'pa_color', 25, 1, 0 ), /* RED, (Any size) - NO STOCK */
( 262, 259, 'pa_size', 31, 1, 0 ), /* Red, MEDIUM - NO STOCK */
( 262, 259, 'pa_size', 30, 1, 0 ), /* Red, SMALL - NO STOCK */

/* Vantablack (267) */

( 267, 267, 'pa_color', 29, 0, 1 ), /* Black */

/* Casual shirt (269) */

( 270, 269, 'pa_color', 27, 1, 0 ), /* GREEN, (Any size) - NO STOCK */
( 270, 269, 'pa_size', 30, 1, 0 ), /* Green, SMALL - NO STOCK */
( 270, 269, 'pa_size', 31, 1, 0 ), /* Green, MEDIUM - NO STOCK */
( 270, 269, 'pa_size', 32, 1, 0 ), /* Green, LARGE - NO STOCK */
( 271, 269, 'pa_color', 38, 1, 1 ), /* PINK, (Any size) */
( 271, 269, 'pa_size', 30, 1, 1 ), /* Pink, SMALL */
( 271, 269, 'pa_size', 31, 1, 1 ), /* Pink, MEDIUM */
( 271, 269, 'pa_size', 32, 1, 1 ); /* Pink, LARGE */
