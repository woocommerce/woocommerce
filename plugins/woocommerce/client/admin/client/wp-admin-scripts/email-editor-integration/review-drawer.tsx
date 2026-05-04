/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { Badge, Button, Drawer, Stack, Text } from '@wordpress/ui';

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
 * The label + hint sublabel comes from the design handoff — `Toggle
 * GroupControl` only fits a single label, so we keep bespoke buttons
 * with `role="radio"` for the same a11y semantics.
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
							<Text variant="heading-sm">{ blockTitle }</Text>
							<Badge intent="medium">
								{ __( 'Conflict', 'woocommerce' ) }
							</Badge>
						</div>
						<Text variant="body-sm">
							{ __(
								'Core changed this text. Pick which version to keep.',
								'woocommerce'
							) }
						</Text>
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
			<Text variant="heading-sm">{ title }</Text>
			<Badge intent={ tag === 'apply_core' ? 'informational' : 'draft' }>
				{ tag === 'apply_core'
					? __( 'Apply core', 'woocommerce' )
					: __( 'Keep yours', 'woocommerce' ) }
			</Badge>
		</div>
		<Text variant="body-sm">{ sub }</Text>
	</div>
);

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
 * Built on `@wordpress/ui`'s `Drawer` primitive: Root + Popup +
 * Header / Title / Description / CloseIcon + Content + Footer + Action.
 * The picker uses `@wordpress/components`'s `ToggleGroupControl` for
 * proper radio-group keyboard navigation; tags use `@wordpress/ui`
 * `Badge`. See `IMPLEMENTATION_GUIDE_review_drawer_v2.md` for the
 * design / token mapping.
 */
export const ReviewDrawer = ( {
	postId,
	emailTitle,
	isOpen,
	onOpenChange,
}: Props ) => {
	const [ choices, setChoices ] = useState< ChoiceMap >( {} );
	const { summary, isLoading, error } = useChangeSummary( postId, isOpen );
	const { apply, isApplying } = useApplyUpdate( postId );

	// Reset choices whenever a new diff is loaded.
	useEffect( () => {
		if ( summary ) {
			setChoices( {} );
		}
	}, [ summary ] );

	const setChoice = (
		path: Array< number | string >,
		decision: 'keep_yours' | 'use_core'
	) => {
		setChoices( ( prev ) => ( {
			...prev,
			[ pathKey( path ) ]: decision,
		} ) );
	};

	const handleApply = () => {
		const choiceList: ApplyChoice[] = Object.entries( choices ).map(
			( [ key, decision ] ) => ( {
				path: JSON.parse( key ) as Array< number | string >,
				decision,
			} )
		);
		// Fire-and-forget — `Drawer.Action` closes the drawer on click.
		// The hook surfaces success/error via the snackbar notices store.
		void apply( choiceList );
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
		isLoading || ! summary || summary.is_fallback || totalChanges === 0;

	return (
		<Drawer.Root
			open={ isOpen }
			onOpenChange={ onOpenChange }
			swipeDirection="right"
		>
			<Drawer.Popup
				size="medium"
				className="woocommerce-review-drawer__popup"
			>
				<Drawer.Header className="woocommerce-review-drawer__head">
					<Stack direction="column" gap="xs">
						<Drawer.Title className="woocommerce-review-drawer__h-title">
							{ __( 'Review template update', 'woocommerce' ) }
						</Drawer.Title>
						<Drawer.Description className="woocommerce-review-drawer__h-sub">
							{ subtitle }
						</Drawer.Description>
					</Stack>
					<Drawer.CloseIcon label={ __( 'Close', 'woocommerce' ) } />
				</Drawer.Header>

				<Drawer.Content className="woocommerce-review-drawer__body">
					{ isLoading && (
						<div
							role="status"
							aria-live="polite"
							aria-label={ __( 'Loading diff', 'woocommerce' ) }
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
								conflicts={ summary.copy_changes }
								choices={ choices }
								onChoose={ setChoice }
							/>
							<AutoResolvedGroup summary={ summary } />
						</>
					) }
				</Drawer.Content>

				<Drawer.Footer className="woocommerce-review-drawer__foot">
					<Text variant="body-sm">
						{ __(
							'Revision recorded for rollback.',
							'woocommerce'
						) }
					</Text>
					<span className="woocommerce-review-drawer__foot-spacer" />
					<Button
						variant="outline"
						tone="neutral"
						size="compact"
						disabled={ isApplying }
						onClick={ () => onOpenChange( false ) }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Drawer.Action
						variant="solid"
						tone="brand"
						size="compact"
						loading={ isApplying }
						disabled={ applyDisabled }
						onClick={ handleApply }
					>
						{ applyLabel }
					</Drawer.Action>
				</Drawer.Footer>
			</Drawer.Popup>
		</Drawer.Root>
	);
};
