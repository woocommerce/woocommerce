/**
 * External dependencies
 */
import { Badge, Icon, Link, Stack, Text, VisuallyHidden } from '@wordpress/ui';
import { check } from '@wordpress/icons';
import { Path, SVG } from '@wordpress/primitives';
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

// The swatch icon, a dashed circle, from the WordPress icons package from version 18 on.
// This package pins an older version that doesn't have it.
const dashedCircle = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.5"
	>
		<Path d="M11.0537 4.81202C11.6819 4.72933 12.3181 4.72933 12.9463 4.81202M14.7744 5.30187C15.3598 5.54434 15.9109 5.86249 16.4135 6.24819M17.7518 7.58648C18.1375 8.08914 18.4557 8.64019 18.6981 9.22555M19.188 11.0537C19.2707 11.6819 19.2707 12.3181 19.188 12.9463M18.6981 14.7744C18.4557 15.3598 18.1375 15.9109 17.7518 16.4135M16.4135 17.7518C15.9109 18.1375 15.3598 18.4557 14.7744 18.6981M12.9463 19.188C12.3181 19.2707 11.6819 19.2707 11.0537 19.188M9.22555 18.6981C8.64019 18.4557 8.08914 18.1375 7.58648 17.7518M6.24819 16.4135C5.86249 15.9109 5.54434 15.3598 5.30187 14.7744M4.81202 12.9463C4.72933 12.3181 4.72933 11.6819 4.81202 11.0537M5.30187 9.22555C5.54434 8.64019 5.86249 8.08914 6.24819 7.58648M7.58648 6.24819C8.08914 5.86249 8.64019 5.54434 9.22555 5.30187" />
	</SVG>
);

/**
 * The marker before a row: a dashed circle for something to do, a check for something passed.
 */
export const RowMarker = ( {
	done,
	muted = false,
}: {
	done: boolean;
	muted?: boolean;
} ) => (
	<Icon
		icon={ done ? check : dashedCircle }
		size={ 24 }
		className={
			'wc-sales-readiness-row__marker' +
			( muted ? ' wc-sales-readiness-row__marker--muted' : '' )
		}
	/>
);

interface RowProps {
	done?: boolean;
	/** A grey marker, for a check that couldn't run. */
	muted?: boolean;
	title: string;
	badge?: ViewBadge | null;
	/** Where to fix it, at the end of the row. */
	action?: ReactNode;
	children?: ReactNode;
	srState?: string;
}

/**
 * A row's frame: marker, title with its badge, whatever goes under the title, then the action.
 */
export const Row = ( {
	done = false,
	muted = false,
	title,
	badge,
	action,
	children,
	srState,
}: RowProps ) => (
	<Stack
		direction="row"
		gap="sm"
		align="flex-start"
		render={ <li /> }
		className="wc-sales-readiness-row"
	>
		<RowMarker done={ done } muted={ muted } />
		<Stack direction="column" className="wc-sales-readiness-row__body">
			<Text variant="body-md" className="wc-sales-readiness-row__title">
				{ srState && <VisuallyHidden>{ srState } </VisuallyHidden> }
				{ title }
				{ badge && (
					<Badge intent={ badge.intent }>{ badge.text }</Badge>
				) }
			</Text>
			{ children }
		</Stack>
		{ action && (
			<div className="wc-sales-readiness-row__action">{ action }</div>
		) }
	</Stack>
);

/**
 * One check. Failing: what to do, why it matters and what was found, the fix line when there is
 * one, and where to fix it. Passed: its title and why it matters.
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
			muted={ row.state === 'unknown' }
			title={ row.title }
			srState={ states[ row.state ] }
			badge={ row.badge }
			action={ row.link && <RowLink link={ row.link } /> }
		>
			{ row.text && (
				<Text variant="body-sm" className="wc-sales-readiness__muted">
					{ row.text }
				</Text>
			) }
			{ row.fix && (
				<Text variant="body-sm" className="wc-sales-readiness-row__fix">
					{ row.fix }
				</Text>
			) }
			{ row.seen_at && (
				<Text variant="body-sm" className="wc-sales-readiness__muted">
					{ __( 'Seen on:', 'woocommerce' ) }{ ' ' }
					<Link href={ row.seen_at.href }>{ row.seen_at.text }</Link>
				</Text>
			) }
		</Row>
	);
};
