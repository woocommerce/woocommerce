/**
 * External dependencies
 */
import React from 'react';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Stepper, { STEPS } from '../stepper';

describe( 'Stepper', () => {
	it( 'renders the four steps in an ordered list with the active one marked aria-current', () => {
		render( <Stepper currentStep="mapping" /> );

		const list = screen.getByRole( 'list', { name: /import progress/i } );
		const items = list.querySelectorAll( 'li' );
		expect( items ).toHaveLength( STEPS.length );

		const current = list.querySelector( '[aria-current="step"]' );
		expect( current ).not.toBeNull();
		expect( current?.textContent ).toContain( 'Mapping' );
	} );

	it( 'marks earlier steps as completed and later as upcoming', () => {
		render( <Stepper currentStep="import" /> );

		const completed =
			document.querySelectorAll( '.is-completed' );
		expect( completed.length ).toBe( 2 ); // upload + mapping

		const upcoming = document.querySelectorAll( '.is-upcoming' );
		expect( upcoming.length ).toBe( 1 ); // done
	} );
} );
