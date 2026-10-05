import { describe, expect, it, test, vi } from 'vitest';

/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { render, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { DismissModal } from '../index.js';
describe( 'Option Save events in DismissModal', () => {
	const spyUpdateOptions = vi.fn();
	test( 'Should save permanent dismissal', async () => {
		const { getByRole } = render(
			<Fragment>
				<div id="woocommerce-admin-print-label" />
				<DismissModal
					visible={ true }
					onClose={ vi.fn() }
					onCloseAll={ vi.fn() }
					trackElementClicked={ vi.fn() }
					updateOptions={ spyUpdateOptions }
				/>
			</Fragment>
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: "I don't need this",
				} )
			);
		} );
		await waitFor( () =>
			expect( spyUpdateOptions ).toHaveBeenCalledWith( {
				woocommerce_shipping_dismissed_timestamp: -1,
			} )
		);
	} );
	test( 'Should save temporary dismissal', async () => {
		// Mock Date.now() so a known timestamp will be saved.
		const mockDate = 123456;
		const realDateNow = Date.now.bind( global.Date );
		global.Date.now = vi.fn( () => mockDate );
		const { getByRole } = render(
			<Fragment>
				<div id="woocommerce-admin-print-label" />
				<DismissModal
					visible={ true }
					onClose={ vi.fn() }
					onCloseAll={ vi.fn() }
					trackElementClicked={ vi.fn() }
					updateOptions={ spyUpdateOptions }
				/>
			</Fragment>
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'Remind me later',
				} )
			);
		} );
		await waitFor( () =>
			expect( spyUpdateOptions ).toHaveBeenCalledWith( {
				woocommerce_shipping_dismissed_timestamp: mockDate,
			} )
		);

		// Restore Date.now().
		global.Date.now = realDateNow;
	} );
} );
describe( 'Tracking events in DismissModal', () => {
	const trackElementClicked = vi.fn();
	it( 'should record an event when user clicks "I don\'t need this"', async () => {
		const { getByRole } = render(
			<Fragment>
				<div id="woocommerce-admin-print-label" />
				<DismissModal
					visible={ true }
					onClose={ vi.fn() }
					onCloseAll={ vi.fn() }
					trackElementClicked={ trackElementClicked }
					updateOptions={ vi.fn() }
				/>
			</Fragment>
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: "I don't need this",
				} )
			);
		} );
		await waitFor( () =>
			expect( trackElementClicked ).toHaveBeenCalledWith(
				'shipping_banner_dismiss_modal_close_forever'
			)
		);
	} );
	it( 'should record an event when user clicks "Remind me later"', async () => {
		const { getByRole } = render(
			<Fragment>
				<div id="woocommerce-admin-print-label" />
				<DismissModal
					visible={ true }
					onClose={ vi.fn() }
					onCloseAll={ vi.fn() }
					trackElementClicked={ trackElementClicked }
					updateOptions={ vi.fn() }
				/>
			</Fragment>
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'Remind me later',
				} )
			);
		} );
		await waitFor( () =>
			expect( trackElementClicked ).toHaveBeenCalledWith(
				'shipping_banner_dismiss_modal_remind_me_later'
			)
		);
	} );
} );
describe( 'Dismissing modal', () => {
	test( 'Should hide the banner by clicking permanent dismissal', async () => {
		const { getByRole, getByTestId } = render(
			<Fragment>
				<div
					id="woocommerce-admin-print-label"
					data-testid="print-label"
				/>
				<DismissModal
					visible={ true }
					onClose={ vi.fn() }
					onCloseAll={ vi.fn() }
					trackElementClicked={ vi.fn() }
					updateOptions={ vi.fn() }
				/>
			</Fragment>
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: "I don't need this",
				} )
			);
		} );
		await waitFor( () =>
			expect( getByTestId( 'print-label' ) ).not.toBeVisible()
		);
	} );
	test( 'Should hide the banner by clicking temporary dismissal', async () => {
		const { getByRole, getByTestId } = render(
			<Fragment>
				<div
					id="woocommerce-admin-print-label"
					data-testid="print-label"
				/>
				<DismissModal
					visible={ true }
					onClose={ vi.fn() }
					onCloseAll={ vi.fn() }
					trackElementClicked={ vi.fn() }
					updateOptions={ vi.fn() }
				/>
			</Fragment>
		);
		await act( async () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'Remind me later',
				} )
			);
		} );
		await waitFor( () =>
			expect( getByTestId( 'print-label' ) ).not.toBeVisible()
		);
	} );
} );
