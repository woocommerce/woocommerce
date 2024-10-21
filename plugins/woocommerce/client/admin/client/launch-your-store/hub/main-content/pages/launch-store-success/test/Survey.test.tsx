/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { LysSurvey } from '../Survey';

describe( 'LysSurvey', () => {
	it( 'should render when survey has not been completed', () => {
		const { getByText } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ jest.fn() } />
		);
		expect(
			getByText( 'How was the experience of launching your store?' )
		).toBeInTheDocument();
	} );

	it( 'should not render when survey has been completed', () => {
		const { queryByText } = render(
			<LysSurvey hasCompleteSurvey={ true } onSubmit={ jest.fn() } />
		);
		expect(
			queryByText( 'How was the experience of launching your store?' )
		).toBeNull();
	} );

	it( 'should submit the survey data when the submit button is clicked', () => {
		const onSubmit = jest.fn();
		const { getByText } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ onSubmit } />
		);
		getByText( '😞' ).click();
		getByText( 'Send' ).click();
		expect( onSubmit ).toHaveBeenCalledWith( {
			action: 'lys_experience',
			score: 1,
			comments: '',
		} );
	} );

	it( 'should show the thanks message after submitting the survey', () => {
		const onSubmit = jest.fn();
		const { getByText } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ onSubmit } />
		);
		getByText( '😍' ).click();
		getByText( 'Send' ).click();
		expect(
			getByText( /We appreciate your feedback!/i )
		).toBeInTheDocument();
	} );

	it( 'should send the comments correctly when the survey is submitted', () => {
		const onSubmit = jest.fn();
		const { getByText, getByTestId } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ onSubmit } />
		);
		getByText( '😍' ).click();
		const commentTextBox = getByTestId( 'launch-your-store-comment' );
		fireEvent.change( commentTextBox, { target: { value: 'Great job!' } } );
		getByText( 'Send' ).click();
		expect( onSubmit ).toHaveBeenCalledWith( {
			action: 'lys_experience',
			score: 5,
			comments: 'Great job!',
		} );
	} );
} );
