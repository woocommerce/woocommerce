/**
 * Internal dependencies
 */
import { asLink } from '../components/shopping-checklist';

describe( 'asLink', () => {
	it( 'disables the button when the URL is rejected', () => {
		expect( asLink( 'javascript:alert(1)' ) ).toEqual( { disabled: true } );
		expect( asLink( ' JavaScript:alert(1)' ) ).toEqual( {
			disabled: true,
		} );
	} );

	it( 'keeps an http link as an anchor', () => {
		const props = asLink( 'https://example.com/policy' );
		expect( props ).toMatchObject( {
			nativeButton: false,
			role: 'link',
		} );
		if ( ! ( 'render' in props ) || ! props.render ) {
			throw new Error( 'expected a link' );
		}
		expect( props.render.props.href ).toBe( 'https://example.com/policy' );
	} );
} );
