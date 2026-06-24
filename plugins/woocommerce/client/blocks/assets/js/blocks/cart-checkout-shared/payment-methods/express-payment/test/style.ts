/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';

const stylePath = path.resolve( __dirname, '../style.scss' );
const style = fs.readFileSync( stylePath, 'utf8' );

describe( 'Express payment styles', () => {
	it( 'adds an overlaid focus cue using the checkout control colors', () => {
		expect( style ).toContain( 'position: relative;' );
		expect( style ).toContain( '&:focus-within::after,' );
		expect( style ).toContain(
			'&.wc-block-components-express-payment__event-button--focused::after {'
		);
		expect( style ).toContain( 'border: 2px solid $input-text-light;' );
		expect( style ).toContain( 'border-radius: inherit;' );
		expect( style ).toContain( 'content: "";' );
		expect( style ).toContain( 'inset: 0;' );
		expect( style ).toContain( 'pointer-events: none;' );
		expect( style ).toContain( 'position: absolute;' );
		expect( style ).toContain( 'z-index: 1;' );
		expect( style ).toContain(
			'.has-dark-controls &:focus-within::after,'
		);
		expect( style ).toContain(
			'.has-dark-controls &.wc-block-components-express-payment__event-button--focused::after {'
		);
		expect( style ).toContain( 'border-color: $input-text-dark;' );
	} );
} );
