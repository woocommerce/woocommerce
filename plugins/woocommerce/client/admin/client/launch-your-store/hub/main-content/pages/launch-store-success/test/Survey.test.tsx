import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
/**
 * Internal dependencies
 */
import { LysSurvey } from '../Survey';
describe( 'LysSurvey', () => {
	it( 'should render when survey has not been completed', () => {
		const { getByText } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ vi.fn() } />
		);
		expect(
			getByText( 'How was the experience of launching your store?' )
		).toBeInTheDocument();
	} );
	it( 'should not render when survey has been completed', () => {
		const { queryByText } = render(
			<LysSurvey hasCompleteSurvey={ true } onSubmit={ vi.fn() } />
		);
		expect(
			queryByText( 'How was the experience of launching your store?' )
		).toBeNull();
	} );
	it( 'should submit the survey data when the submit button is clicked', async () => {
		const onSubmit = vi.fn();
		const { getByText } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ onSubmit } />
		);
		await act( async () => {
			await userEvent.click( getByText( '😞' ) );
		} );
		await act( async () => {
			await userEvent.click( getByText( 'Send' ) );
		} );
		expect( onSubmit ).toHaveBeenCalledWith( {
			action: 'lys_experience',
			score: 1,
			comments: '',
		} );
	} );
	it( 'should show the thanks message after submitting the survey', async () => {
		const onSubmit = vi.fn();
		const { getByText } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ onSubmit } />
		);
		await act( async () => {
			await userEvent.click( getByText( '😍' ) );
		} );
		await act( async () => {
			await userEvent.click( getByText( 'Send' ) );
		} );
		expect(
			getByText( /We appreciate your feedback!/i )
		).toBeInTheDocument();
	} );
	it( 'should send the comments correctly when the survey is submitted', async () => {
		const onSubmit = vi.fn();
		const { getByText, getByTestId } = render(
			<LysSurvey hasCompleteSurvey={ false } onSubmit={ onSubmit } />
		);
		await act( async () => {
			await userEvent.click( getByText( '😍' ) );
		} );
		const commentTextBox = getByTestId( 'launch-your-store-comment' );
		await act( async () => {
			await userEvent.type( commentTextBox, 'Great job!' );
		} );
		await act( async () => {
			await userEvent.click( getByText( 'Send' ) );
		} );
		expect( onSubmit ).toHaveBeenCalledWith( {
			action: 'lys_experience',
			score: 5,
			comments: 'Great job!',
		} );
	} );
} );
