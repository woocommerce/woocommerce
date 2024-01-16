/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import {
	ADDITIONAL_FORM_FIELDS,
	CONTACT_FORM_FIELDS,
} from '@woocommerce/block-settings';
import { AdditionalFieldsPlaceholder } from '@woocommerce/base-components/cart-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-additional-fields',
	} );

	const additionalFields = {
		...ADDITIONAL_FORM_FIELDS,
		...CONTACT_FORM_FIELDS,
	};

	return (
		<div { ...blockProps }>
			<AdditionalFieldsPlaceholder
				additionalFields={ additionalFields }
			/>
		</div>
	);
};

export default Edit;
