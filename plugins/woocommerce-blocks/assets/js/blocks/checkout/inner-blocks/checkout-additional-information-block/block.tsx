/**
 * External dependencies
 */
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { ADDITIONAL_FORM_KEYS } from '@woocommerce/block-settings';
import { Form } from '@woocommerce/base-components/cart-checkout';
import type { FunctionComponent } from 'react';

const Block: FunctionComponent = () => {
	const { additionalFields } = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			additionalFields: store.getAdditionalFields(),
		};
	} );

	const { setAdditionalFields } = useDispatch( CHECKOUT_STORE_KEY );

	const onChangeForm = ( additionalValues ) => {
		setAdditionalFields( additionalValues );
	};

	const additionalFieldValues = {
		...additionalFields,
	};

	if ( ADDITIONAL_FORM_KEYS.length === 0 ) {
		return null;
	}

	return (
		<>
			<StoreNoticesContainer
				context={ noticeContexts.ADDITIONAL_INFORMATION }
			/>
			<Form
				id="additional-information"
				addressType="additional-information"
				onChange={ onChangeForm }
				values={ additionalFieldValues }
				fields={ ADDITIONAL_FORM_KEYS }
			/>
		</>
	);
};

export default Block;
