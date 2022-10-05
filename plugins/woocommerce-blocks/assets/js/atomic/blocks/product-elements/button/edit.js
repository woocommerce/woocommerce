/**
 * External dependencies
 */
import { Disabled } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
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
		<div { ...blockProps }>
			<Disabled>
				<Block { ...{ ...attributes, ...context } } />
			</Disabled>
		</div>
	);
};

export default Edit;
