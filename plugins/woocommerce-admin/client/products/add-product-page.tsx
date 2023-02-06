// @ts-nocheck
/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_FORM_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductForm } from './product-form';
import { ProductTourContainer } from './tour';
import './product-page.scss';
import './fills';

const AddProductPage: React.FC = () => {
	const { isLoading } = useSelect( ( select: WCDataSelector ) => {
		const { hasFinishedResolution: hasProductFormFinishedResolution } =
			select( EXPERIMENTAL_PRODUCT_FORM_STORE_NAME );
		return {
			isLoading: ! hasProductFormFinishedResolution( 'getProductForm' ),
		};
	} );
	useEffect( () => {
		recordEvent( 'view_new_product_management_experience' );
	}, [] );

	return (
		<div className="woocommerce-add-product">
			{ isLoading ? (
				<div className="woocommerce-edit-product__spinner">
					<Spinner />
				</div>
			) : (
				<>
					<ProductForm />
					<ProductTourContainer />
				</>
			) }
		</div>
	);
};

export default AddProductPage;



import { useFormContext, useSlotContext, __experimentalWooProductFieldItem as WooProductFieldItem, TextControl } from "@woocommerce/components";
import { registerPlugin } from "@wordpress/plugins";

export const MakeAFill = () => {
	const sc = useSlotContext();
	const { setValues, values } = useFormContext();
	console.log("maf");
	const fills = sc.getFillHelpers().getFills();
	console.log("fills", fills);
	console.log("values", values);

	// return null;
	return (
		<WooProductFieldItem id="make-a-thingo" sections={ [{ name: "tab/general/details" }] } order={2} pluginId="test-plugin" >
			{() => {
				return (
					<TextControl
						label="Name"
						name={`product-mvp-name`}
						placeholder="e.g. 12 oz Coffee Mug"
						value="Test Name"
						onChange={() => console.debug('Changed!')}
					/>
				);
			}}
		</WooProductFieldItem>
	);
}

registerPlugin('wc-admin-product-editor-api-form-fills-experimental-meetup-thingos', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <MakeAFill />;
	},
});

console.log("loaded emt")