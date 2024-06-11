/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Component, ReactNode, ErrorInfo } from 'react';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import EmptyContent from '../empty-content';
import { alertIcon } from './constants';

export type ErrorBoundaryProps = {
	children: ReactNode;
	/** Custom error title to display, defaults to a generic message */
	errorMessage?: ReactNode;
	/** Whether to show an action button, defaults to true */
	showActionButton?: boolean;
	/**
	 * Label to be used for the action button. Defaults to 'Reload'
	 */
	actionLabel?: string;
	/**
	 * Callback to be used for the action button, defaults to window.location.reload
	 */
	actionCallback?: () => void;
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
	static defaultProps: Partial< ErrorBoundaryProps > = {
		showActionButton: true,
	};

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

	handleReload = () => {
		window.location.reload();
	};

	render() {
		const {
			children,
			errorMessage,
			showActionButton,
			actionLabel,
			actionCallback,
		} = this.props;

		if ( this.state.hasError ) {
			return (
				<div className="woocommerce-error-boundary">
					<EmptyContent
						message={
							errorMessage ||
							__(
								'Oops, something went wrong. Please try again',
								'woocommerce'
							)
						}
						secondaryActionLabel={
							actionLabel || __( 'Reload', 'woocommerce' )
						}
						secondaryActionURL={ null }
						secondaryActionCallback={
							showActionButton
								? actionCallback || this.handleReload
								: undefined
						}
						illustrationWidth={ 36 }
						illustrationHeight={ 36 }
						illustration={ alertIcon }
					/>
				</div>
			);
		}

		return children;
	}
}
