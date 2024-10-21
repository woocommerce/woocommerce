/**
 * Internal dependencies
 */
import { homepageTemplateValidator } from '..';

describe( 'homepageTemplateValidator', () => {
	it( 'should validate when template is part of the allowed list', () => {
		const validTemplate = { homepage_template: 'template1' };
		expect( () =>
			homepageTemplateValidator.parse( validTemplate )
		).not.toThrow();
	} );

	it( 'should not validate when template is not part of the allowed list', () => {
		const invalidTemplate = {
			homepage_template: 'nonexistingtemplate',
		};
		expect( () => homepageTemplateValidator.parse( invalidTemplate ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Template not part of allowed list\\",
		    \\"path\\": [
		      \\"homepage_template\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should not validate when template is not a string', () => {
		const invalidType = { homepage_template: 123 };
		expect( () => homepageTemplateValidator.parse( invalidType ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"invalid_type\\",
		    \\"expected\\": \\"string\\",
		    \\"received\\": \\"number\\",
		    \\"path\\": [
		      \\"homepage_template\\"
		    ],
		    \\"message\\": \\"Expected string, received number\\"
		  }
		]"
	` );
	} );
} );
