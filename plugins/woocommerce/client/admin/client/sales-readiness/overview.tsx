/**
 * External dependencies
 */
import { Badge, Card, Text } from '@wordpress/ui';
import { Button, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './sales-readiness.scss';
import { useReadinessView } from './use-readiness-view';
import { FixFirstList } from './components/fix-first';
import { trackReadiness } from './tracks';

/**
 * WooCommerce > Settings > AI tools, the Overview: the AI shopping card, then the Sales readiness
 * card with Fix first and a link to every check.
 */
export default function SalesReadinessOverview() {
	const { view, error, running, runCheck } = useReadinessView( 'overview' );

	if ( ! view ) {
		return error ? (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		) : (
			<Spinner />
		);
	}
	const { shopping, view_all: viewAll } = view.overview;

	return (
		<div className="wc-sales-readiness wc-sales-readiness--overview">
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<Card.Root className="wc-sales-readiness-card">
				<Card.Header>
					<div className="wc-sales-readiness-card__head">
						<Card.Title>{ shopping.title }</Card.Title>
						{ shopping.badge && (
							<Badge intent={ shopping.badge.intent }>
								{ shopping.badge.text }
							</Badge>
						) }
					</div>
				</Card.Header>
				<Card.Content>
					<Text variant="body-md">{ shopping.text }</Text>
					<div className="wc-sales-readiness-card__actions">
						<Button
							variant="secondary"
							href={ shopping.button.href }
							onClick={ () =>
								trackReadiness( 'link_click', {
									surface: 'overview',
									area: 'shopping',
								} )
							}
						>
							{ shopping.button.text }
						</Button>
					</div>
				</Card.Content>
			</Card.Root>
			<Card.Root className="wc-sales-readiness-card">
				<Card.Header>
					<Card.Title>{ view.title }</Card.Title>
				</Card.Header>
				<Card.Content>
					<Text variant="body-md">{ view.description }</Text>
					{ view.checked_note && (
						<Text
							variant="body-sm"
							className="wc-sales-readiness__muted"
						>
							{ view.checked_note }
						</Text>
					) }
					{ view.intro ? (
						<>
							<Text
								variant="body-sm"
								className="wc-sales-readiness__muted"
							>
								{ view.last_checked }
							</Text>
							{ view.run.message && (
								<Text
									variant="body-sm"
									className="wc-sales-readiness__muted"
									role="status"
								>
									{ view.run.message }
								</Text>
							) }
							<div className="wc-sales-readiness-card__actions">
								<Button
									variant="secondary"
									disabled={ view.run.pending || running }
									isBusy={ running }
									onClick={ runCheck }
								>
									{ view.run.label }
								</Button>
							</div>
						</>
					) : (
						<>
							{ view.fix_first ? (
								<>
									<Text
										variant="body-md"
										className="wc-sales-readiness-overview__heading"
									>
										{ view.fix_first.title }
									</Text>
									<FixFirstList
										items={ view.fix_first.items }
										surface="overview"
									/>
								</>
							) : (
								<Text variant="body-md">
									{ __(
										'Nothing to fix first.',
										'woocommerce'
									) }
								</Text>
							) }
							<div className="wc-sales-readiness-card__actions">
								<Button
									variant="secondary"
									href={ viewAll.href }
									onClick={ () =>
										trackReadiness( 'link_click', {
											surface: 'overview',
											area: 'view_all',
										} )
									}
								>
									{ viewAll.text }
								</Button>
							</div>
						</>
					) }
				</Card.Content>
			</Card.Root>
		</div>
	);
}
