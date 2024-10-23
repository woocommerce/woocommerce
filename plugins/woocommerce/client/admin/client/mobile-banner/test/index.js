jest.mock( '../../lib/platform', () => ( {
	...jest.requireActual( '../../lib/platform' ),
	platform: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	...jest.requireActual( '@woocommerce/tracks' ),
	recordEvent: jest.fn(),
} ) );

jest.mock( '../constants', () => ( {
	...jest.requireActual( '../constants' ),
	PLAY_STORE_LINK: '',
} ) );

/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Banner } from '../banner';
import { platform } from '../../lib/platform';
import { TRACKING_EVENT_NAME } from '../constants';

describe( 'Banner', () => {
	beforeEach( () => {
		platform.mockReturnValue( 'android' );
	} );

	it( 'closes if the user dismisses it', () => {
		const { container, getByTestId } = render(
			<Banner onInstall={ () => {} } onDismiss={ () => {} } />
		);

		fireEvent.click( getByTestId( 'dismiss-btn' ) );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'closes if the user clicks install', () => {
		const { queryByRole, container } = render(
			<Banner onInstall={ () => {} } onDismiss={ () => {} } />
		);

		fireEvent.click( queryByRole( 'link' ) );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'records a tracking event for install', () => {
		const { queryByRole } = render(
			<Banner onInstall={ () => {} } onDismiss={ () => {} } />
		);

		fireEvent.click( queryByRole( 'link' ) );
		expect( recordEvent ).toHaveBeenCalledWith( TRACKING_EVENT_NAME, {
			action: 'install',
		} );
	} );

	it( 'records a dismiss event for dismiss', () => {
		const { container, getByTestId } = render(
			<Banner onInstall={ () => {} } onDismiss={ () => {} } />
		);

		fireEvent.click( getByTestId( 'dismiss-btn' ) );
		expect( container ).toBeEmptyDOMElement();

		expect( recordEvent ).toHaveBeenCalledWith( TRACKING_EVENT_NAME, {
			action: 'dismiss',
		} );
	} );

	it( 'calls the onDismiss handler when dismiss is clicked', () => {
		const dismissHandler = jest.fn();
		const { getByTestId } = render(
			<Banner onInstall={ () => {} } onDismiss={ dismissHandler } />
		);

		fireEvent.click( getByTestId( 'dismiss-btn' ) );
		expect( dismissHandler ).toHaveBeenCalled();
	} );

	it( 'calls the onInstall handler when install is clicked', () => {
		const installHandler = jest.fn();
		const { queryByRole } = render(
			<Banner onInstall={ installHandler } onDismiss={ () => {} } />
		);

		fireEvent.click( queryByRole( 'link' ) );
		expect( installHandler ).toHaveBeenCalled();
	} );

	it( 'does not display unless the platform is android', () => {
		platform.mockReturnValue( 'ios' );

		const { container } = render(
			<Banner onInstall={ () => {} } onDismiss={ () => {} } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
