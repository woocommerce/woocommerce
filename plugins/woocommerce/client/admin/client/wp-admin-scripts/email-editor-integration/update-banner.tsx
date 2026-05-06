/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { Button, Tooltip } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ChangeSummary } from './hooks/use-change-summary';
import type { ApplyState } from './hooks/use-update-banner';

/**
 * Auto-dismiss the success morph this many milliseconds after it
 * appears. Long enough for the merchant to see the confirmation, short
 * enough that the banner doesn't linger after the work is done.
 */
const SUCCESS_AUTODISMISS_MS = 2000;

/**
 * Stable id used to wire `aria-controls` from the expand toggle to the
 * `<ul>` of summary lines.
 */
const CHANGES_LIST_ID = 'wc-update-banner-changes';

interface UpdateBannerProps {
	summary: ChangeSummary | null;
	applyState: ApplyState;
	canApply: boolean;
	canReview: boolean;
	disabledReason: 'dirty' | 'read_only' | 'has_conflicts' | null;
	expanded: boolean;
	onApply: () => void;
	onReview: () => void;
	/**
	 * User-initiated dismiss (× click in the default or failure morph).
	 * The hook fires the `_dismissed` Tracks event from this path —
	 * spec §9.2 scopes the event to the user-initiated paths only.
	 */
	onDismiss: () => void;
	/**
	 * Non-user dismiss path used by the success morph: both the 2s
	 * auto-dismiss timer and the success-morph × click route here so
	 * the `_dismissed` Tracks event does NOT fire (per spec §9.2 —
	 * "does NOT fire on auto-dismiss after success").
	 */
	onAutoDismiss: () => void;
	onToggleExpanded: () => void;
}

/**
 * Tooltip text for a guard reason. Returns `null` when no tooltip
 * should be surfaced — for `has_conflicts` the Apply slot already
 * morphs into a primary "Review changes" button so no tooltip is
 * needed; for `null` (no guard) the buttons are enabled.
 */
function tooltipText(
	reason: UpdateBannerProps[ 'disabledReason' ]
): string | null {
	switch ( reason ) {
		case 'dirty':
			return __( 'Save your changes first.', 'woocommerce' );
		case 'read_only':
			return __(
				"You don't have permission to update this email.",
				'woocommerce'
			);
		case 'has_conflicts':
		case null:
		default:
			return null;
	}
}

/**
 * Compute the subtitle for the default banner branch.
 *
 * Falls back to a generic message when the summary is missing, the
 * change-summary detector flagged the diff as a fallback, or the
 * detector returned an empty `summary_lines`.
 */
function defaultSubtitle( summary: ChangeSummary | null ): string {
	if ( summary === null || summary.is_fallback ) {
		return __( 'WooCommerce updated this template.', 'woocommerce' );
	}
	if ( summary.summary_lines.length === 0 ) {
		return sprintf(
			// translators: %s is a WooCommerce version number, e.g. "1.2.3".
			__( 'WooCommerce %s refreshed this template.', 'woocommerce' ),
			summary.version_to
		);
	}
	return summary.summary_lines[ 0 ];
}

/**
 * Wrap a disabled button in a Tooltip when there's a guard reason that
 * carries help text. The wrapping `<span>` is required: a disabled
 * native button doesn't fire pointer events on its own, so Tooltip
 * needs an enabled element to attach hover/focus listeners to.
 *
 * The tip is also rendered inline as visually-hidden screen-reader
 * text, so assistive tech announces the guard reason even when the
 * tooltip's hover/focus heuristics don't fire (and so tests can query
 * the help text without simulating hover delays).
 */
function MaybeTooltip( {
	tip,
	children,
}: {
	tip: string | null;
	children: JSX.Element;
} ): JSX.Element {
	if ( ! tip ) {
		return children;
	}
	return (
		<Tooltip text={ tip }>
			<span className="wc-update-banner__tooltip-wrap">
				{ children }
				<span className="screen-reader-text">{ tip }</span>
			</span>
		</Tooltip>
	);
}

/**
 * Presentational floating banner that surfaces template-divergence
 * info to the merchant inside the email editor (RSM-141).
 *
 * Pure: receives every piece of state via props from `useUpdateBanner`
 * and never reads from the data layer itself. Three render branches
 * keyed off `applyState`:
 *
 *   - `applied` → success morph, auto-dismisses after 2s
 *   - `failed`  → failure morph, manual recovery only
 *   - default   → idle / applying — the actionable banner
 */
