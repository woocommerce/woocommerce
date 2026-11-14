/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import type { ReactNode } from 'react';
import { Badge, Card, Collapsible, CollapsibleCard, Text } from '@wordpress/ui';
import { Button } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Declared, Section } from '../types';
import { CheckRow, Row, RowLink } from './check-row';
import { trackReadiness } from '../tracks';

/**
 * "Show N more checks" under what it folds, and what to say once open.
 */
export const Fold = ( {
	label,
	hideLabel,
	section,
	children,
}: {
	label: string;
	hideLabel: string;
	section: string;
	children: ReactNode;
} ) => {
	const [ open, setOpen ] = useState( false );
	return (
		<Collapsible.Root
			open={ open }
			onOpenChange={ ( next: boolean ) => {
				trackReadiness( 'fold_toggle', { section, open: next } );
				setOpen( next );
			} }
			className="wc-sales-readiness-fold"
		>
			<Collapsible.Panel>{ children }</Collapsible.Panel>
			<Collapsible.Trigger
				render={ <Button variant="tertiary" size="compact" /> }
			>
				{ open ? hideLabel : label }
			</Collapsible.Trigger>
		</Collapsible.Root>
	);
};

/**
 * "Show 9 more checks", plus how many of them are issues ("Show 9 more checks, 3 issues"), so the
 * card's issue count adds up with what is open.
 */
const foldLabel = ( rows: Section[ 'rows' ] ) => {
	const checks = sprintf(
		/* translators: %d: number of checks. */
		_n(
			'Show %d more check',
			'Show %d more checks',
			rows.length,
			'woocommerce'
		),
		rows.length
	);
	const issues = rows.filter(
		( row ) => row.state === 'open' || row.state === 'required'
	).length;
	if ( issues === 0 ) {
		return checks;
	}
	return sprintf(
		/* translators: 1: "Show 9 more checks", 2: "3 issues". */
		__( '%1$s, %2$s', 'woocommerce' ),
		checks,
		sprintf(
			/* translators: %d: number of issues. */
			_n( '%d issue', '%d issues', issues, 'woocommerce' ),
			issues
		)
	);
};

/** How many checks a question card shows before the rest fold. */
const SHOWN = 2;

/**
 * A question card: its badge, a note, how many products each channel can list, the first
 * two checks, open ones first, then the rest behind a fold. A card where every check passed
 * starts closed, and opens on its header to list them.
 */
export const SectionCard = ( { section }: { section: Section } ) => {
	// Open checks first, then passed ones; the first SHOWN rows stay open, the rest fold.
	const checks = [ ...section.rows, ...section.folded ];
	const passed =
		checks.length > 0 && checks.every( ( row ) => row.state === 'pass' );
	const shown = checks.slice( 0, SHOWN );
	const more = checks.slice( SHOWN );
	const head = (
		<div className="wc-sales-readiness-card__head">
			<Card.Title className="wc-sales-readiness-card__title">
				{ section.title }
			</Card.Title>
			<Badge intent={ section.badge.intent }>
				{ section.badge.text }
			</Badge>
		</div>
	);
	const note = section.note && (
		<Text
			variant="body-sm"
			className="wc-sales-readiness__muted wc-sales-readiness-card__description"
		>
			{ section.note }
		</Text>
	);
	const list = ( rows: Section[ 'rows' ] ) => (
		<ul className="wc-sales-readiness-list">
			{ rows.map( ( row ) => (
				<CheckRow key={ row.id } row={ row } section={ section.id } />
			) ) }
		</ul>
	);

	if ( passed ) {
		return (
			<CollapsibleCard.Root
				className="wc-sales-readiness-card"
				id={ 'wc-sales-readiness-' + section.id }
				onOpenChange={ ( open: boolean ) =>
					trackReadiness( 'fold_toggle', {
						section: section.id,
						open,
					} )
				}
			>
				<CollapsibleCard.Header>{ head }</CollapsibleCard.Header>
				<CollapsibleCard.Content>
					{ note }
					{ list( checks ) }
				</CollapsibleCard.Content>
			</CollapsibleCard.Root>
		);
	}

	return (
		<Card.Root
			className="wc-sales-readiness-card"
			id={ 'wc-sales-readiness-' + section.id }
		>
			<Card.Header>{ head }</Card.Header>
			<Card.Content>
				{ note }
				{ section.channels.length > 0 && (
					<ul className="wc-sales-readiness-channels">
						{ section.channels.map( ( channel ) => (
							<li key={ channel.name }>
								<Text
									variant="body-sm"
									className="wc-sales-readiness-channels__name"
								>
									{ channel.name }
								</Text>
								<Text variant="body-md">
									<strong className="wc-sales-readiness-channels__count">
										{ channel.listable }
									</strong>{ ' ' }
									<span className="wc-sales-readiness__muted">
										{ channel.of }
									</span>
								</Text>
							</li>
						) ) }
					</ul>
				) }
				{ section.empty && (
					<div className="wc-sales-readiness-empty">
						<Text
							variant="body-md"
							className={
								section.empty.text
									? 'wc-sales-readiness-empty__title'
									: undefined
							}
						>
							{ section.empty.title }
						</Text>
						{ section.empty.text && (
							<Text
								variant="body-sm"
								className="wc-sales-readiness__muted"
							>
								{ section.empty.text }
							</Text>
						) }
						{ section.empty.link && (
							<div className="wc-sales-readiness-row__action">
								<RowLink link={ section.empty.link } />
							</div>
						) }
					</div>
				) }
				{ shown.length > 0 && list( shown ) }
				{ more.length > 0 && (
					<Fold
						section={ section.id }
						hideLabel={ __( 'Hide', 'woocommerce' ) }
						label={ foldLabel( more ) }
					>
						{ list( more ) }
					</Fold>
				) }
			</Card.Content>
		</Card.Root>
	);
};

/**
 * Protocols and files the store serves to agents: information, never pass or fail.
 */
export const DeclaredCard = ( { declared }: { declared: Declared } ) => (
	<Card.Root
		className="wc-sales-readiness-card"
		id="wc-sales-readiness-declared"
	>
		<Card.Header>
			<Card.Title className="wc-sales-readiness-card__title">
				{ declared.title }
			</Card.Title>
		</Card.Header>
		<Card.Content>
			<Text variant="body-md">{ declared.text }</Text>
			<Fold
				label={ declared.more }
				hideLabel={ __( 'Hide', 'woocommerce' ) }
				section="declared"
			>
				<ul className="wc-sales-readiness-list">
					{ declared.items.map( ( item ) => (
						<Row
							key={ item.name }
							done={ item.found }
							title={ item.name }
							srState={
								item.found
									? __( 'Found:', 'woocommerce' )
									: __( 'Not found:', 'woocommerce' )
							}
						/>
					) ) }
				</ul>
			</Fold>
		</Card.Content>
	</Card.Root>
);
