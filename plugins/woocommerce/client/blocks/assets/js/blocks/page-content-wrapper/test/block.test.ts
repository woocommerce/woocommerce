/**
 * Internal dependencies
 */
import metadata from '../block.json';

describe( 'woocommerce/page-content-wrapper block metadata', () => {
	it( 'declares the `postId` attribute that backs the `providesContext` mapping', () => {
		// `providesContext.postId` maps to an attribute of the same name, so
		// the attribute MUST be declared, otherwise inner blocks such as
		// `core/post-title` / `core/post-content` receive an undefined
		// context value and can throw in the editor.
		expect( metadata.providesContext.postId ).toBe( 'postId' );
		expect( metadata.attributes.postId ).toBeDefined();
		expect( metadata.attributes.postId.type ).toBe( 'number' );
	} );

	it( 'declares the `postType` attribute that backs the `providesContext` mapping', () => {
		expect( metadata.providesContext.postType ).toBe( 'postType' );
		expect( metadata.attributes.postType ).toBeDefined();
		expect( metadata.attributes.postType.type ).toBe( 'string' );
	} );

	it( 'still declares the original `page` attribute used by variations', () => {
		expect( metadata.attributes.page ).toBeDefined();
		expect( metadata.attributes.page.type ).toBe( 'string' );
		expect( metadata.attributes.page.default ).toBe( '' );
	} );
} );
