/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductNewestBlock } from './block';
import { ProductNewestBlockProps } from './types';

export const Edit = (
	props: unknown & ProductNewestBlockProps
): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ProductNewestBlock { ...props } />
		</div>
	);
};
