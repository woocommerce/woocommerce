/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { pluginNames } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { SelectiveExtensionsBundle, ExtensionSection } from '../';

jest.mock( '../../app-illustration', () => ( {
	AppIllustration: jest.fn().mockReturnValue( '[illustration]' ),
} ) );

const freeExtensions = [
	{
		key: 'task-list/reach',
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
		key: 'obw/basics',
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
		key: 'obw/grow',
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

describe( 'Selective extensions bundle', () => {
	it( 'should list installable free extensions from obw/basics and obw/grow', () => {
		const mockSetInstallExtensionOptions = jest.fn();
		// Render once to get installExtensionOptions
		render(
			<SelectiveExtensionsBundle
				isInstallingActivating={ false }
				installableExtensions={ freeExtensions.slice( 1 ) }
				setInstallExtensionOptions={ mockSetInstallExtensionOptions }
			/>
		);

		const { getByText, queryByText } = render(
			<SelectiveExtensionsBundle
				isInstallingActivating={ false }
				setInstallExtensionOptions={ mockSetInstallExtensionOptions }
				installableExtensions={ freeExtensions.slice( 1 ) }
				installExtensionOptions={
					mockSetInstallExtensionOptions.mock.calls[ 0 ][ 0 ]
				}
			/>
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
		).toBeInTheDocument();
		expect( queryByText( new RegExp( 'Random' ) ) ).not.toBeInTheDocument();
	} );

	it( 'should list installable extensions when dropdown is clicked', () => {
		const { getAllByRole, getByText, queryByText } = render(
			<SelectiveExtensionsBundle
				isInstallingActivating={ false }
				installableExtensions={ freeExtensions }
				setInstallExtensionOptions={ jest.fn() }
			/>
		);
		const collapseButton = getAllByRole( 'button' ).find(
			( item ) => item.textContent === ''
		);
		userEvent.click( collapseButton );
		expect( getByText( 'WC Pay Description' ) ).toBeInTheDocument();
		expect( getByText( 'Mailpoet Description' ) ).toBeInTheDocument();
		expect( queryByText( 'Google Description' ) ).toBeInTheDocument();
		expect( queryByText( 'Random Description' ) ).not.toBeInTheDocument();
	} );

	describe( '<ExtensionSection />', () => {
		it( 'should render title and extensions', () => {
			const title = 'This is title';
			const { queryByText } = render(
				<ExtensionSection
					isResolving={ false }
					title={ title }
					extensions={ freeExtensions[ 0 ].plugins }
					installExtensionOptions={ {} }
					onCheckboxChange={ () => {} }
				/>
			);

			expect( queryByText( title ) ).toBeInTheDocument();
			freeExtensions[ 0 ].plugins.forEach( ( { description } ) => {
				expect( queryByText( description ) ).toBeInTheDocument();
			} );
		} );

		it( 'should not render title when no plugins', () => {
			const title = 'This is title';
			const { queryByText } = render(
				<ExtensionSection
					isResolving={ false }
					title={ title }
					extensions={ [] }
					installExtensionOptions={ {} }
					onCheckboxChange={ () => {} }
				/>
			);

			expect( queryByText( title ) ).not.toBeInTheDocument();
		} );
	} );
} );
