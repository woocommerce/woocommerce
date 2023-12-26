/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import type { ProductCategoriesBlockProps } from './types';

export const Edit = ( props: ProductCategoriesBlockProps ): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Block { ...props } />
		</div>
	);
};
