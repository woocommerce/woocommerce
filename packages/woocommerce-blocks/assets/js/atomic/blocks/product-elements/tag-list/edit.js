/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import EditProductLink from '@woocommerce/editor-components/edit-product-link';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';

const Edit = ( { attributes } ) => {
	return (
		<>
			<EditProductLink />
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</>
	);
};

export default withProductSelector( {
	icon: BLOCK_ICON,
	label: BLOCK_TITLE,
	description: __(
		'Choose a product to display its tags.',
		'woocommerce'
	),
} )( Edit );
