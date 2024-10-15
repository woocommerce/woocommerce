/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Navigation } from '../navigation';

describe( 'Navigation', () => {
	it( 'should render skip button when onSkip is provided', () => {
		const { queryByText } = render( <Navigation onSkip={ () => {} } /> );
		expect( queryByText( 'Skip this step' ) ).toBeInTheDocument();
	} );

	it( 'should redner provided skip button text', () => {
		const { queryByText } = render(
			<Navigation onSkip={ () => {} } skipText="this is test" />
		);
		expect( queryByText( 'this is test' ) ).toBeInTheDocument();
	} );

	it( 'should not display woo logo with showLogo is set to false', () => {
		const { container } = render( <Navigation showLogo={ false } /> );
		expect( container.querySelector( '.woologo' ) ).toBeNull();
	} );

	it( 'should add additional classes to the container', () => {
		const { container } = render(
			<Navigation classNames={ { test: true } } />
		);
		expect( container.querySelector( '.test' ) ).toBeInTheDocument();
	} );
} );
