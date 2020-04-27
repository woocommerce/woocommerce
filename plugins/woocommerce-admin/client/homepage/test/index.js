import { render } from '@testing-library/react';
import Homepage from '../index';

describe( 'homepage', () => {
	it( 'should render', () => {
		const { container } = render( <Homepage /> );
		expect( container ).toMatchInlineSnapshot( `
		<div>
		  <div>
		    Hello World
		  </div>
		</div>
	` );
	} );
} );
