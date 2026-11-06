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
 * The Fix first items under a heading per tier: what each group of issues stops.
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
		<ul className="wc-sales-readiness-list">
			{ items.map( ( item, index ) => {
				const heading = item.tier !== lastTier ? item.tier : null;
				lastTier = item.tier;
				return (
					<Fragment key={ item.id }>
						{ heading && (
							<li
								className="wc-sales-readiness-tier"
								aria-hidden="true"
							>
								<Text variant="body-sm">{ heading }</Text>
							</li>
						) }
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
								</div>
							) }
						</Row>
					</Fragment>
				);
			} ) }
		</ul>
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
			<FixFirstList items={ fixFirst.items } surface="page" />
		</Card.Content>
	</Card.Root>
);
