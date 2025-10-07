/**
 * External dependencies
 */
import {
	registerBlockVariation,
	unregisterBlockVariation,
} from '@wordpress/blocks';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { stacks } from '@woocommerce/icons';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { select, subscribe } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import {
	QueryBlockAttributes,
	ProductQueryBlockQuery,
} from '@woocommerce/blocks/product-query/types';
import { isSiteEditorPage } from '@woocommerce/utils';
import { isNumber, isString } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	PRODUCT_QUERY_VARIATION_NAME,
	DEFAULT_ALLOWED_CONTROLS,
	INNER_BLOCKS_TEMPLATE,
	QUERY_DEFAULT_ATTRIBUTES,
	QUERY_LOOP_ID,
} from '../constants';

const ARCHIVE_PRODUCT_TEMPLATES = [
	'archive-product',
	'taxonomy-product_cat',
	'taxonomy-product_tag',
	'taxonomy-product_brand',
	'taxonomy-product_attribute',
	'product-search-results',
];

const registerProductsBlock = ( attributes: QueryBlockAttributes ) => {
	registerBlockVariation( QUERY_LOOP_ID, {
		description: __(
			'A block that displays a selection of products in your store.',
			'woocommerce'
		),
		name: PRODUCT_QUERY_VARIATION_NAME,
		/* translators: "Products" is the name of the block. */
		title: __( 'Products (Beta)', 'woocommerce' ),
		isActive: ( blockAttributes ) =>
			blockAttributes.namespace === PRODUCT_QUERY_VARIATION_NAME,
		icon: (
			<Icon
				icon={ stacks }
				className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--stacks"
			/>
		),
		attributes: {
			...attributes,
			namespace: PRODUCT_QUERY_VARIATION_NAME,
		},
		// Gutenberg doesn't support this type yet, discussion here:
		// https://github.com/WordPress/gutenberg/pull/43632
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		allowedControls: DEFAULT_ALLOWED_CONTROLS,
		innerBlocks: INNER_BLOCKS_TEMPLATE,
		scope: [],
	} );
};

let currentTemplateSlug: string | undefined;
subscribe( () => {
	const previousTemplateSlug = currentTemplateSlug;
	// @ts-expect-error getEditedPostSlug is not typed
	currentTemplateSlug = select( editorStore )?.getEditedPostSlug();
	if ( previousTemplateSlug === currentTemplateSlug ) {
		return;
	}

	if ( isSiteEditorPage() ) {
		const inherit = ARCHIVE_PRODUCT_TEMPLATES.some( ( template ) =>
			isString( currentTemplateSlug )
				? currentTemplateSlug.includes( template )
				: false
		);

		const inheritQuery: Partial< ProductQueryBlockQuery > = {
			inherit,
		};

		if ( inherit ) {
			inheritQuery.perPage = getSettingWithCoercion(
				'loopShopPerPage',
				12,
				isNumber
			);
		}

		const queryAttributes = {
			...QUERY_DEFAULT_ATTRIBUTES,
			query: {
				...QUERY_DEFAULT_ATTRIBUTES.query,
				...inheritQuery,
			},
		};

		unregisterBlockVariation( QUERY_LOOP_ID, PRODUCT_QUERY_VARIATION_NAME );

		registerProductsBlock( queryAttributes );
	}
}, 'core/edit-site' );

let isBlockRegistered = false;
subscribe( () => {
	if ( ! isBlockRegistered ) {
		isBlockRegistered = true;
		registerProductsBlock( QUERY_DEFAULT_ATTRIBUTES );
	}
}, 'core/edit-post' );
