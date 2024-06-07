/**
 * External dependencies
 */
import { Component, ReactNode, ErrorInfo } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
/**
 * Internal dependencies
 */
import './style.scss';

type ErrorBoundaryProps = {
	children: ReactNode;
};

type ErrorBoundaryState = {
	hasError: boolean;
	error: Error | null;
	errorInfo: ErrorInfo | null;
};

export class ErrorBoundary extends Component<
	ErrorBoundaryProps,
	ErrorBoundaryState
> {
	constructor( props: ErrorBoundaryProps ) {
		super( props );
		this.state = { hasError: false, error: null, errorInfo: null };
	}

	static getDerivedStateFromError(
		error: Error
	): Partial< ErrorBoundaryState > {
		return { hasError: true, error };
	}

	componentDidCatch( _error: Error, errorInfo: ErrorInfo ) {
		this.setState( { errorInfo } );
		// TODO: Log error to error tracking service
	}

	handleRefresh = () => {
		window.location.reload();
	};

	handleOpenIssue = () => {
		const { error, errorInfo } = this.state;
		const issueBody = `
### Describe the bug
A clear and concise description of what the bug is.

**Error Details**
\`\`\`
Error: ${ error?.toString() }
Stack: ${ errorInfo?.componentStack }
\`\`\`

### Expected behavior
A clear and concise description of what you expected to happen.

### Actual behavior

Fatal error occurred and error page was shown.

### Steps to reproduce
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

### WordPress Environment
The System Status Report is found in your WordPress admin under **WooCommerce > Status**.
Please select “Get system report”, then “Copy for support”, and then paste it here.

### Additional context
Add any other context about the problem here.
        `;
		const issueUrl = `https://github.com/woocommerce/woocommerce/issues/new?body=${ encodeURIComponent(
			issueBody
		) }`;
		window.open( issueUrl, '_blank' );
	};

	render() {
		if ( this.state.hasError ) {
			return (
				<div className="woocommerce-error-boundary">
					<h1 className="woocommerce-error-boundary__heading">
						{ __( 'Oops, Something went wrong', 'woocommerce' ) }
					</h1>
					<p className="woocommerce-error-boundary__subheading">
						{ __(
							"We're sorry for the inconvenience. Please try refreshing the page, or you can report the issue on GitHub.",
							'woocommerce'
						) }
					</p>
					<div className="woocommerce-error-boundary__actions">
						<Button
							variant="secondary"
							onClick={ this.handleOpenIssue }
							style={ { margin: '10px', padding: '10px 20px' } }
						>
							{ __( 'Report Issue on Github', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ this.handleRefresh }
							style={ { margin: '10px', padding: '10px 20px' } }
						>
							{ __(
								'Refresh Page and Try Again',
								'woocommerce'
							) }
						</Button>
					</div>
					<details className="woocommerce-error-boundary__details">
						<summary>Click for error details</summary>
						<div className="woocommerce-error-boundary__details-content">
							<strong className="woocommerce-error-boundary__error">
								{ this.state.error &&
									this.state.error.toString() }
							</strong>
							<p>
								{ this.state.errorInfo &&
									this.state.errorInfo.componentStack }
							</p>
						</div>
					</details>
				</div>
			);
		}

		return this.props.children;
	}
}
