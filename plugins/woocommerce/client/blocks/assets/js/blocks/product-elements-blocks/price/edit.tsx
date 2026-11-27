/**
 * External dependencies
 */
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import type { BlockAlignment } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Block from './block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';

type UnsupportedAlignments = 'wide' | 'full';
type AllowedAlignments = Exclude< BlockAlignment, UnsupportedAlignments >;

interface BlockAttributes {
	textAlign?: AllowedAlignments;
}

interface Attributes {
	textAlign: 'left' | 'center' | 'right';
	productId: number;
}

interface Context {
	queryId: number;
	postId: number;
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
				<Block
					{ ...blockAttrs }
					isDescendentOfSingleProductTemplate={
						isDescendentOfSingleProductTemplate
					}
					isAdmin={ false }
				/>
			</div>
		</>
	);
};

export default PriceEdit;
