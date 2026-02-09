/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import QuantityStepper from '../components/quantity-stepper';

const AddToCartWithOptionsQuantitySelectorEdit = () => {
	const blockProps = useBlockProps( {
		className: 'wc-block-add-to-cart-with-options__quantity-selector',
	} );

	return (
		<div { ...blockProps }>
			<Disabled>
				<QuantityStepper />
			</Disabled>
		</div>
	);
};

export default AddToCartWithOptionsQuantitySelectorEdit;
