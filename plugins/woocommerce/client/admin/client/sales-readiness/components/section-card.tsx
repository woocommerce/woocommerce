/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import type { ReactNode } from 'react';
import {
	Badge,
	Card,
	Collapsible,
	CollapsibleCard,
	Icon,
	Text,
	VisuallyHidden,
} from '@wordpress/ui';
import { Path, SVG } from '@wordpress/primitives';
import { Button } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Declared, Section } from '../types';
import { CheckRow, RowLink } from './check-row';
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

const declaredState = ( found: boolean | null ) => {
	if ( found === null ) {
		return __( 'Not checked yet:', 'woocommerce' );
	}
	return found
		? __( 'Found:', 'woocommerce' )
		: __( 'Not found:', 'woocommerce' );
};

// The design's marks for a declared item: a check when found, a dashed line otherwise.
const foundIcon = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.5"
	>
		<Path d="M7 12L10 15L17 8" />
	</SVG>
);
const notFoundIcon = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.5"
	>
		<Path d="M5 12H8M10.5 12H13.5M16 12H19" />
	</SVG>
);

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
			<div className="wc-sales-readiness-declared">
				{ declared.groups.map( ( group ) => (
					<div
						key={ group.title }
						className="wc-sales-readiness-declared__group"
					>
						<Text
							variant="body-sm"
							className="wc-sales-readiness-declared__title"
						>
							{ group.title }
						</Text>
						<ul className="wc-sales-readiness-declared__list">
							{ group.items.map( ( item ) => (
								<li
									key={ item.name }
									className={
										'wc-sales-readiness-declared__item' +
										( item.found === true
											? ''
											: ' wc-sales-readiness__muted' )
									}
								>
									<Icon
										icon={
											item.found === true
												? foundIcon
												: notFoundIcon
										}
										size={ 20 }
									/>
									<Text variant="body-sm">
										<VisuallyHidden>
											{ declaredState(
												item.found
											) }{ ' ' }
										</VisuallyHidden>
										{ item.name }
										{ item.found === false && (
											<span aria-hidden="true">
												{ ' ' }
												{ __(
													'· not found',
													'woocommerce'
												) }
											</span>
										) }
									</Text>
								</li>
							) ) }
						</ul>
					</div>
				) ) }
			</div>
		</CollapsibleCard.Content>
	</CollapsibleCard.Root>
);
