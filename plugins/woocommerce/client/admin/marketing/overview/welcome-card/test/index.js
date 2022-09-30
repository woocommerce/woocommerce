/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WelcomeCard } from '../index.js';

jest.mock( '@woocommerce/tracks' );
jest.mock( '@woocommerce/settings' );

describe( 'WelcomeCard hide button', () => {
	it( 'should record an event when clicked', () => {
		const { getByRole } = render(
			<WelcomeCard isHidden={ false } updateOptions={ jest.fn() } />
		);

		userEvent.click( getByRole( 'button', { name: 'Hide' } ) );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_intro_close',
			{}
		);
	} );

	it( 'should update option when clicked', async () => {
		const mockUpdateOptions = jest.fn();
		const { getByRole } = render(
			<WelcomeCard
				isHidden={ false }
				updateOptions={ mockUpdateOptions }
			/>
		);

		userEvent.click( getByRole( 'button' ) );

		await waitFor( () =>
			expect( mockUpdateOptions ).toHaveBeenCalledTimes( 1 )
		);
		expect( mockUpdateOptions ).toHaveBeenCalledWith( {
			woocommerce_marketing_overview_welcome_hidden: 'yes',
		} );
	} );
} );

describe( 'Component visibility can be toggled', () => {
	it( 'WelcomeCard should be visible if isHidden is false', () => {
		const { getByRole } = render(
			<WelcomeCard isHidden={ false } updateOptions={ jest.fn() } />
		);

		expect( getByRole( 'button' ) ).toBeInTheDocument();
	} );

	it( 'WelcomeCard should be hidden if isHidden is true', () => {
		const { queryByRole } = render(
			<WelcomeCard isHidden={ true } updateOptions={ jest.fn() } />
		);

		expect( queryByRole( 'button' ) ).toBeNull();
	} );
} );
