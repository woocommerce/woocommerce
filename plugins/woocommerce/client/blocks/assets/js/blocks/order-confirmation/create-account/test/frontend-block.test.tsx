/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CreateAccountBlock from '../form';
import { textContentMatcher } from '../../../../../../tests/utils/find-by-text';

jest.mock( '@woocommerce/settings', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest
		.fn()
		.mockImplementation(
			( key: string, defaultValue: unknown ) => defaultValue
		),
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/i18n' ),
	__: jest.fn( ( msg ) => msg ),
} ) );

describe( 'CreateAccountFrontendBlock', () => {
	it( 'Submit button is not disabled', async () => {
		const { getByRole } = render(
			<CreateAccountBlock
				attributes={ { customerEmail: 'test@example.com' } }
				isEditor={ false }
			/>
		);

		await act( async () => {
			const submitButton = getByRole( 'button', {
				name: 'Create account',
			} );
			expect( submitButton ).toBeEnabled();
		} );
	} );

	it( 'Renders a notice stating that a password will be emailed to the user', async () => {
		const { queryByText } = render(
			<CreateAccountBlock
				attributes={ {
					customerEmail: 'test@test.com',
				} }
				isEditor={ false }
			/>
		);

		await act( async () => {
			expect(
				queryByText(
					textContentMatcher(
						'We’ll email you a link to set your password.'
					)
				)
			).toBeInTheDocument();
		} );
	} );
} );

describe( 'CreateAccountFrontendBlock - Editor mode', () => {
	it( 'Renders a placeholder in editor mode', async () => {
		const { queryByText } = render(
			<CreateAccountBlock attributes={ {} } isEditor={ true } />
		);

		await act( async () => {
			expect(
				queryByText( textContentMatcher( 'Create account' ) )
			).toBeInTheDocument();
		} );

		expect(
			queryByText(
				textContentMatcher(
					'We’ll email you a link to set your password.'
				)
			)
		).toBeInTheDocument();
	} );
} );

describe( 'CreateAccountFrontendBlock - Edge cases', () => {
	it( 'Does not render anything when email is empty', async () => {
		const { container } = render(
			<CreateAccountBlock
				attributes={ { customerEmail: '' } }
				isEditor={ false }
			/>
		);

		await act( async () => {
			expect( container ).toBeEmptyDOMElement();
		} );
	} );
} );

describe( 'CreateAccountFrontendBlock - Email handling', () => {
	it( 'Correctly handles email addresses with special characters', async () => {
		const { container } = render(
			<CreateAccountBlock
				attributes={ { customerEmail: 'test+special@example.com' } }
				isEditor={ false }
			/>
		);

		await act( async () => {
			expect(
				container.querySelector( 'input[name="email"]' )
			).toHaveValue( 'test+special@example.com' );
		} );
	} );
} );

describe( 'CreateAccountFrontendBlock - Localization', () => {
	it( 'Displays translated strings when locale is changed', async () => {
		// Mock the translation function
		( __ as jest.Mock ).mockImplementation( ( text: string ) =>
			text === 'Create account' ? 'Créer un compte' : text
		);

		const { getByText } = render(
			<CreateAccountBlock
				attributes={ { customerEmail: 'test@example.com' } }
				isEditor={ false }
			/>
		);

		await act( async () => {
			expect( getByText( 'Créer un compte' ) ).toBeInTheDocument();
		} );

		// Clean up the mock
		jest.unmock( '@wordpress/i18n' );
	} );
} );
