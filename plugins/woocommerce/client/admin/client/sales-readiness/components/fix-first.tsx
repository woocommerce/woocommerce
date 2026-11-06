/**
 * External dependencies
 */
import { Card, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { FixFirst as FixFirstData } from '../types';
import { Row, RowLink } from './check-row';

/**
 * The Fix first items under a heading per tier: what each group of issues stops.
 */
export const FixFirstList = ( {
	items,
}: {
	items: FixFirstData[ 'items' ];
} ) => {
	let lastTier: string | null = null;
	return (
		<ol className="wc-sales-readiness-list">
			{ items.map( ( item ) => {
				const heading = item.tier !== lastTier ? item.tier : null;
				lastTier = item.tier;
				return (
					<li
						key={ item.title }
						className="wc-sales-readiness-list__group"
					>
						{ heading && (
							<Text
								variant="body-sm"
								className="wc-sales-readiness-tier"
								aria-hidden="true"
							>
								{ heading }
							</Text>
						) }
						<ul className="wc-sales-readiness-list">
							<Row
								title={ item.title }
								badge={ item.impact }
								srState={ item.tier + ':' }
							>
								{ item.why && (
									<Text
										variant="body-sm"
										className="wc-sales-readiness__muted"
									>
										{ item.why }
									</Text>
								) }
								{ item.link && (
									<div className="wc-sales-readiness-row__link">
										<RowLink link={ item.link } />
									</div>
								) }
							</Row>
						</ul>
					</li>
				);
			} ) }
		</ol>
	);
};

/**
 * The Fix first card on the Sales readiness page.
 */
export const FixFirstCard = ( { fixFirst }: { fixFirst: FixFirstData } ) => (
	<Card.Root className="wc-sales-readiness-card">
		<Card.Header>
			<Card.Title>{ fixFirst.title }</Card.Title>
		</Card.Header>
		<Card.Content>
			<Text variant="body-sm" className="wc-sales-readiness__muted">
				{ fixFirst.ranking }
			</Text>
			<FixFirstList items={ fixFirst.items } />
		</Card.Content>
	</Card.Root>
);
