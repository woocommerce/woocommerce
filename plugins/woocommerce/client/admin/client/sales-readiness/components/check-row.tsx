/**
 * External dependencies
 */
import { Badge, Link, Text } from '@wordpress/ui';
import { Icon, check } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import type { CheckRow as CheckRowData, ViewBadge, ViewLink } from '../types';

/**
 * A line that is a link when it has a URL, plain text otherwise.
 */
export const RowLink = ( { link }: { link: ViewLink } ) =>
	link.href ? (
		<Link href={ link.href }>{ link.text }</Link>
	) : (
		<Text variant="body-sm" className="wc-sales-readiness__muted">
			{ link.text }
		</Text>
	);

/**
 * The marker before a row: a dashed circle for something to do, a check for something passed.
 */
export const RowMarker = ( { done }: { done: boolean } ) => (
	<span
		className={
			'wc-sales-readiness-row__marker' +
			( done ? ' wc-sales-readiness-row__marker--done' : '' )
		}
		aria-hidden="true"
	>
		{ done && <Icon icon={ check } size={ 20 } /> }
	</span>
);

interface RowProps {
	done?: boolean;
	title: string;
	badge?: ViewBadge | null;
	children?: ReactNode;
	srState?: string;
}

/**
 * A row's frame: marker, title with its badge, then whatever goes under the title.
 */
export const Row = ( {
	done = false,
	title,
	badge,
	children,
	srState,
}: RowProps ) => (
	<li className="wc-sales-readiness-row">
		<RowMarker done={ done } />
		<div className="wc-sales-readiness-row__body">
			<div className="wc-sales-readiness-row__head">
				<Text
					variant="body-md"
					className={
						'wc-sales-readiness-row__title' +
						( done ? ' wc-sales-readiness-row__title--done' : '' )
					}
				>
					{ srState && (
						<span className="screen-reader-text">{ srState } </span>
					) }
					{ title }
				</Text>
				{ badge && (
					<Badge intent={ badge.intent }>{ badge.text }</Badge>
				) }
			</div>
			{ children }
		</div>
	</li>
);

/**
 * One check: its state, what it affects, why it matters, what to do and where.
 */
export const CheckRow = ( { row }: { row: CheckRowData } ) => {
	const states: Record< CheckRowData[ 'state' ], string > = {
		open: __( 'Issue:', 'woocommerce' ),
		required: __( 'Issue:', 'woocommerce' ),
		unknown: __( "Couldn't check:", 'woocommerce' ),
		pass: __( 'Passed:', 'woocommerce' ),
	};
	return (
		<Row
			done={ row.state === 'pass' }
			title={ row.title }
			srState={ states[ row.state ] }
			badge={
				row.state === 'required'
					? {
							intent: 'medium',
							text: __( 'Required', 'woocommerce' ),
						}
					: null
			}
		>
			{ row.affects && (
				<Text variant="body-sm" className="wc-sales-readiness__muted">
					<strong>{ __( 'Affects:', 'woocommerce' ) }</strong>{ ' ' }
					{ row.affects.replace( /^[^:]*:\s*/, '' ) }
				</Text>
			) }
			{ row.lines.map( ( line ) => (
				<Text
					key={ line }
					variant="body-sm"
					className="wc-sales-readiness__muted"
				>
					{ line }
				</Text>
			) ) }
			{ row.seen_at && (
				<Text variant="body-sm" className="wc-sales-readiness__muted">
					{ __( 'Seen on:', 'woocommerce' ) }{ ' ' }
					<Link href={ row.seen_at.href }>{ row.seen_at.text }</Link>
				</Text>
			) }
			{ row.link && (
				<div className="wc-sales-readiness-row__link">
					<RowLink link={ row.link } />
				</div>
			) }
		</Row>
	);
};
