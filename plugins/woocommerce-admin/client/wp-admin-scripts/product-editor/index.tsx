/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmbeddedProductPageLayout } from './embedded-product-page-layout';

const productRoot = document.getElementById( 'woocommerce-product-root' );

render( <EmbeddedProductPageLayout />, productRoot );
