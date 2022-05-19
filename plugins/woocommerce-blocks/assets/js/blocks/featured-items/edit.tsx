/**
 * External dependencies
 */
import { FunctionComponent } from 'react';
import { useBlockProps } from '@wordpress/block-editor';

export function Edit< T >( Block: FunctionComponent< T > ) {
	return function WithBlock( props: T ): JSX.Element {
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
}
