/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { UpdateBanner } from '../update-banner';
import type { ChangeSummary } from '../hooks/use-change-summary';
import type { ApplyState } from '../hooks/use-update-banner';

const baseSummary: ChangeSummary = {
	version_from: '1.0.0',
	version_to: '1.1.0',
	source_hash_to: 'abc123',
	added_blocks: [],
	removed_blocks: [],
	copy_changes: [],
	structural_changes: [],
	summary_lines: [
		'Added Heading and Paragraph blocks.',
		'Updated footer copy.',
	],
	is_fallback: false,
	cache_hit: false,
};

interface BannerOverrides {
	summary?: ChangeSummary | null;
	applyState?: ApplyState;
	canApply?: boolean;
	canReview?: boolean;
	disabledReason?: 'dirty' | 'read_only' | 'has_conflicts' | null;
	expanded?: boolean;
	onApply?: jest.Mock;
	onReview?: jest.Mock;
	onDismiss?: jest.Mock;
	onAutoDismiss?: jest.Mock;
	onToggleExpanded?: jest.Mock;
}

function renderBanner( overrides: BannerOverrides = {} ) {
	const onApply = overrides.onApply ?? jest.fn();
	const onReview = overrides.onReview ?? jest.fn();
	const onDismiss = overrides.onDismiss ?? jest.fn();
	const onAutoDismiss = overrides.onAutoDismiss ?? jest.fn();
	const onToggleExpanded = overrides.onToggleExpanded ?? jest.fn();

	const result = render(
		<UpdateBanner
			summary={
				overrides.summary === undefined
					? baseSummary
					: overrides.summary
			}
			applyState={ overrides.applyState ?? 'idle' }
			canApply={ overrides.canApply ?? true }
			canReview={ overrides.canReview ?? true }
			disabledReason={ overrides.disabledReason ?? null }
			expanded={ overrides.expanded ?? false }
			onApply={ onApply }
			onReview={ onReview }
			onDismiss={ onDismiss }
			onAutoDismiss={ onAutoDismiss }
			onToggleExpanded={ onToggleExpanded }
		/>
	);

	return {
		...result,
		onApply,
		onReview,
		onDismiss,
		onAutoDismiss,
		onToggleExpanded,
	};
}