export function UpdateBanner( {
	summary,
	applyState,
	canApply,
	canReview,
	disabledReason,
	expanded,
	onApply,
	onReview,
	onDismiss,
	onAutoDismiss,
	onToggleExpanded,
}: UpdateBannerProps ): JSX.Element {
	// ---- Success morph ---------------------------------------------------
	// Schedule the auto-dismiss timer once the morph mounts; clean it up
	// on unmount or if `onAutoDismiss` changes mid-lifecycle.
	useEffect( () => {
		if ( applyState !== 'applied' ) {
			return;
		}
		const handle = setTimeout( () => {
			onAutoDismiss();
		}, SUCCESS_AUTODISMISS_MS );
		return () => {
			clearTimeout( handle );
		};
	}, [ applyState, onAutoDismiss ] );

	if ( applyState === 'applied' ) {
		return (
			<div
				className="wc-update-banner wc-update-banner--success"
				role="status"
				aria-live="polite"
			>
				<div className="wc-update-banner__body">
					<div className="wc-update-banner__title">
						{ __( 'Template updated', 'woocommerce' ) }
					</div>
					<div className="wc-update-banner__subtitle">
						{ __(
							'Your customizations were preserved.',
							'woocommerce'
						) }
					</div>
				</div>
				<button
					type="button"
					className="wc-update-banner__dismiss"
					aria-label={ __(
						'Dismiss for this session',
						'woocommerce'
					) }
					onClick={ onAutoDismiss }
				>
					{ '×' }
				</button>
			</div>
		);
	}

	// ---- Failure morph ---------------------------------------------------
	if ( applyState === 'failed' ) {
		return (
			<div
				className="wc-update-banner wc-update-banner--failure"
				role="alert"
			>
				<div className="wc-update-banner__body">
					<div className="wc-update-banner__title">
						{ __( "Couldn't apply", 'woocommerce' ) }
					</div>
				</div>
				<div className="wc-update-banner__actions">
					<Button variant="primary" onClick={ onApply }>
						{ __( 'Try again', 'woocommerce' ) }
					</Button>
					<Button variant="tertiary" onClick={ onReview }>
						{ __( 'Review changes', 'woocommerce' ) }
					</Button>
				</div>
				<button
					type="button"
					className="wc-update-banner__dismiss"
					aria-label={ __(
						'Dismiss for this session',
						'woocommerce'
					) }
					onClick={ onDismiss }
				>
					{ '×' }
				</button>
			</div>
		);
	}

	// ---- Default / applying branch --------------------------------------
	const isApplying = applyState === 'applying';
	const isConflict = disabledReason === 'has_conflicts';
	// Pull out the lines once so the JSX below doesn't need non-null
	// assertions on `summary` — the local is narrowed to `string[]`.
	const expandableLines: string[] =
		summary !== null && ! summary.is_fallback ? summary.summary_lines : [];
	const hasExpandableChanges = expandableLines.length > 0;
	const subtitle = defaultSubtitle( summary );
	const tip = tooltipText( disabledReason );

	const applyLabel = isApplying
		? __( 'Applying…', 'woocommerce' )
		: __( 'Apply', 'woocommerce' );

	// In the conflict variant the Apply slot becomes the primary
	// "Review changes" CTA — a disabled Apply button would be the wrong
	// affordance, since the user can't apply until they resolve the
	// conflicts in the review drawer.
	const applySlot = isConflict ? (
		<Button variant="primary" onClick={ onReview }>
			{ __( 'Review changes', 'woocommerce' ) }
		</Button>
	) : (
		<MaybeTooltip tip={ tip }>
			<Button
				variant="primary"
				onClick={ onApply }
				disabled={ ! canApply || isApplying }
				aria-disabled={ ! canApply ? 'true' : undefined }
				isBusy={ isApplying }
			>
				{ applyLabel }
			</Button>
		</MaybeTooltip>
	);

	const reviewSlot = isConflict ? null : (
		<MaybeTooltip tip={ tip }>
			<Button
				variant="tertiary"
				onClick={ onReview }
				disabled={ ! canReview || isApplying }
				aria-disabled={ ! canReview ? 'true' : undefined }
			>
				{ __( 'Review', 'woocommerce' ) }
			</Button>
		</MaybeTooltip>
	);

	return (
		<div className="wc-update-banner" role="status" aria-live="polite">
			<div className="wc-update-banner__body">
				<div className="wc-update-banner__title">
					{ __( 'Template update available', 'woocommerce' ) }
				</div>
				<div className="wc-update-banner__subtitle">{ subtitle }</div>
				{ hasExpandableChanges && (
					<>
						<button
							type="button"
							className="wc-update-banner__expand"
							aria-expanded={ expanded }
							aria-controls={ CHANGES_LIST_ID }
							onClick={ onToggleExpanded }
						>
							{ expanded
								? __( 'Hide changes', 'woocommerce' )
								: sprintf(
										/* translators: %d is the number of summary changes. */
										_n(
											'Show %d change',
											'Show %d changes',
											expandableLines.length,
											'woocommerce'
										),
										expandableLines.length
								  ) }
						</button>
						{ expanded && (
							<ul
								id={ CHANGES_LIST_ID }
								className="wc-update-banner__changes"
							>
								{ expandableLines.map( ( line, i ) => (
									<li key={ i }>{ line }</li>
								) ) }
							</ul>
						) }
					</>
				) }
			</div>
			<div className="wc-update-banner__actions">
				{ applySlot }
				{ reviewSlot }
			</div>
			<button
				type="button"
				className="wc-update-banner__dismiss"
				aria-label={ __( 'Dismiss for this session', 'woocommerce' ) }
				onClick={ onDismiss }
				disabled={ isApplying }
			>
				{ '×' }
			</button>
		</div>
	);
}
