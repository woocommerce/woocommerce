/**
 * External dependencies
 */
import EditProductLink from '@woocommerce/editor-components/edit-product-link';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import {
	BLOCK_TITLE as label,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';
import type { BlockAttributes } from './types';

interface Props {
	attributes: BlockAttributes;
}

const Edit = ( { attributes }: Props ): JSX.Element => {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<EditProductLink />
			<Block { ...attributes } />
		</div>
	);
};

export default withProductSelector( { icon, label, description } )( Edit );
