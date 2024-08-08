/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import ProductIcon from 'gridicons/dist/product';
import CloudOutlineIcon from 'gridicons/dist/cloud-outline';
import TypesIcon from 'gridicons/dist/types';
import { Icon, chevronRight } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Link from './icon/link_24px.js';
import Widget from './icon/widgets_24px.js';
import LightBulb from './icon/lightbulb_24px.js';
import PrintfulIcon from './icon/printful.png';
import Upload from './icon/upload_40px.js';

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

export const PrintfulAdvertProductPlacement = {
	key: 'printful-advert' as const,
	title: (
		<span className="printful-sponsored__text">
			{ __( 'Print-on-demand products', 'woocommerce' ) }
			<div className="woocommerce-label">
				{ __( 'Promoted', 'woocommerce' ) }
			</div>
		</span>
	),
	content: __(
		'Design and easily sell custom print products online with Printful.',
		'woocommerce'
	),
	className: 'woocommerce-products-list__item-advert',
	before: (
		<img
			className="printful-sponsored__icon"
			alt="Printful"
			src={ PrintfulIcon }
		/>
	),
	after: <Icon icon={ chevronRight } />,
	onClick: () => {
		recordEvent( 'tasklist_product_printful_advert_click' );
		window.open( 'https://woocommerce.com/products/printful', '_blank' );
	},
};

export const ImportCSVItem = {
	key: 'import-csv' as const,
	title: (
		<span className="printful-sponsored__text">
			{ __( 'Are you already selling somewhere else?', 'woocommerce' ) }
		</span>
	),
	content: __( 'Import your products from a CSV file.', 'woocommerce' ),
	className: 'woocommerce-products-list__item-advert',
	before: <Upload />,
	after: <Icon icon={ chevronRight } />,
	onClick: () => {
		recordEvent( 'tasklist_add_product', {
			method: 'import',
		} );
		window.location.href = getAdminLink(
			'edit.php?post_type=product&page=product_importer&wc_onboarding_active_task=products'
		);
	},
};

export type SponsoredProductPlacementType =
	| typeof PrintfulAdvertProductPlacement
	| typeof ImportCSVItem;

export type ProductType =
	| ( typeof productTypes )[ number ]
	| typeof LoadSampleProductType
	| SponsoredProductPlacementType;
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

export const SETUP_TASKLIST_PRODUCTS_AFTER_FILTER =
	'woocommerce_admin_task_products_after';
