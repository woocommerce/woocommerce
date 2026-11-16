/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { Badge, Card, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { FixFirst as FixFirstData } from '../types';
import { Row, RowLink } from './check-row';

/**
 * The Fix first items under a heading per tier: what each group of issues stops. A product item's
 * line starts with its share of sales.
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
					<Fragment key={ item.id }>
						{ heading && (
							<li className="wc-sales-readiness-tier">
								<Text variant="heading-sm" aria-hidden="true">
									{ heading }
								</Text>
								{ item.badge && (
									<Badge intent={ item.badge.intent }>
										{ item.badge.text }
									</Badge>
								) }
							</li>
						) }
						<Row
							title={ item.title }
							action={
								item.link && <RowLink link={ item.link } />
							}
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
						</Row>
					</Fragment>
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
			<Card.Title className="wc-sales-readiness-card__title">
				{ fixFirst.title }
			</Card.Title>
		</Card.Header>
		<Card.Content>
			<Text variant="body-md" className="wc-sales-readiness__muted">
				{ fixFirst.ranking }
			</Text>
			<FixFirstList items={ fixFirst.items } />
		</Card.Content>
	</Card.Root>
);
