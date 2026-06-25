/**
 * External dependencies
 */
import { createPortal, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	useChangeSummary,
	type ChangeSummary,
	type ChangeSummaryCopyChange,
	type ChangeSummaryStructuralChange,
} from './hooks/use-change-summary';
import { useApplyUpdate, type ApplyChoice } from './hooks/use-apply-update';

interface Props {
	postId: number | null;
	emailTitle: string;
	isOpen: boolean;
	onOpenChange: ( open: boolean ) => void;
}

type ChoiceMap = Record< string, 'keep_yours' | 'use_core' >;
type AutoTag = 'apply_core' | 'keep_yours';

/** Stable string key for a path array, used as the choice-map key. */
function pathKey( path: Array< number | string > ): string {
	return JSON.stringify( path );
}

/** Decorative leading dot for section headings (color-coded). */
const SectionDot = ( { tone }: { tone: 'warning' | 'brand' } ) => (
	<span
		aria-hidden="true"
		className={ `woocommerce-review-drawer__dot woocommerce-review-drawer__dot--${ tone }` }
	/>
);

/**
 * Per-conflict choice card. Two cards live side-by-side in a 2-column
 * grid; selecting one toggles the merchant's decision for that block.
 * The label + hint sublabel comes from the design handoff —
 * `ToggleGroupControl` only fits a single label, so we keep bespoke
 * buttons with `role="radio"` for the same a11y semantics.
 */
const ChoiceCard = ( {
	label,
	hint,
	active,
	onClick,
}: {
	label: string;
	hint: string;
	active: boolean;
	onClick: () => void;
} ) => (
	<button
		type="button"
		role="radio"
		aria-checked={ active }
		onClick={ onClick }
		className={ [
			'woocommerce-review-drawer__choice-card',
			active && 'is-active',
		]
			.filter( Boolean )
			.join( ' ' ) }
	>
		<span className="woocommerce-review-drawer__choice-label">
			{ label }
		</span>
		<span className="woocommerce-review-drawer__choice-hint">{ hint }</span>
	</button>
);

const ConflictsGroup = ( {
	conflicts,
	choices,
	onChoose,
}: {
	conflicts: ChangeSummaryCopyChange[];
	choices: ChoiceMap;
	onChoose: (
		path: Array< number | string >,
		decision: 'keep_yours' | 'use_core'
	) => void;
} ) => {
	if ( conflicts.length === 0 ) {
		return null;
	}

	const heading = sprintf(
		/* translators: %d: number of conflicts. */
		_n(
			'Needs your attention · %d conflict',
			'Needs your attention · %d conflicts',
			conflicts.length,
			'woocommerce'
		),
		conflicts.length
	);

	return (
		<section
			className="woocommerce-review-drawer__group"
			aria-labelledby="woocommerce-review-drawer-conflicts-heading"
		>
			<h3
				id="woocommerce-review-drawer-conflicts-heading"
				className="woocommerce-review-drawer__group-h"
			>
				<SectionDot tone="warning" />
				{ heading }
			</h3>
			{ conflicts.map( ( conflict ) => {
				const key = pathKey( conflict.path );
				const decision = choices[ key ] ?? 'keep_yours';
				const blockTitle =
					conflict.total > 1
						? sprintf(
								/* translators: 1: block name; 2: occurrence; 3: total. */
								__( '%1$s %2$d of %3$d', 'woocommerce' ),
								conflict.block,
								conflict.occurrence,
								conflict.total
						  )
						: conflict.block;

				return (
					<div
						key={ key }
						className="woocommerce-review-drawer__item"
					>
						<div className="woocommerce-review-drawer__item-h">
							<h4 className="woocommerce-review-drawer__item-title">
								{ blockTitle }
							</h4>
							<span className="woocommerce-review-drawer__tag woocommerce-review-drawer__tag--conflict">
								{ __( 'Conflict', 'woocommerce' ) }
							</span>
						</div>
						<p className="woocommerce-review-drawer__item-sub">
							{ __(
								'Core changed this text. Pick which version to keep.',
								'woocommerce'
							) }
						</p>
						<div
							className="woocommerce-review-drawer__diff"
							role="group"
							aria-label={ __( 'Diff', 'woocommerce' ) }
						>
							<div className="woocommerce-review-drawer__diff-row woocommerce-review-drawer__diff-row--minus">
								{ conflict.before }
							</div>
							<div className="woocommerce-review-drawer__diff-row woocommerce-review-drawer__diff-row--plus">
								{ conflict.after }
							</div>
						</div>
						<div
							className="woocommerce-review-drawer__choice"
							role="radiogroup"
							aria-label={ __(
								'Choose which version to apply',
								'woocommerce'
							) }
						>
							<ChoiceCard
								label={ __( 'Keep yours', 'woocommerce' ) }
								hint={ __( 'Default · safe', 'woocommerce' ) }
								active={ decision === 'keep_yours' }
								onClick={ () =>
									onChoose( conflict.path, 'keep_yours' )
								}
							/>
							<ChoiceCard
								label={ __( 'Use core', 'woocommerce' ) }
								hint={ __(
									'Discard your edit',
									'woocommerce'
								) }
								active={ decision === 'use_core' }
								onClick={ () =>
									onChoose( conflict.path, 'use_core' )
								}
							/>
						</div>
					</div>
				);
			} ) }
		</section>
	);
};

