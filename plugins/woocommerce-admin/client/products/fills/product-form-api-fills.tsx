/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { useSelect, resolveSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_FORM_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { registerCoreProductFields } from '@woocommerce/components';

registerCoreProductFields();

/**
 * Internal dependencies
 */
import { Fields } from './product-form-field-fills';
import { Sections } from './product-form-api-section-fills';

const Form = () => {
	const { formData } = useSelect( ( select: WCDataSelector ) => {
		return {
			formData: select(
				EXPERIMENTAL_PRODUCT_FORM_STORE_NAME
			).getProductForm(),
		};
	} );

	return (
		<>
			{ formData && (
				<>
					<Sections sections={ formData.sections } />
					<Fields fields={ formData.fields } />
				</>
			) }
		</>
	);
};

/**
 * Preloading product form data, as product pages are waiting on this to be resolved.
 * The above Form component won't get rendered until the getProductForm is resolved.
 */
resolveSelect( EXPERIMENTAL_PRODUCT_FORM_STORE_NAME ).getProductForm();
registerPlugin( 'wc-admin-product-editor-form-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <Form />;
	},
} );
