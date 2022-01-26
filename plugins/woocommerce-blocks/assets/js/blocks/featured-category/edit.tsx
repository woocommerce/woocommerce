/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Block from './block';

export const Edit = ( props: unknown ): JSX.Element => {
	const blockProps = useBlockProps();

	// The useBlockProps function returns the style with the `color`.
	// We need to remove it to avoid the block to be styled with the color.
	const { color, ...styles } = blockProps.style;

	return (
		<div { ...blockProps } style={ styles }>
			<Block { ...props } />
		</div>
	);
};
