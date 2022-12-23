/**
 * External dependencies
 */
import { Disabled } from '@wordpress/components';
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';

/**
 * Internal dependencies
 */
import Block from './block';
import { BlockAttributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< BlockAttributes > & {
	context?: Context | undefined;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const isDescendentOfQueryLoop = Number.isFinite( context?.queryId );

	useEffect(
		() => setAttributes( { isDescendentOfQueryLoop } ),
		[ setAttributes, isDescendentOfQueryLoop ]
	);
	return (
		<>
			<BlockControls>
				{ isDescendentOfQueryLoop && (
					<AlignmentToolbar
						value={ attributes.textAlign }
						onChange={ ( newAlign ) => {
							setAttributes( { textAlign: newAlign || '' } );
						} }
					/>
				) }
			</BlockControls>
			<div { ...blockProps }>
				<Disabled>
					<Block { ...{ ...attributes, ...context } } />
				</Disabled>
			</div>
		</>
	);
};

export default Edit;
