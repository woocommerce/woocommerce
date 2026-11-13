/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import type { ReactNode } from 'react';
import { Badge, Card, Collapsible, Text } from '@wordpress/ui';
import { Button } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Declared, Section } from '../types';
import { CheckRow, Row } from './check-row';
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
 * two checks, open ones first, then the rest behind a fold.
 */
export const SectionCard = ( { section }: { section: Section } ) => {
	// Open checks first, then passed ones; the first SHOWN rows stay open, the rest fold.
	// A card where every check passed says so in its badge and lists nothing.
	const checks = [ ...section.rows, ...section.folded ];
	const all = checks.some( ( row ) => row.state !== 'pass' ) ? checks : [];
	const shown = all.slice( 0, SHOWN );
	const more = all.slice( SHOWN );
	return (
		<Card.Root
			className="wc-sales-readiness-card"
			id={ 'wc-sales-readiness-' + section.id }
		>
			<Card.Header>
				<div className="wc-sales-readiness-card__head">
					<Card.Title className="wc-sales-readiness-card__title">
						{ section.title }
					</Card.Title>
					<Badge intent={ section.badge.intent }>
						{ section.badge.text }
					</Badge>
				</div>
			</Card.Header>
			<Card.Content>
				{ section.note && (
					<Text
						variant="body-sm"
						className="wc-sales-readiness__muted wc-sales-readiness-card__description"
					>
						{ section.note }
					</Text>
				) }
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
					<Text variant="body-md">{ section.empty }</Text>
				) }
				{ shown.length > 0 && (
					<ul className="wc-sales-readiness-list">
						{ shown.map( ( row ) => (
							<CheckRow
								key={ row.id }
								row={ row }
								section={ section.id }
							/>
						) ) }
					</ul>
				) }
				{ more.length > 0 && (
					<Fold
						section={ section.id }
						hideLabel={ __( 'Hide', 'woocommerce' ) }
						label={ foldLabel( more ) }
					>
						<ul className="wc-sales-readiness-list">
							{ more.map( ( row ) => (
								<CheckRow
									key={ row.id }
									row={ row }
									section={ section.id }
								/>
							) ) }
						</ul>
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
