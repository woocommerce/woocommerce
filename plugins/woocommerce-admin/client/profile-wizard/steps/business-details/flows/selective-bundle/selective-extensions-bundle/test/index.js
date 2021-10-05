/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import userEvent from '@testing-library/user-event';
import { pluginNames } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { SelectiveExtensionsBundle } from '../';

jest.mock( '../../app-illustration', () => ( {
	AppIllustration: jest.fn().mockReturnValue( '[illustration]' ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn().mockImplementation( () => ( {
		updateOptions: jest.fn(),
		installAndActivatePlugins: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	pluginNames: {
		'woocommerce-payments': 'WooCommerce Payments',
		mailpoet: 'Mailpoet',
		random: 'Random',
		'google-listings-and-ads': 'Google Listings and Ads',
	},
} ) );

const freeExtensions = [
	{
		key: 'basics',
		title: 'Get the basics',
		plugins: [
			{
				key: 'woocommerce-payments',
				description: 'WC Pay Description',
				is_visible: true,
				is_installed: true,
			},
			{
				key: 'mailpoet',
				description: 'Mailpoet Description',
				manage_url: 'admin.php?page=mailpoet-newsletters',
				is_visible: true,
				is_installed: true,
			},
		],
	},
	{
		key: 'reach',
		title: 'Reach out to customers',
		plugins: [
			{
				key: 'random',
				name: 'Random',
				description: 'Random description',
				manage_url: 'admin.php?page=mailpoet-newsletters',
				is_visible: true,
				is_installed: true,
			},
		],
	},
	{
		key: 'grow',
		title: 'Grow your store',
		plugins: [
			{
				key: 'google-listings-and-ads',
				name: 'Google Ads & Marketing by Kliken',
				description: 'Google Description',
				manage_url: 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart',
				is_visible: true,
				is_installed: false,
			},
		],
	},
];

const profileItems = { product_types: [] };

describe( 'Selective extensions bundle', () => {
	it( 'should list installable free extensions in footer only basics', () => {
		useSelect.mockReturnValue( {
			freeExtensions,
			isResolving: false,
			profileItems,
		} );
		const { getByText, queryByText } = render(
			<SelectiveExtensionsBundle isInstallingActivating={ false } />
		);
		expect(
			getByText( new RegExp( pluginNames.mailpoet ) )
		).toBeInTheDocument();
		expect(
			getByText( new RegExp( pluginNames[ 'woocommerce-payments' ] ) )
		).toBeInTheDocument();
		expect(
			queryByText(
				new RegExp( pluginNames[ 'google-listings-and-ads' ] )
			)
		).not.toBeInTheDocument();
		expect(
			queryByText( new RegExp( pluginNames.random ) )
		).not.toBeInTheDocument();
	} );

	it( 'should list installable extensions when dropdown is clicked', () => {
		useSelect.mockReturnValue( {
			freeExtensions,
			isResolving: false,
			profileItems,
		} );
		const { getAllByRole, getByText, queryByText } = render(
			<SelectiveExtensionsBundle isInstallingActivating={ false } />
		);
		const collapseButton = getAllByRole( 'button' ).find(
			( item ) => item.textContent === ''
		);
		userEvent.click( collapseButton );
		expect( getByText( 'WC Pay Description' ) ).toBeInTheDocument();
		expect( getByText( 'Mailpoet Description' ) ).toBeInTheDocument();
		expect( queryByText( 'Google Description' ) ).not.toBeInTheDocument();
		expect( queryByText( 'Random Description' ) ).not.toBeInTheDocument();
	} );
} );
