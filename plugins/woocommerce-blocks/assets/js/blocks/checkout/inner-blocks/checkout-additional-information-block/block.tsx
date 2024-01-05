/**
 * External dependencies
 */
import { noticeContexts } from '@woocommerce/base-context';
import { __ } from '@wordpress/i18n';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { ADDITIONAL_FORM_KEYS } from '@woocommerce/block-settings';
import { Form } from '@woocommerce/base-components/cart-checkout';
import type { FunctionComponent } from 'react';
import NoticeBanner from '@woocommerce/base-components/notice-banner';

const Block: FunctionComponent = () => {
	const { additionalFields, isEditor } = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		const editorStore = select( 'core/editor' );
		return {
			additionalFields: store.getAdditionalFields(),
			isEditor: !! editorStore,
		};
	} );

	const { setAdditionalFields } = useDispatch( CHECKOUT_STORE_KEY );

	const onChangeForm = ( additionalValues ) => {
		setAdditionalFields( additionalValues );
	};

	const additionalFieldValues = {
		...additionalFields,
	};

	if ( ADDITIONAL_FORM_KEYS.length === 0 && ! isEditor ) {
		return null;
	}

	if ( ADDITIONAL_FORM_KEYS.length === 0 && isEditor ) {
		return (
			<NoticeBanner status="warning" isDismissible={ false }>
				{ __(
					'There are no custom fields registered. This block will not be visible on the front-end.',
					'woocommerce'
				) }
			</NoticeBanner>
		);
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
