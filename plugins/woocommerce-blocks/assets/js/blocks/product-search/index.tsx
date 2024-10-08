/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { store as blockEditorStore, Warning } from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, search } from '@wordpress/icons';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean } from '@woocommerce/types';
import { Button } from '@wordpress/components';
import type { Block as BlockType } from '@wordpress/blocks';
import {
	// @ts-ignore waiting for @types/wordpress__blocks update
	registerBlockVariation,
	registerBlockType,
	createBlock,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import { withProductSearchControls } from './inspector-controls';
import Block from './block';
import Edit from './edit';
import { SEARCH_BLOCK_NAME, SEARCH_VARIATION_NAME } from './constants';

const isBlockVariationAvailable = getSettingWithCoercion(
	'isBlockVariationAvailable',
	false,
	isBoolean
);

const attributes = {
	/**
	 * Whether to show the field label.
	 */
	hasLabel: {
		type: 'boolean',
		default: true,
	},

	/**
	 * Search field label.
	 */
	label: {
		type: 'string',
		default: __( 'Search', 'woocommerce' ),
	},

	/**
	 * Search field placeholder.
	 */
	placeholder: {
		type: 'string',
		default: __( 'Search products…', 'woocommerce' ),
	},

	/**
	 * Store the instance ID.
	 */
	formId: {
		type: 'string',
		default: '',
	},
};

const PRODUCT_SEARCH_ATTRIBUTES = {
	label: attributes.label.default,
	buttonText: attributes.label.default,
	placeholder: attributes.placeholder.default,
	query: {
		post_type: 'product',
	},
	namespace: SEARCH_VARIATION_NAME,
};

const DeprecatedBlockEdit = ( { clientId }: { clientId: string } ) => {
	// @ts-ignore @wordpress/block-editor/store types not provided
	const { replaceBlocks } = useDispatch( blockEditorStore );

	const currentBlockAttributes = useSelect(
		( select ) =>
			select( 'core/block-editor' ).getBlockAttributes( clientId ),
		[ clientId ]
	);

	const updateBlock = () => {
		replaceBlocks(
			clientId,
			createBlock( 'core/search', {
				label:
					currentBlockAttributes?.label ||
					PRODUCT_SEARCH_ATTRIBUTES.label,
				buttonText: PRODUCT_SEARCH_ATTRIBUTES.buttonText,
				placeholder:
					currentBlockAttributes?.placeholder ||
					PRODUCT_SEARCH_ATTRIBUTES.placeholder,
				query: PRODUCT_SEARCH_ATTRIBUTES.query,
			} )
		);
	};

	const actions = [
		<Button key="update" onClick={ updateBlock } variant="primary">
			{ __( 'Upgrade Block', 'woocommerce' ) }
		</Button>,
	];

	return (
		<Warning actions={ actions } className="wc-block-components-actions">
			{ __(
				'This version of the Product Search block is outdated. Upgrade to continue using.',
				'woocommerce'
			) }
		</Warning>
	);
};

registerBlockType( SEARCH_VARIATION_NAME, {
	title: __( 'Product Search', 'woocommerce' ),
	apiVersion: 3,
	icon: {
		src: (
			<Icon
				icon={ search }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'A search box to allow customers to search for products by keyword.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
		inserter: ! isBlockVariationAvailable,
	},
	attributes,
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/legacy-widget' ],
				// We can't transform if raw instance isn't shown in the REST API.
				isMatch: ( { idBase, instance } ) =>
					idBase === 'woocommerce_product_search' && !! instance?.raw,
				transform: ( { instance } ) =>
					createBlock( SEARCH_VARIATION_NAME, {
						label:
							instance.raw.title ||
							PRODUCT_SEARCH_ATTRIBUTES.label,
					} ),
			},
		],
	},
	deprecated: [
		{
			attributes,
			save( props ) {
				return (
					<div>
						<Block { ...props } />
					</div>
				);
			},
		},
	],
	edit: isBlockVariationAvailable ? DeprecatedBlockEdit : Edit,
	save() {
		return null;
	},
} );

function registerProductSearchNamespace( props: BlockType, blockName: string ) {
	if ( blockName === 'core/search' ) {
		// Gracefully handle if settings.attributes is undefined.
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- We need this because `attributes` is marked as `readonly`
		props.attributes = {
			...props.attributes,
			namespace: {
				type: 'string',
			},
		};
	}

	return props;
}

addFilter(
	'blocks.registerBlockType',
	SEARCH_VARIATION_NAME,
	registerProductSearchNamespace
);

if ( isBlockVariationAvailable ) {
	registerBlockVariation( 'core/search', {
		name: SEARCH_VARIATION_NAME,
		title: __( 'Product Search', 'woocommerce' ),
		icon: {
			src: (
				<Icon
					icon={ search }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		// @ts-ignore waiting for @types/wordpress__blocks update
		isActive: ( blockAttributes, variationAttributes ) => {
			return (
				blockAttributes.query?.post_type ===
				variationAttributes.query.post_type
			);
		},
		category: 'woocommerce',
		keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
		description: __(
			'A search box to allow customers to search for products by keyword.',
			'woocommerce'
		),
		attributes: PRODUCT_SEARCH_ATTRIBUTES,
	} );
	addFilter(
		'editor.BlockEdit',
		SEARCH_BLOCK_NAME,
		withProductSearchControls
	);
}
