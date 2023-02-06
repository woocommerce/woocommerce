// @ts-nocheck
/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
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
	const { isLoading } = useSelect((select: WCDataSelector) => {
		const { hasFinishedResolution: hasProductFormFinishedResolution } =
			select(EXPERIMENTAL_PRODUCT_FORM_STORE_NAME);
		return {
			isLoading: !hasProductFormFinishedResolution('getProductForm'),
		};
	});
	useEffect(() => {
		recordEvent('view_new_product_management_experience');
	}, []);

	console.log("app");
	return (
		<div className="woocommerce-add-product">
			{isLoading ? (
				<div className="woocommerce-edit-product__spinner">
					<Spinner />
				</div>
			) : (
				<>
					<ProductForm />
					<ProductTourContainer />
				</>
			)}
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

import { Button } from "@wordpress/components";
// This function takes a description and generates two product descriptions
// using the OpenAI API
async function generateDescriptions( description, setResults ) {
	const model = 'text-davinci-003';
	const prompt = generatePrompt( description );
	const api_key = '';

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
	setResults( results);
}

export const MakeAFill = () => {
	const sc = useSlotContext();
	const { setValue, values } = useFormContext();
	console.log("maf");
	const fills = sc.getFillHelpers().getFills();
	console.log( 'fills', fills );
	console.log( 'values', values );
	console.log( values );

	// TODO:
	// 1. Get the values from the form (values.description?)
	// 2. Add a button to the render function (call API)
	// 3. When button is clicked, call API with values from the form
	// 4. When API returns, update the form with the new values

	const [descriptionValue, setDescriptionValue] = useState("");

	// render results with a button next to each one 
	const [results, setResults] = useState([]); // expect an array of two strings	


	useEffect(() => {
		setDescriptionValue(values.description?.slice(126, -27));
	}, [values])

	useEffect(() => {
		console.log("descriptionValue", descriptionValue);
	}, [descriptionValue])

	const handleMagicButton = useCallback(() => {
		console.log("clicked w/", descriptionValue);
		generateDescriptions(descriptionValue, setResults);
		// setResults(["result 1", "result 2"]);
	}, [descriptionValue]);

	const handleResultButton = useCallback((index) => {
		let richTextValue = `<!-- wp:paragraph {\"placeholder\":\"Describe this product. What makes it unique? What are its most important features?\"} -->\n<p>${results[index]}</p>\n<!-- /wp:paragraph -->`
		console.log("clicked result", index);
		setValue('description', richTextValue);
		// setValue('name', results[index]);
	}, [results]);

	return (
		<WooProductFieldItem id="make-a-thingo" sections={ [{ name: "tab/general/details" }] } order={2} pluginId="test-plugin" >
			{() => {
				return (
					<>
						<Button variant="primary" onClick={handleMagicButton}>🪄</Button>
						<div>
							{results.length > 0 && (
								<>
									<div>{results[0]}</div><Button variant="primary" onClick={() => { handleResultButton(0) }} >Add</Button>
									<div>{results[1]}</div><Button variant="primary" onClick={() => { handleResultButton(1) }}>Add</Button>
								</>
							)}
						</div>
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
