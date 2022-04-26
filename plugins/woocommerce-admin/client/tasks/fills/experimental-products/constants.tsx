/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import ProductIcon from 'gridicons/dist/product';
import CloudOutlineIcon from 'gridicons/dist/cloud-outline';
import TypesIcon from 'gridicons/dist/types';
import CalendarIcon from 'gridicons/dist/calendar';
import { Icon, chevronRight, widget, link } from '@wordpress/icons';

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
		key: 'subscription' as const,
		title: __( 'Subscription product', 'woocommerce' ),
		content: __(
			'Item that customers receive on a regular basis.',
			'woocommerce'
		),
		before: <CalendarIcon />,
		after: <Icon icon={ chevronRight } />,
	},
	{
		key: 'grouped' as const,
		title: __( 'Grouped product', 'woocommerce' ),
		content: __( 'A collection of related products.', 'woocommerce' ),
		before: <Icon icon={ widget } />,
		after: <Icon icon={ chevronRight } />,
	},
	{
		key: 'external' as const,
		title: __( 'External product', 'woocommerce' ),
		content: __( 'Link a product to an external website.', 'woocommerce' ),
		before: <Icon icon={ link } />,
		after: <Icon icon={ chevronRight } />,
	},
] );

export type ProductType = typeof productTypes[ number ];
export type ProductTypeKey = ProductType[ 'key' ];
