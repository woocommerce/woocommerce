/**
 * External dependencies
 */
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { ReviewDrawer } from '../review-drawer';
import type { ChangeSummary } from '../hooks/use-change-summary';

const mockUseChangeSummary = jest.fn();
const mockUseApplyUpdate = jest.fn();

jest.mock( '../hooks/use-change-summary', () => ( {
	useChangeSummary: () => mockUseChangeSummary(),
} ) );

jest.mock( '../hooks/use-apply-update', () => ( {
	useApplyUpdate: () => mockUseApplyUpdate(),
} ) );

const summary: ChangeSummary = {
	version_from: '1.0.0',
	version_to: '1.1.0',
	source_hash_to: 'abc123',
	added_blocks: [],
	removed_blocks: [],
	copy_changes: [
		{
			block: 'Paragraph',
			before: 'Merchant text one',
			after: 'Core text one',
			occurrence: 1,
			total: 2,
			path: [ 0 ],
			auto_resolvable: false,
		},
		{
			block: 'Paragraph',
			before: 'Merchant text two',
			after: 'Core text two',
			occurrence: 2,
			total: 2,
			path: [ 1 ],
			auto_resolvable: false,
		},
	],
	structural_changes: [],
	summary_lines: [],
	is_fallback: false,
	cache_hit: false,
};

describe( 'ReviewDrawer', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'applies only an explicitly selected core conflict and closes after success', async () => {
		const apply = jest.fn().mockResolvedValue( { status: 'applied' } );
		const onOpenChange = jest.fn();
		mockUseChangeSummary.mockReturnValue( {
			summary,
			isLoading: false,
			error: null,
			refetch: jest.fn(),
		} );
		mockUseApplyUpdate.mockReturnValue( { apply, isApplying: false } );

		render(
			<ReviewDrawer
				postId={ 123 }
				emailTitle="New order"
				isOpen
				onOpenChange={ onOpenChange }
			/>
		);

		const conflictGroups = screen.getAllByRole( 'radiogroup', {
			name: 'Choose which version to apply',
		} );
		expect( conflictGroups ).toHaveLength( 2 );
		for ( const conflictGroup of conflictGroups ) {
			expect(
				within( conflictGroup ).getByRole( 'radio', {
					name: /keep yours/i,
				} )
			).toHaveAttribute( 'aria-checked', 'true' );
			expect(
				within( conflictGroup ).getByRole( 'radio', {
					name: /use core/i,
				} )
			).toHaveAttribute( 'aria-checked', 'false' );
		}

		await userEvent.click(
			within( conflictGroups[ 0 ] ).getByRole( 'radio', {
				name: /use core/i,
			} )
		);

		expect(
			within( conflictGroups[ 0 ] ).getByRole( 'radio', {
				name: /use core/i,
			} )
		).toHaveAttribute( 'aria-checked', 'true' );
		expect(
			within( conflictGroups[ 0 ] ).getByRole( 'radio', {
				name: /keep yours/i,
			} )
		).toHaveAttribute( 'aria-checked', 'false' );
		expect(
			within( conflictGroups[ 1 ] ).getByRole( 'radio', {
				name: /keep yours/i,
			} )
		).toHaveAttribute( 'aria-checked', 'true' );
		expect(
			within( conflictGroups[ 1 ] ).getByRole( 'radio', {
				name: /use core/i,
			} )
		).toHaveAttribute( 'aria-checked', 'false' );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Apply (2)' } )
		);

		await waitFor( () =>
			expect( apply ).toHaveBeenCalledWith( [
				{ path: [ 0 ], decision: 'use_core' },
			] )
		);
		expect( apply ).toHaveBeenCalledTimes( 1 );
		await waitFor( () =>
			expect( onOpenChange ).toHaveBeenCalledWith( false )
		);
		expect( onOpenChange ).toHaveBeenCalledTimes( 1 );
	} );
} );
