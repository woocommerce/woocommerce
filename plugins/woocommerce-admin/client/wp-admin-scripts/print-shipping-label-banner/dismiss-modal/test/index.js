/**
 * External dependencies
 */
import { shallow } from 'enzyme';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DismissModal } from '../index.js';

describe( 'Option Save events in DismissModal', () => {
	const spyUpdateOptions = jest.fn();

	let dismissModalWrapper;

	beforeEach( () => {
		document.body.innerHTML =
			document.body.innerHTML +
			'<div id="woocommerce-admin-print-label">';
		dismissModalWrapper = shallow(
			<DismissModal
				visible={ true }
				onClose={ jest.fn() }
				onCloseAll={ jest.fn() }
				trackElementClicked={ jest.fn() }
				updateOptions={ spyUpdateOptions }
			/>
		);
	} );

	test( 'Should save permanent dismissal', () => {
		const permanentDismissTimestamp = -1;
		const actionButtons = dismissModalWrapper.find( Button );
		expect( actionButtons.length ).toBe( 2 );
		const permanenttDismissButton = actionButtons.last();
		permanenttDismissButton.simulate( 'click' );
		expect( spyUpdateOptions ).toHaveBeenCalledWith( {
			woocommerce_shipping_dismissed_timestamp: permanentDismissTimestamp,
		} );
	} );

	test( 'Should save temporary dismissal', () => {
		// Mock Date.now() so a known timestamp will be saved.
		const mockDate = 123456;
		const realDateNow = Date.now.bind( global.Date );
		global.Date.now = jest.fn( () => mockDate );

		const actionButtons = dismissModalWrapper.find( Button );
		expect( actionButtons.length ).toBe( 2 );
		const remindMeLaterButton = actionButtons.first();
		remindMeLaterButton.simulate( 'click' );
		expect( spyUpdateOptions ).toHaveBeenCalledWith( {
			woocommerce_shipping_dismissed_timestamp: mockDate,
		} );

		// Restore Date.now().
		global.Date.now = realDateNow;
	} );
} );

describe( 'Tracking events in DismissModal', () => {
	const trackElementClicked = jest.fn();

	let dismissModalWrapper;

	beforeEach( () => {
		dismissModalWrapper = shallow(
			<DismissModal
				visible={ true }
				onClose={ jest.fn() }
				onCloseAll={ jest.fn() }
				trackElementClicked={ trackElementClicked }
				updateOptions={ jest.fn() }
			/>
		);
	} );

	it( 'should record an event when user clicks "I don\'t need this"', () => {
		const actionButtons = dismissModalWrapper.find( Button );
		expect( actionButtons.length ).toBe( 2 );
		const iDoNotNeedThisButton = actionButtons.last();
		iDoNotNeedThisButton.simulate( 'click' );
		expect( trackElementClicked ).toHaveBeenCalledWith(
			'shipping_banner_dismiss_modal_close_forever'
		);
	} );

	it( 'should record an event when user clicks "Remind me later"', () => {
		const actionButtons = dismissModalWrapper.find( Button );
		expect( actionButtons.length ).toBe( 2 );
		const remindMeLaterButton = actionButtons.first();
		remindMeLaterButton.simulate( 'click' );
		expect( trackElementClicked ).toHaveBeenCalledWith(
			'shipping_banner_dismiss_modal_remind_me_later'
		);
	} );
} );

describe( 'Dismissing modal', () => {
	let dismissModalWrapper;

	beforeEach( () => {
		document.body.innerHTML =
			document.body.innerHTML +
			'<div id="woocommerce-admin-print-label">';
		dismissModalWrapper = shallow(
			<DismissModal
				visible={ true }
				onClose={ jest.fn() }
				onCloseAll={ jest.fn() }
				trackElementClicked={ jest.fn() }
				updateOptions={ jest.fn() }
			/>
		);
	} );

	test( 'Should hide the banner by clicking permanent dismissal', () => {
		const actionButtons = dismissModalWrapper.find( Button );
		expect( actionButtons.length ).toBe( 2 );
		const permanenttDismissButton = actionButtons.last();
		permanenttDismissButton.simulate( 'click' );

		const bannerStyle = document.getElementById(
			'woocommerce-admin-print-label'
		).style;
		expect( bannerStyle.display ).toBe( 'none' );
	} );

	test( 'Should hide the banner by clicking  temporary dismissal', () => {
		const actionButtons = dismissModalWrapper.find( Button );
		expect( actionButtons.length ).toBe( 2 );
		const remindMeLaterButton = actionButtons.first();
		remindMeLaterButton.simulate( 'click' );

		const bannerStyle = document.getElementById(
			'woocommerce-admin-print-label'
		).style;
		expect( bannerStyle.display ).toBe( 'none' );
	} );
} );