describe( 'UpdateBanner', () => {
	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'renders the default state with title, summary subtitle, and actions', () => {
		renderBanner();

		expect(
			screen.getByText( 'Template update available' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Added Heading and Paragraph blocks.' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /^apply$/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /^review$/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: /dismiss for this session/i,
			} )
		).toBeInTheDocument();
	} );

	it( 'renders the generic fallback subtitle when summary.is_fallback is true', () => {
		renderBanner( {
			summary: { ...baseSummary, is_fallback: true },
		} );

		expect(
			screen.getByText( 'WooCommerce updated this template.' )
		).toBeInTheDocument();
		// Expand toggle is hidden in the fallback variant.
		expect(
			screen.queryByRole( 'button', { name: /show \d+ change/i } )
		).not.toBeInTheDocument();
	} );

	it( 'renders the bullet list when expanded is true', () => {
		renderBanner( { expanded: true } );

		expect(
			screen.getByRole( 'button', { name: /hide changes/i } )
		).toBeInTheDocument();

		const items = screen.getAllByRole( 'listitem' );
		expect( items.length ).toBe( 2 );
		expect( items[ 0 ] ).toHaveTextContent(
			'Added Heading and Paragraph blocks.'
		);
		expect( items[ 1 ] ).toHaveTextContent( 'Updated footer copy.' );
	} );

	it( 'replaces the Apply button with a primary "Review changes" button when has_conflicts', () => {
		renderBanner( {
			canApply: false,
			disabledReason: 'has_conflicts',
		} );

		expect(
			screen.getByRole( 'button', { name: /review changes/i } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: /^apply$/i } )
		).not.toBeInTheDocument();
		// The plain tertiary "Review" should NOT also be present.
		expect(
			screen.queryByRole( 'button', { name: /^review$/i } )
		).not.toBeInTheDocument();
	} );

	it( 'aria-disables the Apply button and surfaces a tooltip when dirty', () => {
		renderBanner( {
			canApply: false,
			disabledReason: 'dirty',
		} );

		const applyButton = screen.getByRole( 'button', { name: /^apply$/i } );
		expect( applyButton ).toHaveAttribute( 'aria-disabled', 'true' );

		// The tip is rendered inline as visually-hidden text alongside
		// the disabled button, so it's announced by screen readers and
		// queryable here directly.
		expect(
			screen.getAllByText( 'Save your changes first.' ).length
		).toBeGreaterThan( 0 );
	} );

	it( 'aria-disables both Apply and Review and surfaces a tooltip when read_only', () => {
		renderBanner( {
			canApply: false,
			canReview: false,
			disabledReason: 'read_only',
		} );

		const applyButton = screen.getByRole( 'button', { name: /^apply$/i } );
		const reviewButton = screen.getByRole( 'button', {
			name: /^review$/i,
		} );
		expect( applyButton ).toHaveAttribute( 'aria-disabled', 'true' );
		expect( reviewButton ).toHaveAttribute( 'aria-disabled', 'true' );

		expect(
			screen.getAllByText(
				"You don't have permission to update this email."
			).length
		).toBeGreaterThan( 0 );
	} );

	it( 'shows the applying label and disables Review/Dismiss when applyState is applying', () => {
		renderBanner( { applyState: 'applying' } );

		// "Applying…" label.
		const applyButton = screen.getByRole( 'button', {
			name: /applying/i,
		} );
		expect( applyButton ).toBeInTheDocument();

		const reviewButton = screen.getByRole( 'button', {
			name: /^review$/i,
		} );
		const dismissButton = screen.getByRole( 'button', {
			name: /dismiss for this session/i,
		} );
		expect( reviewButton ).toBeDisabled();
		expect( dismissButton ).toBeDisabled();
	} );

	it( 'renders the success morph and auto-dismisses after 2s via onAutoDismiss', () => {
		jest.useFakeTimers();
		const onDismiss = jest.fn();
		const onAutoDismiss = jest.fn();
		renderBanner( { applyState: 'applied', onDismiss, onAutoDismiss } );

		expect( screen.getByText( 'Template updated' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Your customizations were preserved.' )
		).toBeInTheDocument();

		act( () => {
			jest.advanceTimersByTime( 2000 );
		} );
		// Spec §9.2: success auto-dismiss does NOT fire `_dismissed`. The
		// banner routes through the no-event path so the hook can skip
		// `recordEvent`.
		expect( onAutoDismiss ).toHaveBeenCalledTimes( 1 );
		expect( onDismiss ).not.toHaveBeenCalled();
	} );

	it( 'success-state × button routes to onAutoDismiss, not onDismiss', () => {
		const onDismiss = jest.fn();
		const onAutoDismiss = jest.fn();
		renderBanner( { applyState: 'applied', onDismiss, onAutoDismiss } );

		fireEvent.click(
			screen.getByRole( 'button', {
				name: /dismiss for this session/i,
			} )
		);
		expect( onAutoDismiss ).toHaveBeenCalledTimes( 1 );
		expect( onDismiss ).not.toHaveBeenCalled();
	} );

	it( 'renders the failure morph with role=alert and does not auto-dismiss', () => {
		jest.useFakeTimers();
		const onDismiss = jest.fn();
		const { container } = renderBanner( {
			applyState: 'failed',
			onDismiss,
		} );

		expect( screen.getByText( /couldn't apply/i ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /try again/i } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: /review changes/i } )
		).toBeInTheDocument();

		// Use the container query — failure morph carries role="alert".
		expect( container.querySelector( '[role="alert"]' ) ).not.toBeNull();

		act( () => {
			jest.advanceTimersByTime( 5000 );
		} );
		expect( onDismiss ).not.toHaveBeenCalled();
	} );

	it( 'renders default container with role=status and aria-live=polite', () => {
		const { container } = renderBanner();
		const statusEl = container.querySelector(
			'[role="status"][aria-live="polite"]'
		);
		expect( statusEl ).not.toBeNull();
	} );

	it( 'fires the click callbacks for Apply, Review, and Dismiss', () => {
		const onApply = jest.fn();
		const onReview = jest.fn();
		const onDismiss = jest.fn();
		const onAutoDismiss = jest.fn();
		renderBanner( { onApply, onReview, onDismiss, onAutoDismiss } );

		fireEvent.click( screen.getByRole( 'button', { name: /^apply$/i } ) );
		fireEvent.click( screen.getByRole( 'button', { name: /^review$/i } ) );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: /dismiss for this session/i,
			} )
		);

		expect( onApply ).toHaveBeenCalledTimes( 1 );
		expect( onReview ).toHaveBeenCalledTimes( 1 );
		// Default-state × routes to onDismiss (the user-initiated path that
		// fires the `_dismissed` Tracks event in the hook).
		expect( onDismiss ).toHaveBeenCalledTimes( 1 );
		expect( onAutoDismiss ).not.toHaveBeenCalled();
	} );

	it( 'failure-state × routes to onDismiss (Tracks event fires)', () => {
		const onDismiss = jest.fn();
		const onAutoDismiss = jest.fn();
		renderBanner( { applyState: 'failed', onDismiss, onAutoDismiss } );

		fireEvent.click(
			screen.getByRole( 'button', {
				name: /dismiss for this session/i,
			} )
		);
		expect( onDismiss ).toHaveBeenCalledTimes( 1 );
		expect( onAutoDismiss ).not.toHaveBeenCalled();
	} );
} );
