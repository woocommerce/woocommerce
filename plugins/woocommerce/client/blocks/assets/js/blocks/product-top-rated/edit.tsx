/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductTopRatedBlock } from './block';
import { Props } from './types';

export const Edit = ( props: unknown & Props ): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ProductTopRatedBlock { ...props } />
		</div>
	);
};
