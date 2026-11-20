/**
 * External dependencies
 */
import { Badge, Card, Text } from '@wordpress/ui';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { safeHref } from './safe-href';
import './sales-readiness.scss';
import type { ReadinessView } from './types';
import { useReadinessView } from './use-readiness-view';
import { FixFirstList } from './components/fix-first';
import { AgentConnect, type ConnectView } from './components/agent-connect';
import { ErrorNotice } from './components/error-notice';
import { RunStatus } from './components/run-status';
import { trackReadiness } from './tracks';

/**
 * Whether every check on the view passed. Empty sections (no products yet) and unknown or
 * open checks must not claim full readiness.
 */
function everyCheckPassed( view: ReadinessView ): boolean {
	const rows = view.sections.flatMap( ( section ) => [
		...section.rows,
		...section.folded,
	] );
	return rows.length > 0 && rows.every( ( row ) => row.state === 'pass' );
}

/**
 * WooCommerce > Settings > AI tools, the Overview: the WordPress.com connection card, the AI shopping
 * card, then the Sales readiness card with Fix first and a link to every check.
 */
export default function SalesReadinessOverview( {
	initialView = null,
	connect = null,
}: {
	initialView?: ReadinessView | null;
	connect?: ConnectView | null;
} ) {
	const { view, error, reload, running, runCheck } = useReadinessView(
		'overview',
		initialView
	);

	// No spinner while the view loads, as on the AI shopping health page.
	if ( ! view ) {
		if ( ! error && ! connect ) {
			return null;
		}
		return (
			<div className="wc-sales-readiness wc-sales-readiness--overview">
				{ error && <ErrorNotice message={ error } reload={ reload } /> }
				{ connect && <AgentConnect connect={ connect } /> }
			</div>
		);
	}
	const { shopping, view_all: viewAll } = view.overview;
	// A rejected URL is not a link: the button stays, disabled, and does not record a click.
	const linkProps = ( href: string | undefined, area: string ) =>
		href
			? {
					href,
					onClick: () =>
						trackReadiness( 'link_clicked', {
							surface: 'overview',
							area,
						} ),
				}
			: { disabled: true };

	return (
		<div className="wc-sales-readiness wc-sales-readiness--overview">
			{ error && <ErrorNotice message={ error } reload={ reload } /> }
			{ connect && <AgentConnect connect={ connect } /> }
			<Card.Root className="wc-sales-readiness-card">
				<Card.Header>
					<div className="wc-sales-readiness-card__head">
						<Card.Title className="wc-sales-readiness-card__title">
							{ shopping.title }
						</Card.Title>
						{ shopping.badge && (
							<Badge intent={ shopping.badge.intent }>
								{ shopping.badge.text }
							</Badge>
						) }
					</div>
				</Card.Header>
				<Card.Content>
					<Text
						variant="body-md"
						className="wc-sales-readiness__muted"
					>
						{ shopping.text }
					</Text>
					<div className="wc-sales-readiness-card__actions">
						<Button
							variant="secondary"
							size="compact"
							{ ...linkProps(
								safeHref( shopping.button.href ),
								'shopping'
							) }
						>
							{ shopping.button.text }
						</Button>
					</div>
				</Card.Content>
			</Card.Root>
			<Card.Root className="wc-sales-readiness-card">
				<Card.Header>
					<Card.Title className="wc-sales-readiness-card__title">
						{ view.title }
					</Card.Title>
				</Card.Header>
				<Card.Content>
					<Text
						variant="body-md"
						className="wc-sales-readiness__muted"
					>
						{ view.description }
						{ view.overview.based_on &&
							' ' + view.overview.based_on }
					</Text>
					{ view.intro ? (
						<>
							<Text
								variant="body-sm"
								className="wc-sales-readiness__muted"
							>
								{ view.last_checked }
							</Text>
							<RunStatus run={ view.run } />
							<div className="wc-sales-readiness-card__actions">
								<Button
									variant="secondary"
									size="compact"
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
							{ view.fix_first?.items.length ? (
								<FixFirstList
									items={ view.fix_first.items }
									surface="overview"
								/>
							) : (
								<Text variant="body-md">
									{ everyCheckPassed( view )
										? __(
												'Nothing to fix right now. Your store has everything AI assistants need to find and shop your products.',
												'woocommerce'
											)
										: __(
												'Nothing ranked to fix right now. View all checks for the full results.',
												'woocommerce'
											) }
								</Text>
							) }
							<div className="wc-sales-readiness-card__actions wc-sales-readiness-card__actions--link">
								<Button
									variant="tertiary"
									size="compact"
									{ ...linkProps(
										safeHref( viewAll.href ),
										'view_all'
									) }
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
