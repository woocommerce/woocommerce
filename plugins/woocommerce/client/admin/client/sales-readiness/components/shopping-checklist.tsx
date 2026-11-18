/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
/* eslint-disable @wordpress/use-recommended-components -- The design asks for these @wordpress/ui components; this chunk bundles the package. */
import { Badge, Button, Collapsible, Text } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ShoppingView } from '../types';
import { Row } from './check-row';

type Checklist = ShoppingView[ 'checklist' ];

/**
 * Props that make a @wordpress/ui Button a link: an anchor that keeps the link role. This
 * version of the package has no ButtonLink.
 */
export const asLink = ( href: string ) => ( {
	nativeButton: false,
	role: 'link',
	// The Button renders the text into it.
	// eslint-disable-next-line jsx-a11y/anchor-has-content
	render: <a href={ href } />,
} );

/**
 * The open setup steps, required ones first, each with a button to where to fix it, then the
 * completed ones behind "Show N completed steps".
 */
export const ShoppingChecklist = ( {
	checklist,
}: {
	checklist: Checklist;
} ) => {
	const [ open, setOpen ] = useState( false );
	const issue = __( 'Issue:', 'woocommerce' );

	return (
		<div className="wc-ai-shopping-checklist">
			{ /* Drawn at the end of the card's title line. A summary field would do that, but this DataForm
			     version supports one only on a card that collapses. */ }
			{ checklist.badge && (
				<Badge
					intent={ checklist.badge.intent }
					className="wc-ai-shopping-checklist__badge"
				>
					{ checklist.badge.text }
				</Badge>
			) }
			{ checklist.steps.length > 0 && (
				<ul className="wc-sales-readiness-list">
					{ checklist.steps.map( ( step ) => (
						<Row
							key={ step.id }
							title={ step.title }
							badge={ step.badge }
							srState={ issue }
							action={
								step.link && (
									<Button
										variant="outline"
										size="compact"
										{ ...asLink( step.link.href ) }
										aria-label={ step.link.sr || undefined }
									>
										{ step.link.text }
									</Button>
								)
							}
						>
							{ step.lines.map( ( line ) => (
								<Text
									key={ line }
									variant="body-sm"
									className="wc-sales-readiness__muted"
								>
									{ line }
								</Text>
							) ) }
						</Row>
					) ) }
				</ul>
			) }
			{ checklist.done.length > 0 && (
				<Collapsible.Root
					open={ open }
					onOpenChange={ setOpen }
					className="wc-ai-shopping-fold"
				>
					<Collapsible.Panel>
						<ul className="wc-sales-readiness-list">
							{ checklist.done.map( ( step ) => (
								<Row
									key={ step.id }
									done
									title={ step.title }
									srState={ __( 'Passed:', 'woocommerce' ) }
								>
									<Text
										variant="body-sm"
										className="wc-sales-readiness__muted"
									>
										{ step.text }
									</Text>
								</Row>
							) ) }
						</ul>
					</Collapsible.Panel>
					<Collapsible.Trigger
						render={ <Button variant="minimal" size="compact" /> }
					>
						{ open ? checklist.hide_done : checklist.show_done }
					</Collapsible.Trigger>
				</Collapsible.Root>
			) }
		</div>
	);
};
