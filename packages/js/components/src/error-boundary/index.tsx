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
	/**
	 * The content to be rendered inside the ErrorBoundary component.
	 */
	children: ReactNode;
	/**
	 * The custom error message to be displayed. Defaults to a generic message.
	 */
	errorMessage?: ReactNode;
	/**
	 * Determines whether to show an action button. Defaults to true.
	 */
	showActionButton?: boolean;
	/**
	 * The label to be used for the action button. Defaults to 'Reload'.
	 */
	actionLabel?: string;
	/**
	 * The callback function to be executed when the action button is clicked. Defaults to window.location.reload.
	 *
	 * @param error - The error that was caught.
	 */
	actionCallback?: ( error: Error ) => void;
	/**
	 * Determines whether to reset the error boundary state after the action is performed. Defaults to true.
	 */
	resetErrorAfterAction?: boolean;

	/**
	 * Callback function to be executed when an error is caught.
	 *
	 * @param error     - The error that was caught.
	 * @param errorInfo - The error info object.
	 */
	onError?: ( error: Error, errorInfo: ErrorInfo ) => void;
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
		resetErrorAfterAction: true,
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

		if ( this.props.onError ) {
			this.props.onError( _error, errorInfo );
		}
		// TODO: Log error to error tracking service
	}

	handleReload = () => {
		window.location.reload();
	};

	handleAction = () => {
		const { actionCallback, resetErrorAfterAction } = this.props;

		if ( actionCallback ) {
			actionCallback( this.state.error as Error );
		} else {
			this.handleReload();
		}

		if ( resetErrorAfterAction ) {
			this.setState( { hasError: false, error: null, errorInfo: null } );
		}
	};

	render() {
		const { children, errorMessage, showActionButton, actionLabel } =
			this.props;

		if ( this.state.hasError ) {
			return (
				<div className="woocommerce-error-boundary">
					<EmptyContent
						title=""
						actionLabel=""
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
							showActionButton ? this.handleAction : undefined
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
