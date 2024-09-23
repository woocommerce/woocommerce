/**
 * External dependencies
 */
import { updateCategory } from '@wordpress/blocks';
import { woo } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import '../css/editor.scss';
import '../css/style.scss';
import './filters/block-list-block';
import './filters/get-block-attributes';
import './base/components/notice-banner/style.scss';
import './atomic/utils/blocks-registration-manager';

// Icons are set on the front-end to make use of WordPress SVG primitive,
// See: https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#wp-blocks-updatecategory

updateCategory( 'woocommerce', { icon: <Icon icon={ woo } /> } );
updateCategory( 'woocommerce-product-elements', {
	icon: (
		<Icon icon={ woo } className="wc-block-editor-components-block-icon" />
	),
} );
