/**
 * External dependencies
 */
import { Card, Link, Text } from '@wordpress/ui';
import { Button } from '@wordpress/components';
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
}

/**
 * When the store was last checked, with Check again, then Need help? with the results to
 * download for support (built from the last check), from the first check on. Before the first check it says what a
 * check shows and offers Run check.
 */
export const CheckedCard = ( {
	view,
	running,
	onRun,
	onDownloadError,
}: CheckedCardProps ) => (
	<Card.Root className="wc-sales-readiness-card">
		<Card.Content>
			{ view.intro && (
				<Text
					variant="body-md"
					className="wc-sales-readiness-checked__intro"
				>
					{ view.intro }
				</Text>
			) }
			<div className="wc-sales-readiness-checked">
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
					{ ! view.intro && (
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
				<Button
					variant="secondary"
					size="compact"
					disabled={ view.run.pending || running }
					isBusy={ running }
					onClick={ onRun }
				>
					{ view.run.label }
				</Button>
			</div>
			<RunStatus run={ view.run } />
		</Card.Content>
	</Card.Root>
);