const AutoResolvedItem = ( {
	title,
	sub,
	tag,
}: {
	title: string;
	sub: string;
	tag: AutoTag;
} ) => (
	<div className="woocommerce-review-drawer__item">
		<div className="woocommerce-review-drawer__item-h">
			<h4 className="woocommerce-review-drawer__item-title">{ title }</h4>
			<span
				className={ [
					'woocommerce-review-drawer__tag',
					`woocommerce-review-drawer__tag--${
						tag === 'apply_core' ? 'apply-core' : 'keep-yours'
					}`,
				].join( ' ' ) }
			>
				{ tag === 'apply_core'
					? __( 'Apply core', 'woocommerce' )
					: __( 'Keep yours', 'woocommerce' ) }
			</span>
		</div>
		<p className="woocommerce-review-drawer__item-sub">{ sub }</p>
	</div>
);

const AutoResolvedGroup = ( {
	summary,
	autoResolvedCopyChanges,
}: {
	summary: ChangeSummary;
	autoResolvedCopyChanges: ChangeSummaryCopyChange[];
} ) => {
	const total =
		summary.added_blocks.length +
		summary.removed_blocks.length +
		summary.structural_changes.length +
		autoResolvedCopyChanges.length;

	if ( total === 0 ) {
		return null;
	}

	const heading = sprintf(
		/* translators: %d: number of auto-resolved blocks. */
		_n(
			'Auto-resolved · %d block',
			'Auto-resolved · %d blocks',
			total,
			'woocommerce'
		),
		total
	);

	return (
		<section
			className="woocommerce-review-drawer__group"
			aria-labelledby="woocommerce-review-drawer-auto-heading"
		>
			<h3
				id="woocommerce-review-drawer-auto-heading"
				className="woocommerce-review-drawer__group-h"
			>
				<SectionDot tone="brand" />
				{ heading }
			</h3>

			{ autoResolvedCopyChanges.map( ( entry ) => {
				const title =
					entry.total > 1
						? sprintf(
								/* translators: 1: block name; 2: occurrence; 3: total. */
								__( '%1$s %2$d of %3$d', 'woocommerce' ),
								entry.block,
								entry.occurrence,
								entry.total
						  )
						: entry.block;
				return (
					<AutoResolvedItem
						key={ `copy-${ pathKey( entry.path ) }-${
							entry.occurrence ?? 0
						}` }
						title={ title }
						sub={ __(
							'Core updated this text. Your version was unchanged, so the update will apply.',
							'woocommerce'
						) }
						tag="apply_core"
					/>
				);
			} ) }
			{ summary.added_blocks.map( ( entry ) => (
				<AutoResolvedItem
					key={ `added-${ pathKey( entry.path ) }` }
					title={ entry.label }
					sub={ __(
						'Added by core. Will appear in your email.',
						'woocommerce'
					) }
					tag="apply_core"
				/>
			) ) }
			{ summary.removed_blocks.map( ( entry ) => (
				<AutoResolvedItem
					key={ `removed-${ pathKey( entry.path ) }` }
					title={ entry.label }
					sub={ __(
						'Not in core. Your block is preserved.',
						'woocommerce'
					) }
					tag="keep_yours"
				/>
			) ) }
			{ summary.structural_changes.map(
				( change: ChangeSummaryStructuralChange, idx: number ) => (
					<AutoResolvedItem
						key={ `structural-${ idx }` }
						title={ change.description }
						sub={ __(
							'Structural change applied automatically.',
							'woocommerce'
						) }
						tag="apply_core"
					/>
				)
			) }
		</section>
	);
};

