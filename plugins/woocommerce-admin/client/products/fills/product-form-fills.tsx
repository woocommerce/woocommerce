/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_FORM_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Fields } from './product-form-field-fills';
import { Sections } from './product-form-section-fills';

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

registerPlugin( 'wc-admin-product-editor-form-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <Form />;
	},
} );
