/**
 * External dependencies
 */
import { Disabled } from '@wordpress/components';
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Block from './block';

const Edit = ( { attributes, setAttributes, context } ) => {
	const blockProps = useBlockProps();
	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );

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
