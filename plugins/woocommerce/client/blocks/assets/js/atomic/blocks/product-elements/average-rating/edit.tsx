/**
 * External dependencies
 */
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Block from './block';

export interface BlockAttributes {
	textAlign: string;
}

const Edit = ( props: BlockEditProps< BlockAttributes > ): JSX.Element => {
	const { attributes, setAttributes } = props;
	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-product-average-rating',
	} );

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ attributes.textAlign }
					onChange={ ( newAlign ) => {
						setAttributes( { textAlign: newAlign || '' } );
					} }
				/>
			</BlockControls>
			<div { ...blockProps }>
				<Block { ...attributes } />
			</div>
		</>
	);
};

export default Edit;
