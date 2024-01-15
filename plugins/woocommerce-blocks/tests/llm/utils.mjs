import { request } from './client.mjs';
import { parsePattern } from './patterns.utils.mjs';

/**
 * Generates images based on a prompt using the WooCommerce private AI endpoint.
 *
 * @param {string} prompt - The prompt for generating images.
 * @return {Promise<Array<Record<string, string>>>} A promise that resolves to an array of generated images.
 */
const generateImages = async ( prompt ) => {
	const endpoint = '/wp-json/wc/private/ai/images';
	const response = await request( endpoint, 'POST', {
		business_description: prompt,
	} );

	const { images } = await response.json();
	return images;
};

/**
 * Generates products based on a prompt and an array of images using the WooCommerce private AI endpoint.
 *
 * @param {string}                        prompt - The prompt for generating products.
 * @param {Array<Record<string, string>>} images - An array of images to associate with the products.
 * @return {Promise<any>} A promise that resolves to the response from the server.
 */
const generateProducts = async ( prompt, images ) => {
	const endpoint = '/wp-json/wc/private/ai/products';
	const response = await request( endpoint, 'POST', {
		business_description: prompt,
		images,
	} );

	return response.json();
};

/**
 * Generates patterns based on a prompt and an array of images using the WooCommerce private AI endpoint.
 *
 * @param {string}                        prompt - The prompt for generating patterns.
 * @param {Array<Record<string, string>>} images - An array of images to associate with the patterns.
 * @return {Promise<any>} A promise that resolves to the response from the server.
 */
const generatePatterns = async ( prompt, images ) => {
	const endpoint = '/wp-json/wc/private/ai/patterns';
	const response = await request( endpoint, 'POST', {
		business_description: prompt,
		images,
	} );

	return response.json();
};

const updatePrompt = async ( prompt ) => {
	const endpoint = '/wp-json/wc-admin/options';
	await request( endpoint, 'POST', {
		woo_ai_describe_store_description: prompt,
		isWCEndpoint: true,
	} );
};

const deleteProducts = async () => {
	const endpoint = '/wp-json/wc/v3/products';
	const productsResponse = await request( endpoint, 'GET' );
	const products = await productsResponse.json();
	const productIds = products.map( ( product ) => product.id );

	for ( const productId of productIds ) {
		await request( `${ endpoint }/${ productId }`, 'DELETE' );
	}
};

const getPatterns = async () => {
	const CYSPatternsSlugs = [
		// Body
		'woocommerce-blocks/hero-product-split',
		'woocommerce-blocks/product-collection-5-columns',
		'woocommerce-blocks/hero-product-3-split',
		'woocommerce-blocks/product-collection-3-columns',
		'woocommerce-blocks/testimonials-3-columns',
		'woocommerce-blocks/featured-category-triple',
		'woocommerce-blocks/social-follow-us-in-social-media',
		// Footer
		'woocommerce-blocks/footer-with-3-menus',
	];
	const endpoint = '/wp-json/wp/v2/block-patterns/patterns';
	const response = await request( endpoint, 'GET', null, false );
	const patterns = await response.json();
	const CYSPatterns = patterns.filter( ( { name } ) =>
		CYSPatternsSlugs.includes( name )
	);

	return CYSPatterns.map( ( pattern ) => {
		return parsePattern( pattern.content );
	} );
};

/**
 * Sets up the store by generating images, products, and patterns based on a prompt.
 *
 * @param {string} prompt - The prompt for setting up the store.
 * @return {Promise<void>} A promise that resolves when the setup is complete.
 */
export const setupStore = async ( prompt ) => {
	await deleteProducts();
	await updatePrompt( prompt );
	const images = await generateImages( prompt );
	const productsResponse = await generateProducts( prompt, images );
	await generatePatterns( prompt, images );
	const patterns = await getPatterns();

	return {
		products: productsResponse.product_content,
		patterns,
	};
};
