/**
 * External dependencies
 */
import { createPortal, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button, RadioControl, Spinner } from '@wordpress/components';
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
	onClose: () => void;
}

type ChoiceMap = Record< string, 'keep_yours' | 'use_core' >;

/** Stable string key for a path array, used as the choice-map key. */
function pathKey( path: Array< number | string > ): string {
	return JSON.stringify( path );
}

const DrawerHeader = ( {
	emailTitle,
	versionTo,
	totalChanges,
	onClose,
}: {
	emailTitle: string;
	versionTo: string;
	totalChanges: number;
	onClose: () => void;
} ) => {
	const subtitle = sprintf(
		/* translators: 1: email name; 2: WooCommerce version; 3: number of changes. */
		_n(
			'%1$s · WooCommerce %2$s · %3$d change',
			'%1$s · WooCommerce %2$s · %3$d changes',
			totalChanges,
			'woocommerce'
		),
		emailTitle,
		versionTo,
		totalChanges
	);

	return (
		<div className="woocommerce-review-drawer__header">
			<div className="woocommerce-review-drawer__header-text">
				<h2
					id="woocommerce-review-drawer-title"
					className="woocommerce-review-drawer__title"
				>
					{ __( 'Review template update', 'woocommerce' ) }
				</h2>
				<p className="woocommerce-review-drawer__subtitle">
					{ subtitle }
				</p>
			</div>
			<Button
				icon={ closeSmall }
				label={ __( 'Close', 'woocommerce' ) }
				onClick={ onClose }
				className="woocommerce-review-drawer__close"
			/>
		</div>
	);
};

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
			className="woocommerce-review-drawer__group woocommerce-review-drawer__group--conflicts"
			aria-labelledby="woocommerce-review-drawer-conflicts-heading"
		>
			<h3
				id="woocommerce-review-drawer-conflicts-heading"
				className="woocommerce-review-drawer__group-heading"
			>
				{ heading }
			</h3>
			<ul className="woocommerce-review-drawer__list">
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
						<li
							key={ key }
							className="woocommerce-review-drawer__row woocommerce-review-drawer__row--conflict"
						>
							<div className="woocommerce-review-drawer__row-title">
								{ blockTitle }
							</div>
							<div className="woocommerce-review-drawer__diff">
								<div className="woocommerce-review-drawer__diff-row woocommerce-review-drawer__diff-row--minus">
									{ conflict.before }
								</div>
								<div className="woocommerce-review-drawer__diff-row woocommerce-review-drawer__diff-row--plus">
									{ conflict.after }
								</div>
							</div>
							<RadioControl
								className="woocommerce-review-drawer__choice"
								selected={ decision }
								options={ [
									{
										label: __(
											'Keep yours',
											'woocommerce'
										),
										value: 'keep_yours',
									},
									{
										label: __( 'Use core', 'woocommerce' ),
										value: 'use_core',
									},
								] }
								onChange={ ( value ) => {
									if (
										value === 'keep_yours' ||
										value === 'use_core'
									) {
										onChoose( conflict.path, value );
									}
								} }
							/>
						</li>
					);
				} ) }
			</ul>
		</section>
	);
};

const AutoResolvedRow = ( {
	label,
	tag,
}: {
	label: string;
	tag: 'apply_core' | 'keep_yours';
} ) => {
	const tagLabel =
		tag === 'apply_core'
			? __( 'Apply core', 'woocommerce' )
			: __( 'Keep yours', 'woocommerce' );

	return (
		<li
			className={ [
				'woocommerce-review-drawer__row',
				'woocommerce-review-drawer__row--auto',
				`woocommerce-review-drawer__row--${ tag }`,
			].join( ' ' ) }
		>
			<span className="woocommerce-review-drawer__row-title">
				{ label }
			</span>
			<span
				className={ [
					'woocommerce-review-drawer__tag',
					`woocommerce-review-drawer__tag--${ tag }`,
				].join( ' ' ) }
			>
				{ tagLabel }
			</span>
		</li>
	);
};

const AutoResolvedGroup = ( { summary }: { summary: ChangeSummary } ) => {
	const total =
		summary.added_blocks.length +
		summary.removed_blocks.length +
		summary.structural_changes.length;

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
			className="woocommerce-review-drawer__group woocommerce-review-drawer__group--auto-resolved"
			aria-labelledby="woocommerce-review-drawer-auto-heading"
		>
			<h3
				id="woocommerce-review-drawer-auto-heading"
				className="woocommerce-review-drawer__group-heading"
			>
				{ heading }
			</h3>
			<ul className="woocommerce-review-drawer__list">
				{ summary.added_blocks.map( ( entry ) => (
					<AutoResolvedRow
						key={ `added-${ pathKey( entry.path ) }` }
						label={ entry.label }
						tag="apply_core"
					/>
				) ) }
				{ summary.removed_blocks.map( ( entry ) => (
					<AutoResolvedRow
						key={ `removed-${ pathKey( entry.path ) }` }
						label={ entry.label }
						tag="keep_yours"
					/>
				) ) }
				{ summary.structural_changes.map(
					( change: ChangeSummaryStructuralChange, idx: number ) => (
						<AutoResolvedRow
							key={ `structural-${ idx }` }
							label={ change.description }
							tag="apply_core"
						/>
					)
				) }
			</ul>
		</section>
	);
};

