/**
 * External dependencies
 */
import { noticeContexts } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { ORDER_FORM_KEYS } from '@woocommerce/block-settings';
import { Form } from '@woocommerce/base-components/cart-checkout';
import type { FunctionComponent } from 'react';

const Block: FunctionComponent = () => {
	const { additionalFields } = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			additionalFields: store.getAdditionalFields(),
		};
	} );

	const { setAdditionalFields } = useDispatch( checkoutStore );

	const onChangeForm = ( additionalValues ) => {
		setAdditionalFields( additionalValues );
	};

	const additionalFieldValues = {
		...additionalFields,
	};

	if ( ORDER_FORM_KEYS.length === 0 ) {
		return null;
	}

	return (
		<>
			<StoreNoticesContainer
				context={ noticeContexts.ORDER_INFORMATION }
			/>
			<Form
				id="order"
				addressType="order"
				onChange={ onChangeForm }
				values={ additionalFieldValues }
				fields={ ORDER_FORM_KEYS }
			/>
		</>
	);
};

export default Block;
