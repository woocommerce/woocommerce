/**
 * External dependencies
 */
import { Component, createElement } from '@wordpress/element';
import type { ErrorInfo, ReactNode } from 'react';
import { __ } from '@wordpress/i18n';

type ErrorBoundaryProps = {
	children: ReactNode;
	fallback?: ReactNode;
};

type ErrorBoundaryState = {
	hasError: boolean;
};

/**
 * Minimal error boundary used to isolate field-level render failures in the
 * modernised settings DataForm.
 *
 * The admin client ships a richer app-level ErrorBoundary that reports to
 * remote logging; the SDK intentionally keeps this local boundary small so it
 * can be consumed by external plugins without pulling in Woo-internal
 * dependencies.
 */
export class ErrorBoundary extends Component<
	ErrorBoundaryProps,
	ErrorBoundaryState
> {
	constructor( props: ErrorBoundaryProps ) {
		super( props );
		this.state = { hasError: false };
	}

	static getDerivedStateFromError(): ErrorBoundaryState {
		return { hasError: true };
	}

	componentDidCatch( error: Error, errorInfo: ErrorInfo ) {
		// eslint-disable-next-line no-console
		console.error(
			'modern-settings-sdk: field render failed.',
			error,
			errorInfo
		);
	}

	render() {
		if ( this.state.hasError ) {
			if ( this.props.fallback !== undefined ) {
				return this.props.fallback;
			}

			return (
				<div className="modern-woocommerce-settings__field-error">
					{ __(
						'This setting could not be rendered.',
						'woocommerce'
					) }
				</div>
			);
		}

		return this.props.children;
	}
}
