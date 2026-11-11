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

/**
 * "Show N more checks" under what it folds, "Hide" once open.
 */
export const Fold = ( {
	label,
	children,
}: {
	label: string;
	children: ReactNode;
} ) => {
	const [ open, setOpen ] = useState( false );
	return (
		<Collapsible.Root
			open={ open }
			onOpenChange={ setOpen }
			className="wc-sales-readiness-fold"
		>
			<Collapsible.Panel>{ children }</Collapsible.Panel>
			<Collapsible.Trigger
				render={ <Button variant="tertiary" size="compact" /> }
			>
				{ open ? __( 'Hide', 'woocommerce' ) : label }
			</Collapsible.Trigger>
		</Collapsible.Root>
	);
};

/**
 * A question card: its badge, a note, how many products each channel can list, the open
 * checks, then the passed ones behind a fold.
 */
export const SectionCard = ( { section }: { section: Section } ) => (
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
				<Text variant="body-sm" className="wc-sales-readiness__muted">
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
			{ section.rows.length > 0 && (
				<ul className="wc-sales-readiness-list">
					{ section.rows.map( ( row ) => (
						<CheckRow key={ row.title } row={ row } />
					) ) }
				</ul>
			) }
			{ section.folded.length > 0 && (
				<Fold
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
					<ul className="wc-sales-readiness-list">
						{ section.folded.map( ( row ) => (
							<CheckRow key={ row.title } row={ row } />
						) ) }
					</ul>
				</Fold>
			) }
		</Card.Content>
	</Card.Root>
);

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
			<Fold label={ declared.more }>
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
