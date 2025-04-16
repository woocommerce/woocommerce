/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { noticeContexts, useEditorContext } from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { ORDER_FORM_KEYS } from '@woocommerce/block-settings';
import { Form } from '@woocommerce/base-components/cart-checkout';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import type { FunctionComponent } from 'react';
import type { OrderFormValues } from '@woocommerce/settings';

const Block: FunctionComponent = () => {
	const { additionalFields } = useSelect( ( select ) => {
		const store = select( checkoutStore );
		return {
			additionalFields: store.getAdditionalFields(),
		};
	}, [] );
	const { isEditor } = useEditorContext();
	const { setAdditionalFields } = useDispatch( checkoutStore );

	const onChangeForm = ( additionalValues: OrderFormValues ) => {
		setAdditionalFields( additionalValues );
	};

	const additionalFieldValues = {
		...additionalFields,
	};

	const WrapperComponent = isEditor ? Noninteractive : Fragment;

	return (
		<>
			<StoreNoticesContainer
				context={ noticeContexts.ORDER_INFORMATION }
			/>
			<WrapperComponent>
				<Form
					id="order"
					addressType="order"
					onChange={ onChangeForm }
					fields={ ORDER_FORM_KEYS }
					values={ additionalFieldValues }
				/>
			</WrapperComponent>
		</>
	);
};

export default Block;
