/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { isSiteEditorPage } from '@woocommerce/utils';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import QuantityStepper from '../components/quantity-stepper';

const AddToCartWithOptionsQuantitySelectorEdit = () => {
	const blockProps = useBlockProps( {
		className: 'wc-block-add-to-cart-with-options__quantity-selector',
	} );

	const isSiteEditor = useSelect(
		( select ) => isSiteEditorPage( select( 'core/edit-site' ) ),
		[]
	);

	return (
		<div { ...blockProps }>
			<Disabled>
				<QuantityStepper isSiteEditor={ isSiteEditor } />
			</Disabled>
		</div>
	);
};

export default AddToCartWithOptionsQuantitySelectorEdit;
