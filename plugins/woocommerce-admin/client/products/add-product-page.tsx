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

import {
	useFormContext,
	useSlotContext,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	TextControl,
} from '@woocommerce/components';
import { registerPlugin } from '@wordpress/plugins';

const generatePrompt = ( description ) => {
	return `As an expert online marketer, paraphrase the following product description into 2 different styles.
	Style 1: Extremely exciting and engaging
	Style 2: Extremely sarcastic and funny
	Description: ${ description }`;
};

// This function takes a description and generates two product descriptions
// using the OpenAI API
async function generateDescriptions( description ) {
	const model = 'text-davinci-003';
	const prompt = generatePrompt( description );
	const api_key = '<API KEY>';

	const response = await fetch( `https://api.openai.com/v1/completions`, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			Authorization: `Bearer ${ api_key }`,
		},
		body: JSON.stringify( {
			model,
			prompt,
			max_tokens: 100,
			temperature: 0.8,
		} ),
	} );

	const responseData = await response.json();

	if ( ! responseData || ! responseData.choices ) {
		console.error( 'Error generating text' );
		return;
	}

	const reply = responseData.choices[ 0 ].text;
	const style1start = reply.indexOf( 'Style 1: ' );
	const style1end = reply.indexOf( 'Style 2: ' );
	const results = [
		reply.slice( style1start, style1end ).replace( 'Style 1: ', '' ).trim(),
		reply.slice( style1end ).replace( 'Style 2: ', '' ).trim(),
	];
	return results;
}

export const MakeAFill = () => {
	const sc = useSlotContext();
	const { setValues, values } = useFormContext();
	console.log( 'maf' );
	const fills = sc.getFillHelpers().getFills();
	console.log( 'fills', fills );
	console.log( 'values', values );
	console.log( values );

	// return null;
	return (
		<WooProductFieldItem
			id="make-a-thingo"
			sections={ [ { name: 'tab/general/details' } ] }
			order={ 2 }
			pluginId="test-plugin"
		>
			{ () => {
				return (
					<>
						<TextControl
							label="Name"
							name={ `product-mvp-name` }
							placeholder="e.g. 12 oz Coffee Mug"
							value="Test Name"
							onChange={ () => console.debug( 'Changed!' ) }
						/>
					</>
				);
			} }
		</WooProductFieldItem>
	);
};

registerPlugin(
	'wc-admin-product-editor-api-form-fills-experimental-meetup-thingos',
	{
		// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
		scope: 'woocommerce-product-editor',
		render: () => {
			return <MakeAFill />;
		},
	}
);

console.log( 'loaded emt' );
