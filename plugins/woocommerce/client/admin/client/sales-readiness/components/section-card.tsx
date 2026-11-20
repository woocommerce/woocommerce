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
				trackReadiness( 'fold_toggle', {
					surface: 'page',
					section,
					open: next,
				} );
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
 * A question card: its badge, a note, how many products each channel can list, every check
 * that didn't pass, then the passed ones behind a fold. A card where every check passed
 * starts closed, and opens on its header to list them.
 */
export const SectionCard = ( { section }: { section: Section } ) => {
	const checks = [ ...section.rows, ...section.folded ];
	const passed =
		checks.length > 0 && checks.every( ( row ) => row.state === 'pass' );
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
			variant="body-md"
			className="wc-sales-readiness__muted wc-sales-readiness-card__description"
		>
			{ section.note }
		</Text>
	);
	const list = ( rows: Section[ 'rows' ] ) => (
		<ul className="wc-sales-readiness-list">
			{ rows.map( ( row ) => (
				<CheckRow
					key={ row.id }
					row={ row }
					section={ section.id }
					surface="page"
				/>
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
						surface: 'page',
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
									variant="heading-sm"
									className="wc-sales-readiness-card__label"
								>
									{ channel.name }
								</Text>
								<div>
									<Text variant="body-lg">
										{ channel.listable }
									</Text>{ ' ' }
									<Text
										variant="body-sm"
										className="wc-sales-readiness__muted"
									>
										{ channel.of }
									</Text>
								</div>
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
				{ section.rows.length > 0 && list( section.rows ) }
				{ section.folded.length > 0 && (
					<Fold
						section={ section.id }
						hideLabel={ __( 'Hide', 'woocommerce' ) }
						label={ sprintf(
							/* translators: %d: number of checks. */
							_n(
								'Show %d more check',
								'Show %d more checks',
								section.folded.length,
								'woocommerce'
							),
							section.folded.length
						) }
					>
						{ list( section.folded ) }
					</Fold>
				) }
			</Card.Content>
		</Card.Root>
	);
};

/**
 * Protocols and files the store serves to agents: information, never pass or fail, so the card
 * starts closed and opens on its header to list them.
 */
export const DeclaredCard = ( { declared }: { declared: Declared } ) => (
	<CollapsibleCard.Root
		className="wc-sales-readiness-card"
		id="wc-sales-readiness-declared"
		onOpenChange={ ( open: boolean ) =>
			trackReadiness( 'fold_toggle', {
				surface: 'page',
				section: 'declared',
				open,
			} )
		}
	>
		<CollapsibleCard.Header>
			<div className="wc-sales-readiness-card__head">
				<Card.Title className="wc-sales-readiness-card__title">
					{ declared.title }
				</Card.Title>
				<Badge intent={ declared.badge.intent }>
					{ declared.badge.text }
				</Badge>
			</div>
		</CollapsibleCard.Header>
		<CollapsibleCard.Content>
			<Text
				variant="body-md"
				className="wc-sales-readiness__muted wc-sales-readiness-card__description"
			>
				{ declared.text }
			</Text>
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
		</CollapsibleCard.Content>
	</CollapsibleCard.Root>
);