/**
 * Review drawer — surfaces the change-summary diff and lets the merchant
 * pick per-conflict "Keep yours / Use core" choices, then commits via the
 * /apply endpoint.
 *
 * Hand-rolled drawer (right-side, 480px, scrim, slide animation, focus
 * trap, Escape close) rendered via `createPortal` to `document.body` so
 * the fixed-position panel isn't trapped inside the `display: none`
 * `<PluginArea scope="woocommerce-email-editor">` wrapper. The choice
 * picker is the bespoke `ChoiceCard` two-up grid (the design's two-line
 * label + hint doesn't fit `ToggleGroupControl`'s single-label API);
 * tag pills and typography are plain `<span>` / `<h*>` / `<p>` styled
 * via SCSS.
 */
export const ReviewDrawer = ( {
	postId,
	emailTitle,
	isOpen,
	onOpenChange,
}: Props ) => {
	const drawerRef = useRef< HTMLDivElement >( null );
	const previousFocusRef = useRef< HTMLElement | null >( null );

	const [ choices, setChoices ] = useState< ChoiceMap >( {} );
	const { summary, isLoading, error } = useChangeSummary( postId, isOpen );
	const { apply, isApplying } = useApplyUpdate( postId );

	// Reset choices whenever a new diff is loaded.
	useEffect( () => {
		if ( summary ) {
			setChoices( {} );
		}
	}, [ summary ] );

	// Focus management — save the previously focused element on open,
	// move focus into the panel, restore on close.
	useEffect( () => {
		let rafId1: number;
		let rafId2: number;
		if ( isOpen ) {
			const drawerElement = drawerRef.current;
			if ( drawerElement ) {
				previousFocusRef.current = drawerElement.ownerDocument
					.activeElement as HTMLElement;
				rafId1 = requestAnimationFrame( () => {
					rafId2 = requestAnimationFrame( () => {
						drawerElement.focus();
					} );
				} );
			}
		} else if ( previousFocusRef.current?.isConnected ) {
			previousFocusRef.current.focus();
		}
		return () => {
			cancelAnimationFrame( rafId1 );
			cancelAnimationFrame( rafId2 );
		};
	}, [ isOpen ] );

	// Escape closes; Tab/Shift+Tab traps inside the drawer.
	useEffect( () => {
		const handleKeyDown = ( event: KeyboardEvent ) => {
			if ( ! isOpen ) {
				return;
			}
			if ( event.key === 'Escape' ) {
				onOpenChange( false );
				return;
			}
			if ( event.key === 'Tab' ) {
				const drawerElement = drawerRef.current;
				if ( ! drawerElement ) {
					return;
				}
				const focusable = drawerElement.querySelectorAll(
					'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]):not([disabled])'
				);
				if ( focusable.length === 0 ) {
					return;
				}
				const first = focusable[ 0 ] as HTMLElement;
				const last = focusable[ focusable.length - 1 ] as HTMLElement;
				const active = drawerElement.ownerDocument
					.activeElement as HTMLElement;
				if ( event.shiftKey ) {
					if ( active === first || active === drawerElement ) {
						event.preventDefault();
						last?.focus();
					}
				} else if ( active === last ) {
					event.preventDefault();
					first?.focus();
				}
			}
		};
		if ( isOpen ) {
			document.addEventListener( 'keydown', handleKeyDown );
		}
		return () => {
			document.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ isOpen, onOpenChange ] );

	const setChoice = (
		path: Array< number | string >,
		decision: 'keep_yours' | 'use_core'
	) => {
		setChoices( ( prev ) => ( {
			...prev,
			[ pathKey( path ) ]: decision,
		} ) );
	};

	const handleApply = async () => {
		const choiceList: ApplyChoice[] = Object.entries( choices ).map(
			( [ key, decision ] ) => ( {
				path: JSON.parse( key ) as Array< number | string >,
				decision,
			} )
		);
		const res = await apply( choiceList );
		if ( res ) {
			onOpenChange( false );
		}
	};

	const totalChanges = summary
		? summary.copy_changes.length +
		  summary.added_blocks.length +
		  summary.removed_blocks.length +
		  summary.structural_changes.length
		: 0;

	const subtitle = sprintf(
		/* translators: 1: email name; 2: WooCommerce version; 3: number of changes. */
		_n(
			'%1$s · WooCommerce %2$s · %3$d change',
			'%1$s · WooCommerce %2$s · %3$d changes',
			totalChanges,
			'woocommerce'
		),
		emailTitle,
		summary?.version_to ?? '',
		totalChanges
	);

	const applyLabel = sprintf(
		/* translators: %d: total number of changes that will be applied. */
		__( 'Apply (%d)', 'woocommerce' ),
		totalChanges
	);

	const applyDisabled =
		isApplying ||
		isLoading ||
		! summary ||
		summary.is_fallback ||
		totalChanges === 0;

	return createPortal(
		<>
			<div
				className="woocommerce-review-drawer__overlay"
				onClick={ () => onOpenChange( false ) }
				role="presentation"
				style={ { display: isOpen ? 'block' : 'none' } }
				aria-hidden={ ! isOpen }
			/>
			<div className="woocommerce-review-drawer">
				<aside
					ref={ drawerRef }
					className={ [
						'woocommerce-review-drawer__panel',
						isOpen ? 'is-open' : 'is-closed',
					].join( ' ' ) }
					role="dialog"
					aria-modal="true"
					aria-labelledby="woocommerce-review-drawer-title"
					aria-hidden={ ! isOpen }
					tabIndex={ -1 }
				>
					<header className="woocommerce-review-drawer__header">
						<div className="woocommerce-review-drawer__h-stack">
							<h2
								id="woocommerce-review-drawer-title"
								className="woocommerce-review-drawer__title"
							>
								{ __(
									'Review template update',
									'woocommerce'
								) }
							</h2>
							<p className="woocommerce-review-drawer__subtitle">
								{ subtitle }
							</p>
						</div>
						<Button
							icon={ closeSmall }
							label={ __( 'Close', 'woocommerce' ) }
							onClick={ () => onOpenChange( false ) }
							className="woocommerce-review-drawer__close"
						/>
					</header>

					<div className="woocommerce-review-drawer__body">
						{ isLoading && (
							<div
								role="status"
								aria-live="polite"
								aria-label={ __(
									'Loading diff',
									'woocommerce'
								) }
								className="woocommerce-review-drawer__status"
							>
								<Spinner />
							</div>
						) }

						{ error && (
							<div
								role="alert"
								className="woocommerce-review-drawer__status"
							>
								{ __(
									'Could not load the change summary.',
									'woocommerce'
								) }
							</div>
						) }

						{ summary && summary.is_fallback && (
							<div className="woocommerce-review-drawer__status">
								{ summary.summary_lines[ 0 ] ??
									__(
										'Template updated — see release notes.',
										'woocommerce'
									) }
							</div>
						) }

						{ summary && ! summary.is_fallback && (
							<>
								<ConflictsGroup
									conflicts={ summary.copy_changes.filter(
										( cc ) => ! cc.auto_resolvable
									) }
									choices={ choices }
									onChoose={ setChoice }
								/>
								<AutoResolvedGroup
									summary={ summary }
									autoResolvedCopyChanges={ summary.copy_changes.filter(
										( cc ) => cc.auto_resolvable === true
									) }
								/>
							</>
						) }
					</div>

					<footer className="woocommerce-review-drawer__footer">
						<p className="woocommerce-review-drawer__foot-note">
							{ __(
								'Revision recorded for rollback.',
								'woocommerce'
							) }
						</p>
						<div className="woocommerce-review-drawer__footer-actions">
							<Button
								variant="tertiary"
								onClick={ () => onOpenChange( false ) }
								disabled={ isApplying }
								__next40pxDefaultSize
							>
								{ __( 'Cancel', 'woocommerce' ) }
							</Button>
							<Button
								variant="primary"
								onClick={ () => {
									void handleApply();
								} }
								disabled={ applyDisabled }
								isBusy={ isApplying }
								__next40pxDefaultSize
							>
								{ applyLabel }
							</Button>
						</div>
					</footer>
				</aside>
			</div>
		</>,
		document.body
	);
};
