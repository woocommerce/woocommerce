/**
 * External dependencies
 */
import fs from 'fs';
import path from 'path';

const stylePath = path.resolve( __dirname, '../style.scss' );
const style = fs.readFileSync( stylePath, 'utf8' );

describe( 'Express payment styles', () => {
	it( 'adds a visible focus cue using the checkout control colors', () => {
		expect( style ).toMatch(
			/> (div|li),\s*> (div|li) \{\s*[^}]*&:focus-within \{[^}]*outline: 2px solid \$input-text-light;[^}]*\.has-dark-controls & \{[^}]*outline-color: \$input-text-dark;/s
		);
	} );
} );
