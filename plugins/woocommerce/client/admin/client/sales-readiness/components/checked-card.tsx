/**
 * External dependencies
 */
/* eslint-disable @wordpress/use-recommended-components -- The design asks for these @wordpress/ui components; this chunk bundles the package. */
import { Button, Card, Link, Text } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */
import { Spinner, ToggleControl } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ReadinessView } from '../types';
import { RunStatus } from './run-status';
import { SupportDownload } from './support-download';

interface CheckedCardProps {
	view: ReadinessView;
	running: boolean;
	onRun: () => void;
	onDownloadError: ( message: string ) => void;
	onDailyChecks?: ( enabled: boolean ) => void;
	savingDailyChecks?: boolean;
}

/**
 * When the store was last checked, with Check again. Before the first check it says Not checked
 * yet and offers Check your store. While a check runs it shows a spinner and Checking your store,
 * with Check again turned off. From the first check on, Need help? offers the results to
 * download for support (built from the last check), and a switch turns the daily check off or on.
 */
export const CheckedCard = ( {
	view,
	running,
	onRun,
	onDownloadError,
	onDailyChecks,
	savingDailyChecks = false,
}: CheckedCardProps ) => {
	const checking = ( view.run.pending && ! view.run.stalled ) || running;
	return (
		<Card.Root className="wc-sales-readiness-card">
			<Card.Content>
				<div className="wc-sales-readiness-checked">
					{ checking ? (
						<div
							className="wc-sales-readiness-checked__running"
							role="status"
						>
							<Spinner />
							<Text variant="body-md">
								{ view.run.message ||
									__( 'Checking your store', 'woocommerce' ) }
							</Text>
						</div>
					) : (
						<div>
							<Text variant="body-md">{ view.last_checked }</Text>
							{ view.checked_note && (
								<Text
									variant="body-sm"
									className="wc-sales-readiness__muted"
								>
									{ view.checked_note }
								</Text>
							) }
							{ view.checked && (
								<Text
									variant="body-sm"
									className="wc-sales-readiness__muted"
								>
									{ createInterpolateElement(
										__(
											'Need help? <download>Download your results</download> and <support>contact support</support>.',
											'woocommerce'
										),
										{
											download: (
												<SupportDownload
													onError={ onDownloadError }
												/>
											),
											support: (
												<Link
													href={ view.support_url }
													openInNewTab
												/>
											),
										}
									) }
								</Text>
							) }
						</div>
					) }
					<Button
						variant="outline"
						size="compact"
						className="wc-sales-readiness-ui-button"
						disabled={ view.run.pending || running }
						onClick={ onRun }
					>
						{ checking
							? __( 'Check again', 'woocommerce' )
							: view.run.label }
					</Button>
				</div>
				{ ! checking && <RunStatus run={ view.run } /> }
				{ view.checked && onDailyChecks && (
					<div className="wc-sales-readiness-checked__daily">
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Automatically run checks daily',
								'woocommerce'
							) }
							help={
								view.daily_checks
									? undefined
									: __(
											'For an updated report on how well AI assistants can find and shop your store, select Check again above.',
											'woocommerce'
										)
							}
							checked={ view.daily_checks }
							disabled={ savingDailyChecks }
							onChange={ onDailyChecks }
						/>
					</div>
				) }
			</Card.Content>
		</Card.Root>
	);
};
