/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import ProductIcon from 'gridicons/dist/product';
import CloudOutlineIcon from 'gridicons/dist/cloud-outline';
import TypesIcon from 'gridicons/dist/types';
import { Icon, chevronRight } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Link from './icon/link_24px.js';
import Widget from './icon/widgets_24px.js';
import LightBulb from './icon/lightbulb_24px.js';

export const productTypes = Object.freeze( [
	{
		key: 'physical' as const,
		title: __( 'Physical product', 'woocommerce' ),
		content: __(
			'A tangible item that gets delivered to customers.',
			'woocommerce'
		),
		before: <ProductIcon />,
		after: <Icon icon={ chevronRight } />,
	},
	{
		key: 'digital' as const,
		title: __( 'Digital product', 'woocommerce' ),
		content: __(
			'A digital product like service, downloadable book, music or video.',
			'woocommerce'
		),
		before: <CloudOutlineIcon />,
		after: <Icon icon={ chevronRight } />,
	},
	{
		key: 'variable' as const,
		title: __( 'Variable product', 'woocommerce' ),
		content: __(
			'A product with variations like color or size.',
			'woocommerce'
		),
		before: <TypesIcon />,
		after: <Icon icon={ chevronRight } />,
	},
	{
		key: 'grouped' as const,
		title: __( 'Grouped product', 'woocommerce' ),
		content: __( 'A collection of related products.', 'woocommerce' ),
		before: <Widget />,
		after: <Icon icon={ chevronRight } />,
	},
	{
		key: 'external' as const,
		title: __( 'External product', 'woocommerce' ),
		content: __( 'Link a product to an external website.', 'woocommerce' ),
		before: <Link />,
		after: <Icon icon={ chevronRight } />,
	},
] );

export const LoadSampleProductType = {
	key: 'load-sample-product' as const,
	title: __( 'can’t decide?', 'woocommerce' ),
	content: __(
		'Load sample products and see what they look like in your store.',
		'woocommerce'
	),
	before: <LightBulb />,
	after: <Icon icon={ chevronRight } />,
	className: 'woocommerce-products-list__item-load-sample-product',
};

export type ProductType =
	| ( typeof productTypes )[ number ]
	| typeof LoadSampleProductType;
export type ProductTypeKey = ProductType[ 'key' ];

export const onboardingProductTypesToSurfaced: Readonly<
	Record< string, ProductTypeKey[] >
> = Object.freeze( {
	physical: [ 'physical', 'variable', 'grouped' ],
	downloads: [ 'digital' ],
	// key in alphabetical and ascending order for mapping
	'downloads,physical': [ 'physical', 'digital' ],
} );
export const defaultSurfacedProductTypes =
	onboardingProductTypesToSurfaced.physical;

export const supportedOnboardingProductTypes = [ 'physical', 'downloads' ];

export const SETUP_TASKLIST_PRODUCT_TYPES_FILTER =
	'experimental_woocommerce_tasklist_product_types';
