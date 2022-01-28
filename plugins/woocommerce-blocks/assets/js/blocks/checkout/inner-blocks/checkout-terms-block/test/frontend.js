/**
 * External dependencies
 */
import {
	render,
	findByLabelText,
	queryByLabelText,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import FrontendBlock from '../frontend';

describe( 'FrontendBlock', () => {
	let validationData = {
		hasValidationErrors: false,
		getValidationError: jest.fn(),
		clearValidationError: jest.fn(),
		hideValidationError: jest.fn(),
		setValidationErrors: jest.fn(),
	};
	beforeEach( () => {
		validationData = {
			hasValidationErrors: false,
			getValidationError: jest.fn(),
			clearValidationError: jest.fn(),
			hideValidationError: jest.fn(),
			setValidationErrors: jest.fn(),
		};
	} );

	it( 'Renders a checkbox if the checkbox prop is true', async () => {
		const { container } = render(
			<FrontendBlock
				checkbox={ true }
				text={ 'I agree to the terms and conditions' }
				validation={ validationData }
			/>
		);

		const checkbox = await findByLabelText(
			container,
			'I agree to the terms and conditions'
		);

		expect( checkbox ).toBeInTheDocument();
	} );

	it( 'Does not render a checkbox if the checkbox prop is false', async () => {
		const { container } = render(
			<FrontendBlock
				checkbox={ false }
				text={ 'I agree to the terms and conditions' }
				validation={ validationData }
			/>
		);

		const checkbox = queryByLabelText(
			container,
			'I agree to the terms and conditions'
		);

		expect( checkbox ).not.toBeInTheDocument();
	} );

	it( 'Clears any validation errors when the checkbox is checked', async () => {
		validationData.getValidationError.mockImplementation( () => {
			return {
				message: 'Please read and accept the terms and conditions.',
				hidden: false,
			};
		} );
		const { container } = render(
			<FrontendBlock
				checkbox={ true }
				text={ 'I agree to the terms and conditions' }
				validation={ validationData }
			/>
		);
		const checkbox = await findByLabelText(
			container,
			'I agree to the terms and conditions'
		);
		userEvent.click( checkbox );
		expect( validationData.clearValidationError ).toHaveBeenLastCalledWith(
			expect.stringMatching( /terms-and-conditions-\d/ )
		);
	} );
} );
