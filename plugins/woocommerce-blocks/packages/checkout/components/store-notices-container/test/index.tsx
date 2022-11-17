/**
 * External dependencies
 */
import { store as noticesStore } from '@wordpress/notices';
import { dispatch, select } from '@wordpress/data';
import { act, render, screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import StoreNoticesContainer from '../index';

describe( 'StoreNoticesContainer', () => {
	it( 'Shows notices from the correct context', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom test error', {
			id: 'custom-test-error',
			context: 'test-context',
		} );
		render( <StoreNoticesContainer context="test-context" /> );
		expect( screen.getAllByText( /Custom test error/i ) ).toHaveLength( 2 );
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-test-error',
				'test-context'
			)
		);
		await waitFor( () => {
			return (
				select( noticesStore ).getNotices( 'test-context' ).length === 0
			);
		} );
	} );

	it( 'Does not show notices from other contexts', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom test error 2', {
			id: 'custom-test-error-2',
			context: 'test-context',
		} );
		render( <StoreNoticesContainer context="other-context" /> );
		expect( screen.queryAllByText( /Custom test error 2/i ) ).toHaveLength(
			0
		);
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-test-error-2',
				'test-context'
			)
		);
		await waitFor( () => {
			return (
				select( noticesStore ).getNotices( 'test-context' ).length === 0
			);
		} );
	} );

	it( 'Does not show snackbar notices', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom test error 2', {
			id: 'custom-test-error-2',
			context: 'test-context',
			type: 'snackbar',
		} );
		render( <StoreNoticesContainer context="other-context" /> );
		expect( screen.queryAllByText( /Custom test error 2/i ) ).toHaveLength(
			0
		);
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-test-error-2',
				'test-context'
			)
		);
		await waitFor( () => {
			return (
				select( noticesStore ).getNotices( 'test-context' ).length === 0
			);
		} );
	} );

	it( 'Shows additional notices', () => {
		render(
			<StoreNoticesContainer
				additionalNotices={ [
					{
						id: 'additional-test-error',
						status: 'error',
						spokenMessage: 'Additional test error',
						isDismissible: false,
						content: 'Additional test error',
						actions: [],
						speak: false,
						__unstableHTML: '',
						type: 'default',
					},
				] }
			/>
		);
		expect( screen.getAllByText( /Additional test error/i ) ).toHaveLength(
			2
		);
	} );
} );
