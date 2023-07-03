/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import type { ProductsByTagBlockProps } from './types';

export const Edit = ( props: ProductsByTagBlockProps ): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Block { ...props } />
		</div>
	);
};