const DrawerFooter = ( {
	totalChanges,
	isApplying,
	disabled,
	onApply,
	onCancel,
}: {
	totalChanges: number;
	isApplying: boolean;
	disabled: boolean;
	onApply: () => void;
	onCancel: () => void;
} ) => {
	const applyLabel = sprintf(
		/* translators: %d: total number of changes that will be applied. */
		__( 'Apply (%d)', 'woocommerce' ),
		totalChanges
	);

	return (
		<div className="woocommerce-review-drawer__footer">
			<span className="woocommerce-review-drawer__footer-note">
				{ __( 'Revision recorded for rollback.', 'woocommerce' ) }
			</span>
			<div className="woocommerce-review-drawer__footer-actions">
				<Button
					variant="tertiary"
					onClick={ onCancel }
					disabled={ isApplying }
					__next40pxDefaultSize
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ onApply }
					disabled={ disabled }
					isBusy={ isApplying }
					__next40pxDefaultSize
				>
					{ applyLabel }
				</Button>
			</div>
		</div>
	);
};

/**
 * Review drawer — surfaces the change-summary diff and lets the merchant
 * pick per-conflict "Keep yours / Use core" choices, then commits via the
 * /apply endpoint.
 *
 * Hand-rolled drawer (right-side, 480px, scrim, slide animation, focus
 * trap) because `__experimentalDrawer` from `@wordpress/components` isn't
 * available in the version used by `wp-admin-scripts`. Mirrors the
 * accessibility pattern in
 * `client/wp-admin-scripts/fulfillments/components/user-interface/fulfillment-drawer/`.
 *
 * RSM-141 wires this up to the editor banner's "Review" button. For now,
 * the trigger lives in `review-update-plugin.tsx` (an interim button in
 * the email actions slot) so the drawer is end-to-end testable without
 * RSM-141.
 */
export const ReviewDrawer = ( {
	postId,
	emailTitle,
	isOpen,
	onClose,
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

	// Focus management — see fulfillment-drawer.tsx for the established
	// pattern. Save the previously focused element on open, restore on close.
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
				onClose();
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
	}, [ isOpen, onClose ] );

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
			onClose();
		}
	};

	const totalChanges = summary
		? summary.copy_changes.length +
		  summary.added_blocks.length +
		  summary.removed_blocks.length +
		  summary.structural_changes.length
		: 0;

	// Render via portal to document.body so the fixed-position panel isn't
	// trapped inside the `display: none` plugin-area container that
	// `<PluginArea scope="woocommerce-email-editor" />` mounts into.
	return createPortal(
		<>
			<div
				className="woocommerce-review-drawer__backdrop"
				onClick={ onClose }
				role="presentation"
				style={ { display: isOpen ? 'block' : 'none' } }
				aria-hidden={ ! isOpen }
			/>
			<div className="woocommerce-review-drawer">
				<div
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
					<DrawerHeader
						emailTitle={ emailTitle }
						versionTo={ summary?.version_to ?? '' }
						totalChanges={ totalChanges }
						onClose={ onClose }
					/>

					<div className="woocommerce-review-drawer__body">
						{ isLoading && (
							<div
								className="woocommerce-review-drawer__loading"
								role="status"
								aria-live="polite"
							>
								<Spinner />
								<span className="screen-reader-text">
									{ __( 'Loading diff…', 'woocommerce' ) }
								</span>
							</div>
						) }

						{ error && (
							<div
								className="woocommerce-review-drawer__error"
								role="alert"
							>
								{ __(
									'Could not load the change summary.',
									'woocommerce'
								) }
							</div>
						) }

						{ summary && summary.is_fallback && (
							<div className="woocommerce-review-drawer__fallback">
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
									conflicts={ summary.copy_changes }
									choices={ choices }
									onChoose={ setChoice }
								/>
								<AutoResolvedGroup summary={ summary } />
							</>
						) }
					</div>

					<DrawerFooter
						totalChanges={ totalChanges }
						isApplying={ isApplying }
						disabled={
							isApplying ||
							isLoading ||
							! summary ||
							summary.is_fallback ||
							totalChanges === 0
						}
						onApply={ () => {
							void handleApply();
						} }
						onCancel={ onClose }
					/>
				</div>
			</div>
		</>,
		document.body
	);
};
