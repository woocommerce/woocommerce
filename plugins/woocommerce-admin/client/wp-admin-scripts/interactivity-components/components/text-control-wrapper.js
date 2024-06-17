/**
 * External dependencies
 */
import { TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getProductId } from '../utils';

export function TextControlWrapper( { label, property } ) {
	const product = useSelect( ( select ) => {
		const { getEditedEntityRecord } = select( 'core' );
		return getEditedEntityRecord( 'postType', 'product', getProductId() );
	} );

	const { editEntityRecord } = useDispatch( 'core' );

	return (
		<TextControl
			label={ label }
			onChange={ ( value ) => {
				editEntityRecord( 'postType', 'product', getProductId(), {
					[ property ]: value,
				} );
			} }
			value={ product[ property ] ?? '' }
		/>
	);
}
