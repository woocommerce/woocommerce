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
import { isExperimentalWcRestApiV4Enabled } from '@woocommerce/block-settings';
import { useProduct } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import Block from './block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';

type UnsupportedAligments = 'wide' | 'full';
type AllowedAlignments = Exclude< BlockAlignment, UnsupportedAligments >;

interface BlockAttributes {
	textAlign?: AllowedAlignments;
}

interface Attributes {
	textAlign: 'left' | 'center' | 'right';
	isDescendentOfSingleProduct: boolean;
	isDescendentOfSingleProductBlock: boolean;
	productId: number;
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

	let { isDescendentOfSingleProductTemplate } =
		useIsDescendentOfSingleProductTemplate();

	if ( isDescendentOfQueryLoop ) {
		isDescendentOfSingleProductTemplate = false;
	}

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

	const isExperimentalWcRestApiEnabled = isExperimentalWcRestApiV4Enabled();

	const { product } = useProduct( context.postId );

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
				{
					// If experimental WC API is not available we must fallback to "old" way of fetching product data.
					// Once it's available everywhere we can remove this fallback.
					isExperimentalWcRestApiEnabled ? (
						<Block
							{ ...blockAttrs }
							isAdmin={ true }
							product={ product }
							isExperimentalWcRestApiV4Enabled={
								isExperimentalWcRestApiEnabled
							}
						/>
					) : (
						<Block
							{ ...blockAttrs }
							product={ undefined }
							isAdmin={ false }
							isExperimentalWcRestApiV4Enabled={ false }
						/>
					)
				}
			</div>
		</>
	);
};

export default PriceEdit;
