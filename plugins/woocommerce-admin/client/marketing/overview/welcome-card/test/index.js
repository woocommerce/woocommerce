/**
 * External dependencies
 */
import { shallow } from 'enzyme';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { WelcomeCard } from '../index.js';

jest.mock( '@woocommerce/tracks' );
jest.mock( '@woocommerce/wc-admin-settings' );

describe( 'WelcomeCard hide button', () => {
	let welcomeCardWrapper;
	let mockUpdateOptions;

	beforeEach( () => {
		mockUpdateOptions = jest.fn();
		welcomeCardWrapper = shallow(
			<WelcomeCard
				isHidden={ false }
				updateOptions={ mockUpdateOptions }
			/>
		);
	} );

	it( 'should record an event when clicked', () => {
		const welcomeHideButton = welcomeCardWrapper.find( Button );
		expect( welcomeHideButton.length ).toBe( 1 );
		welcomeHideButton.simulate( 'click' );
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketing_intro_close',
			{}
		);
	} );

	it( 'should update option when clicked', () => {
		const welcomeHideButton = welcomeCardWrapper.find( Button );
		expect( welcomeHideButton.length ).toBe( 1 );
		welcomeHideButton.simulate( 'click' );
		expect( mockUpdateOptions ).toHaveBeenCalledTimes( 1 );
		expect( mockUpdateOptions ).toHaveBeenCalledWith( {
			woocommerce_marketing_overview_welcome_hidden: 'yes',
		} );
	} );
} );

describe( 'Component visibility can be toggled', () => {
	const mockUpdateOptions = jest.fn();

	it( 'WelcomeCard should be visible if isHidden is false', () => {
		const welcomeCardWrapper = shallow(
			<WelcomeCard
				isHidden={ false }
				updateOptions={ mockUpdateOptions }
			/>
		);
		const welcomeCardButton = welcomeCardWrapper.find( Button );
		expect( welcomeCardButton.length ).toBe( 1 );
		expect( mockUpdateOptions ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'WelcomeCard should be hidden if isHidden is true', () => {
		const welcomeCardWrapper = shallow(
			<WelcomeCard
				isHidden={ true }
				updateOptions={ mockUpdateOptions }
			/>
		);
		const welcomeCardButton = welcomeCardWrapper.find( Button );
		expect( welcomeCardButton.length ).toBe( 0 );
		expect( mockUpdateOptions ).toHaveBeenCalledTimes( 0 );
	} );
} );
