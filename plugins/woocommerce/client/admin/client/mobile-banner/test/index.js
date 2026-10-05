import { beforeEach, describe, expect, it, vi } from 'vitest';
vi.mock( '../../lib/platform', async () => {
	const mock = {
		...( await vi.importActual( '../../lib/platform' ) ),
		platform: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/tracks', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/tracks' ) ),
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../constants', async () => {
	const mock = {
		...( await vi.importActual( '../constants' ) ),
		PLAY_STORE_LINK:
			'https://play.google.com/store/apps/details?id=com.woocommerce.android',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

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
		const dismissHandler = vi.fn();
		const { getByTestId } = render(
			<Banner onInstall={ () => {} } onDismiss={ dismissHandler } />
		);
		fireEvent.click( getByTestId( 'dismiss-btn' ) );
		expect( dismissHandler ).toHaveBeenCalled();
	} );
	it( 'calls the onInstall handler when install is clicked', () => {
		const installHandler = vi.fn();
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
