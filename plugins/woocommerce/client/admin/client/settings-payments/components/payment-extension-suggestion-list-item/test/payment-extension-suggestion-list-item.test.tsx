/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { render, fireEvent } from '@testing-library/react';
import {
	PaymentExtensionSuggestionProvider,
	PluginData,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PaymentExtensionSuggestionListItem } from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'PaymentExtensionSuggestionListItem', () => {
	it( 'should record settings_payments_provider_enable_click event on click of the Enable button', () => {
		const { getByRole } = render(
			<PaymentExtensionSuggestionListItem
				suggestion={
					{
						id: 'test-gateway',
						title: 'Test Gateway',
						description: 'Test Gateway Description',
						icon: 'test-gateway-icon',
						image: 'test-gateway-image',
						short_description: 'Test Gateway Short Description',
						tags: [],
						plugin: {
							slug: 'test-gateway-plugin',
							file: 'test-gateway-file',
							status: 'installed',
						} as PluginData,
						_order: 1,
						_type: 'test-type',
					} as unknown as PaymentExtensionSuggestionProvider
				}
				installingPlugin={ null }
				setUpPlugin={ () => {} }
				pluginInstalled={ true }
				acceptIncentive={ () => {} }
				shouldHighlightIncentive={ false }
			/>
		);

		fireEvent.click( getByRole( 'button', { name: 'Enable' } ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'settings_payments_provider_enable_click',
			{
				business_country: expect.any( String ),
				provider_id: 'test-gateway',
				suggestion_id: 'test-gateway',
			}
		);
	} );
} );
