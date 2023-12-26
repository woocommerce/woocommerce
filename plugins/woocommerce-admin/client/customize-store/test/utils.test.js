/**
 * Internal dependencies
 */
import { setLogoWidth } from '../utils';

describe( 'setLogoWidth', () => {
	it( 'should replace the width value in the JSON object with 60', () => {
		const input = `<!-- wp:site-logo {"shouldSyncIcon":false} /-->`;
		const expectedOutput = `<!-- wp:site-logo {"shouldSyncIcon":false,"width":60} /-->`;
		expect( setLogoWidth( input ) ).toEqual( expectedOutput );
	} );

	it( 'should add a width value of 60 to the JSON object if it does not exist', () => {
		const input = `<!-- wp:site-logo /-->`;
		const expectedOutput = `<!-- wp:site-logo {"width":60} /-->`;
		expect( setLogoWidth( input ) ).toEqual( expectedOutput );
	} );

	it( 'should replace the width value in the JSON object for multiple instances', () => {
		const input = `
      <!-- wp:site-logo {"width":120} /-->
      <!-- wp:site-logo /-->
    `;
		const expectedOutput = `
      <!-- wp:site-logo {"width":60} /-->
      <!-- wp:site-logo {"width":60} /-->
    `;
		expect( setLogoWidth( input ) ).toEqual( expectedOutput );
	} );

	it( 'should handle other properties in the JSON object', () => {
		const input = `<!-- wp:site-logo {"width":120,"height":80} /-->`;
		const expectedOutput = `<!-- wp:site-logo {"width":60,"height":80} /-->`;
		expect( setLogoWidth( input ) ).toEqual( expectedOutput );
	} );

	it( 'should not modify other comments in the input', () => {
		const input = `
      <!-- wp:site-logo {"width":120} /-->
      <!-- Some other comment -->
    `;
		const expectedOutput = `
      <!-- wp:site-logo {"width":60} /-->
      <!-- Some other comment -->
    `;
		expect( setLogoWidth( input ) ).toEqual( expectedOutput );
	} );

	it( 'should use the provided width if specified', () => {
		const input = `<!-- wp:site-logo {"width":120} /-->`;
		const customWidth = 80;
		const expectedOutput = `<!-- wp:site-logo {"width":80} /-->`;
		expect( setLogoWidth( input, customWidth ) ).toEqual( expectedOutput );
	} );
} );
