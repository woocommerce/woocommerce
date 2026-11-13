/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { Card, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import type { FixFirst as FixFirstData } from '../types';
import { trackReadiness, type Surface } from '../tracks';
import { Row, RowLink } from './check-row';

/**
 * The Fix first items under a heading per tier: what each group of issues stops. Required shows
 * once, on the first tier's heading; a product item shows its share of sales or how many products it affects.
 */
export const FixFirstList = ( {
	items,
	surface,
}: {
	items: FixFirstData[ 'items' ];
	surface: Surface;
} ) => {
	let lastTier: string | null = null;
	return (
		<ol className="wc-sales-readiness-list">
			{ items.map( ( item, index ) => {
				const heading = item.tier !== lastTier ? item.tier : null;
				lastTier = item.tier;
				return (
					<Fragment key={ item.id }>
						{ heading && (
							<li className="wc-sales-readiness-tier">
								<Text variant="heading-sm" aria-hidden="true">
									{ heading }
								</Text>
							</li>
						) }
						<Row
							title={ item.title }
							badge={ item.impact }
							action={
								item.link && (
									<RowLink
										link={ item.link }
										onClick={ () =>
											trackReadiness( 'link_click', {
												surface,
												area: 'fix_first',
												check_id: item.id,
												position: index + 1,
											} )
										}
									/>
								)
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
			<FixFirstList items={ fixFirst.items } surface="page" />
		</Card.Content>
	</Card.Root>
);
