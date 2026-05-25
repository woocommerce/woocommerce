/**
 * External dependencies
 */
import {
	BlockContextProvider,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { isProductResponseItem, useProduct } from '@woocommerce/entities';
import type { BlockEditProps, TemplateArray } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type {
	SelectableItem,
	SelectableItemsContext,
} from '../../types/type-defs/selectable-items';

type Term = {
	id: number;
	slug: string;
	name: string;
};

type ProductAttribute = {
	id: number;
	name: string;
	taxonomy?: string;
	terms?: Term[];
};

type SwatchFields = {
	color?: string;
};

const STORE_NAMESPACE = 'woocommerce/product-image-with-color-swatches';

const TEMPLATE: TemplateArray = [
	[
		'woocommerce/product-image',
		{
			imageSizing: 'thumbnail',
			showSaleBadge: false,
		},
		[
			[
				'woocommerce/product-sale-badge',
				{
					align: 'right',
				},
			],
		],
	],
	[ 'woocommerce/product-filter-chips' ],
];

const ALLOWED_BLOCKS = [
	'woocommerce/product-image',
	'woocommerce/product-filter-chips',
];

function getAttributeKey( attribute: ProductAttribute ): string {
	const taxonomy = attribute.taxonomy || attribute.name;
	return taxonomy.replace( /^pa_/, '' ).toLowerCase();
}

function getColorAttribute(
	attributes: ProductAttribute[] | undefined,
	termColors: Record< string, string >
): ProductAttribute | undefined {
	if ( ! Array.isArray( attributes ) ) {
		return undefined;
	}

	return (
		attributes.find( ( attribute ) =>
			attribute.terms?.some( ( term ) => String( term.id ) in termColors )
		) ||
		attributes.find( ( attribute ) => {
			const attributeKey = getAttributeKey( attribute );
			return attributeKey === 'color' || attributeKey === 'colour';
		} )
	);
}

function getSelectableItems(
	attribute: ProductAttribute | undefined,
	termColors: Record< string, string >
): SelectableItem< SwatchFields >[] {
	if ( ! attribute || ! Array.isArray( attribute.terms ) ) {
		return [];
	}

	return attribute.terms.map( ( term ) => ( {
		id: `product-image-swatch-${ term.slug }`,
		label: term.name,
		ariaLabel: sprintf(
			/* translators: %s: color name */
			__( 'Show %s image', 'woocommerce' ),
			term.name
		),
		value: term.slug,
		...( String( term.id ) in termColors
			? { color: termColors[ String( term.id ) ] }
			: {} ),
	} ) );
}

const Edit = ( {
	context,
}: BlockEditProps< Record< string, never > > ): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-product-image-with-color-swatches',
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		allowedBlocks: ALLOWED_BLOCKS,
	} );
	const { product } = useProduct( context.postId as number );
	const termColors = getSetting< Record< string, string > >(
		'productImageWithColorSwatchesTermColors',
		{}
	);

	const selectableContext = useMemo( () => {
		const attributes =
			isProductResponseItem( product ) && product.type === 'variable'
				? ( product.attributes as ProductAttribute[] )
				: [];
		const colorAttribute = getColorAttribute( attributes, termColors );

		return {
			items: getSelectableItems( colorAttribute, termColors ),
			selectionMode: 'single' as const,
			storeNamespace: STORE_NAMESPACE,
			groupLabel: colorAttribute?.name || __( 'Color', 'woocommerce' ),
		} satisfies SelectableItemsContext< SwatchFields >;
	}, [ product, termColors ] );

	console.log(selectableContext)

	return (
		<BlockContextProvider
			value={ {
				'woocommerce/selectableItems': selectableContext,
			} }
		>
			<div { ...innerBlocksProps } />
		</BlockContextProvider>
	);
};

export default Edit;
