/**
 * External dependencies
 */
import { act, render, fireEvent, waitFor } from '@testing-library/react';
import { getSetting } from '@woocommerce/settings';
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

describe( 'CreateAccountFrontendBlock - Automatic password generation off', () => {
	beforeEach( () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key: string, defaultValue: unknown ) => {
				if ( key === 'registrationGeneratePassword' ) {
					return false;
				}
				return defaultValue;
			}
		);
	} );

	it( 'Renders "Set a password" prompt', async () => {
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
				await queryByText(
					textContentMatcher( 'Set a password for test@test.com' )
				)
			).toBeInTheDocument();
		} );
	} );

	it( 'Disables submit button when password is required but empty', async () => {
		const { getByRole, getByLabelText } = render(
			<CreateAccountBlock
				attributes={ { customerEmail: 'test@example.com' } }
				isEditor={ false }
			/>
		);

		await act( async () => {
			const passwordInput = getByLabelText( 'Password' );
			const submitButton = getByRole( 'button', {
				name: 'Create account',
			} );
			expect( submitButton ).toBeDisabled();

			// Weak password.
			fireEvent.change( passwordInput, {
				target: { value: '12345' },
			} );
			expect( submitButton ).toBeDisabled();

			// Strong password.
			fireEvent.change( passwordInput, {
				target: { value: 'StrongP@ssw0rd123!' },
			} );
			expect( submitButton ).toBeEnabled();
		} );
	} );
} );

describe( 'CreateAccountFrontendBlock - Automatic password generation on', () => {
	beforeEach( () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key: string, defaultValue: unknown ) => {
				if ( key === 'registrationGeneratePassword' ) {
					return true;
				}
				return defaultValue;
			}
		);
	} );

	it( 'Does not render "Set a password" prompt', async () => {
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
				await queryByText(
					textContentMatcher( 'Set a password for test@test.com' )
				)
			).not.toBeInTheDocument();
		} );
	} );

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
				await queryByText(
					textContentMatcher(
						'Check your email at test@test.com for the link to set up an account password.'
					)
				)
			).toBeInTheDocument();
		} );
	} );
} );

describe( 'CreateAccountFrontendBlock - Editor mode', () => {
	beforeEach( () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key: string, defaultValue: unknown ) => {
				if ( key === 'registrationGeneratePassword' ) {
					return false;
				}
				return defaultValue;
			}
		);
	} );

	it( 'Renders a placeholder in editor mode', async () => {
		const { queryByText } = render(
			<CreateAccountBlock attributes={ {} } isEditor={ true } />
		);

		await act( async () => {
			expect(
				await queryByText( textContentMatcher( 'Create account' ) )
			).toBeInTheDocument();
		} );

		expect(
			await queryByText(
				textContentMatcher( 'Set a password for customer@email.com' )
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

describe( 'CreateAccountFrontendBlock - Password strength (check-password-strength)', () => {
	beforeEach( () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key: string, defaultValue: unknown ) => {
				if ( key === 'registrationGeneratePassword' ) {
					return false;
				}
				return defaultValue;
			}
		);
	} );

	describe( 'Shows password strength meter and updates accordingly', () => {
		it( 'Very weak password', async () => {
			const { getByLabelText, getByText } = render(
				<CreateAccountBlock
					attributes={ { customerEmail: 'test@example.com' } }
					isEditor={ false }
				/>
			);
			const passwordInput = getByLabelText( 'Password' );
			fireEvent.change( passwordInput, {
				target: { value: 'we' },
			} );
			await waitFor( () => {
				expect(
					getByText( 'Too weak', {
						selector:
							'.wc-block-components-password-strength__meter',
					} )
				).toBeInTheDocument();
			} );
		} );

		it( 'Weak password', async () => {
			const { getByLabelText, getByText } = render(
				<CreateAccountBlock
					attributes={ { customerEmail: 'test@example.com' } }
					isEditor={ false }
				/>
			);
			const passwordInput = getByLabelText( 'Password' );
			fireEvent.change( passwordInput, {
				target: { value: 'weak' },
			} );
			await waitFor( () => {
				expect(
					getByText( 'Weak', {
						selector:
							'.wc-block-components-password-strength__meter',
					} )
				).toBeInTheDocument();
			} );
		} );

		it( 'Medium password', async () => {
			const { getByLabelText, getByText } = render(
				<CreateAccountBlock
					attributes={ { customerEmail: 'test@example.com' } }
					isEditor={ false }
				/>
			);
			const passwordInput = getByLabelText( 'Password' );
			fireEvent.change( passwordInput, {
				target: { value: 'M3dium!!' },
			} );
			await waitFor( () => {
				expect(
					getByText( 'Medium', {
						selector:
							'.wc-block-components-password-strength__meter',
					} )
				).toBeInTheDocument();
			} );
		} );

		it( 'Strong password', async () => {
			const { getByLabelText, getByText } = render(
				<CreateAccountBlock
					attributes={ { customerEmail: 'test@example.com' } }
					isEditor={ false }
				/>
			);
			const passwordInput = getByLabelText( 'Password' );
			fireEvent.change( passwordInput, {
				target: { value: 'StrongP@ssw0rd123!' },
			} );
			await waitFor( () => {
				expect(
					getByText( 'Strong', {
						selector:
							'.wc-block-components-password-strength__meter',
					} )
				).toBeInTheDocument();
			} );
		} );

		it( 'Very strong password', async () => {
			const { getByLabelText, getByText } = render(
				<CreateAccountBlock
					attributes={ { customerEmail: 'test@example.com' } }
					isEditor={ false }
				/>
			);
			const passwordInput = getByLabelText( 'Password' );
			fireEvent.change( passwordInput, {
				target: { value: 'V3ryStrongP@ssw0rd123!' },
			} );
			await waitFor( () => {
				expect(
					getByText( 'Very strong', {
						selector:
							'.wc-block-components-password-strength__meter',
					} )
				).toBeInTheDocument();
			} );
		} );
	} );
} );

describe( 'CreateAccountFrontendBlock - Email handling', () => {
	it( 'Correctly handles email addresses with special characters', async () => {
		const { queryByText } = render(
			<CreateAccountBlock
				attributes={ { customerEmail: 'test+special@example.com' } }
				isEditor={ false }
			/>
		);

		await act( async () => {
			expect(
				await queryByText(
					textContentMatcher(
						'Set a password for test+special@example.com'
					)
				)
			).toBeInTheDocument();
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
