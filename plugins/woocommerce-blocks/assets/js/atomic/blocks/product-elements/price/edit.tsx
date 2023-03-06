/**
 * External dependencies
 */
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import type { BlockAlignment } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Block from './block';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';
import { ProductSelector } from '../shared/product-selector';

type UnsupportedAligments = 'wide' | 'full';
type AllowedAlignments = Exclude< BlockAlignment, UnsupportedAligments >;

interface BlockAttributes {
	textAlign?: AllowedAlignments;
}

interface Attributes {
	textAlign: 'left' | 'center' | 'right';
}

interface Context {
	queryId: number;
}

interface Props {
	attributes: Attributes;
	setAttributes: (
		attributes: Partial< BlockAttributes > & Record< string, unknown >
	) => void;
	context: Context;
}

const PriceEdit = ( {
	attributes,
	setAttributes,
	context,
}: Props ): JSX.Element => {
	const blockProps = useBlockProps();
	const blockAttrs = {
		...attributes,
		...context,
	};
	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );

	const isDescendentOfSingleProductTemplate = useSelect(
		( select ) => {
			const store = select( 'core/edit-site' );
			const postId = store?.getEditedPostId();

			return (
				( postId === 'woocommerce/woocommerce//product-meta' ||
					postId === 'woocommerce/woocommerce//single-product' ) &&
				! isDescendentOfQueryLoop
			);
		},
		[ isDescendentOfQueryLoop ]
	);

	useEffect(
		() =>
			setAttributes( {
				isDescendentOfQueryLoop,
				isDescendentOfSingleProductTemplate,
			} ),
		[
			isDescendentOfQueryLoop,
			isDescendentOfSingleProductTemplate,
			setAttributes,
		]
	);

	const showProductSelector =
		! isDescendentOfQueryLoop && ! isDescendentOfSingleProductTemplate;

	if ( ! showProductSelector ) {
		return (
			<>
				<BlockControls>
					<AlignmentToolbar
						value={ attributes.textAlign }
						onChange={ ( textAlign: AllowedAlignments ) => {
							setAttributes( { textAlign } );
						} }
					/>
				</BlockControls>
				<div { ...blockProps }>
					<Block { ...blockAttrs } />
				</div>
			</>
		);
	}

	return (
		<div { ...blockProps }>
			<ProductSelector
				productId={ attributes.productId }
				setAttributes={ setAttributes }
				icon={ BLOCK_ICON }
				label={ BLOCK_TITLE }
				description={ __(
					'Choose a product to display its price.',
					'woo-gutenberg-products-block'
				) }
			>
				<BlockControls>
					<AlignmentToolbar
						value={ attributes.textAlign }
						onChange={ ( textAlign: AllowedAlignments ) => {
							setAttributes( { textAlign } );
						} }
					/>
				</BlockControls>
				<Block { ...blockAttrs } />
			</ProductSelector>
		</div>
	);
};

export default PriceEdit;
