/**
 * External dependencies
 */
import { Badge, Card, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { FixFirst as FixFirstData } from '../types';
import { Row, RowLink } from './check-row';

type FixFirstItem = FixFirstData[ 'items' ][ number ];

type TierGroup = {
	tier: string;
	badge: FixFirstItem[ 'badge' ];
	items: FixFirstItem[];
};

function groupByTier( items: FixFirstData[ 'items' ] ): TierGroup[] {
	const groups: TierGroup[] = [];
	for ( const item of items ) {
		const last = groups[ groups.length - 1 ];
		if ( ! last || last.tier !== item.tier ) {
			groups.push( {
				tier: item.tier,
				badge: item.badge,
				items: [ item ],
			} );
		} else {
			last.items.push( item );
		}
	}
	return groups;
}

/**
 * The Fix first items under a heading per tier: what each group of issues stops. A product item's
 * line starts with its share of sales.
 */
export const FixFirstList = ( {
	items,
}: {
	items: FixFirstData[ 'items' ];
} ) => (
	<div className="wc-sales-readiness-fix-first-groups">
		{ groupByTier( items ).map( ( group ) => (
			<div key={ group.tier } className="wc-sales-readiness-tier-group">
				<div className="wc-sales-readiness-tier">
					<Text
						variant="heading-sm"
						className="wc-sales-readiness-card__label"
					>
						{ group.tier }
					</Text>
					{ group.badge && (
						<Badge intent={ group.badge.intent }>
							{ group.badge.text }
						</Badge>
					) }
				</div>
				<ol className="wc-sales-readiness-list">
					{ group.items.map( ( item ) => (
						<Row
							key={ item.id }
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
					) ) }
				</ol>
			</div>
		) ) }
	</div>
);

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
