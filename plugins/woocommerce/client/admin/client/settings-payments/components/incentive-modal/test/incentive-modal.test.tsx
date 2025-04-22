/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';
import {
	PaymentIncentive,
	PaymentProvider,
	PaymentProviderType,
	PluginData,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { IncentiveModal } from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const testIncentive: PaymentIncentive = {
	id: 'test-incentive',
	description: 'Test Incentive',
	promo_id: 'test-promo-id',
	title: 'Test Title',
	short_description: 'Test Short Description',
	cta_label: 'Test CTA Label',
	tc_url: 'https://example.com',
	badge: 'test-badge',
	_dismissals: [],
	_links: {
		dismiss: {
			href: 'https://example.com',
		},
	},
};

const testProvider: PaymentProvider = {
	id: 'test-provider',
	_order: 1,
	_type: PaymentProviderType.Gateway, // or any valid PaymentProviderType
	title: 'Test Title',
	description: 'Test Description',
	icon: 'test-icon',
	plugin: {
		id: 'test-plugin',
		name: 'Test Plugin',
		description: 'Test Plugin Description',
		pluginUrl: 'https://example.com',
		slug: 'test-plugin-slug',
		file: 'test-plugin-file',
		status: 'active',
	} as PluginData,
};

describe( 'IncentiveModal', () => {
	it( 'should record settings_payments_incentive_show event when the incentive is shown', () => {
		render(
			<IncentiveModal
				incentive={ testIncentive }
				provider={ testProvider }
				onboardingUrl="https://example.com"
				onAccept={ jest.fn() }
				onDismiss={ jest.fn() }
				setupPlugin={ jest.fn() }
			/>
		);

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_incentive_show',
			{
				display_context: 'wc_settings_payments__modal',
				incentive_id: 'test-promo-id',
				provider_id: 'test-provider',
			}
		);
	} );

	it( 'should record settings_payments_incentive_accept event when the accept button is clicked', () => {
		const onAccept = jest.fn();
		const { getByRole } = render(
			<IncentiveModal
				incentive={ testIncentive }
				provider={ testProvider }
				onboardingUrl="https://example.com"
				onAccept={ onAccept }
				onDismiss={ jest.fn() }
				setupPlugin={ jest.fn() }
			/>
		);

		fireEvent.click( getByRole( 'button', { name: 'Test CTA Label' } ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_incentive_accept',
			{
				display_context: 'wc_settings_payments__modal',
				incentive_id: 'test-promo-id',
				provider_id: 'test-provider',
			}
		);
	} );

	it( 'should record settings_payments_incentive_dismiss event when the close button is clicked', () => {
		const onAccept = jest.fn();
		const { getByRole } = render(
			<IncentiveModal
				incentive={ testIncentive }
				provider={ testProvider }
				onboardingUrl="https://example.com"
				onAccept={ onAccept }
				onDismiss={ jest.fn() }
				setupPlugin={ jest.fn() }
			/>
		);

		fireEvent.click( getByRole( 'button', { name: 'Close' } ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_incentive_dismiss',
			{
				display_context: 'wc_settings_payments__modal',
				incentive_id: 'test-promo-id',
				provider_id: 'test-provider',
			}
		);
	} );
} );
