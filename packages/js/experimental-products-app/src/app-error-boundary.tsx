/**
 * External dependencies
 */
import { Component, type ErrorInfo, type ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { EmptyState, Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { FEEDBACK_URL, GITHUB_ISSUES_URL } from './constants';

type AppErrorBoundaryProps = {
	children: ReactNode;
};

type AppErrorBoundaryState = {
	error: Error | null;
	hasError: boolean;
};

export class AppErrorBoundary extends Component<
	AppErrorBoundaryProps,
	AppErrorBoundaryState
> {
	state: AppErrorBoundaryState = {
		error: null,
		hasError: false,
	};

	static getDerivedStateFromError(
		error: Error
	): Partial< AppErrorBoundaryState > {
		return {
			error,
			hasError: true,
		};
	}

	componentDidCatch( error: Error, errorInfo: ErrorInfo ) {
		// eslint-disable-next-line no-console
		console.error( error, errorInfo );
	}

	handleReload = () => {
		window.location.reload();
	};

	render() {
		if ( this.state.hasError ) {
			return (
				<EmptyState.Root className="woocommerce-experimental-products-app-error">
					<EmptyState.Title>
						{ __(
							'Oops, the experimental products experience ran into a problem',
							'woocommerce'
						) }
					</EmptyState.Title>
					<EmptyState.Description className="woocommerce-experimental-products-app-error__description">
						{ __(
							'This experience is still experimental. Please report the issue on GitHub or share feedback in the survey so we can improve it.',
							'woocommerce'
						) }
					</EmptyState.Description>
					<EmptyState.Actions>
						<Stack direction="row" gap="xs" justify="center">
							<Button
								href={ GITHUB_ISSUES_URL }
								target="_blank"
								rel="noopener noreferrer"
								variant="primary"
							>
								{ __(
									'Report an issue on GitHub',
									'woocommerce'
								) }
							</Button>
							<Button
								href={ FEEDBACK_URL }
								target="_blank"
								rel="noopener noreferrer"
								variant="secondary"
							>
								{ __(
									'Share feedback in survey',
									'woocommerce'
								) }
							</Button>
							<Button
								onClick={ this.handleReload }
								variant="secondary"
							>
								{ __( 'Reload page', 'woocommerce' ) }
							</Button>
						</Stack>
					</EmptyState.Actions>
				</EmptyState.Root>
			);
		}

		return this.props.children;
	}
}
