/**
 * External dependencies
 */
import { ReactNode, ErrorInfo, useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { captureException } from '@woocommerce/remote-logging';
import { bumpStat } from '@woocommerce/tracks';
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

export const ErrorBoundary: React.FC< ErrorBoundaryProps > = ( {
	children,
} ) => {
	const [ state, setState ] = useState< ErrorBoundaryState >( {
		hasError: false,
		error: null,
		errorInfo: null,
	} );

	useEffect( () => {
		const errorHandler = ( error: Error, errorInfo: ErrorInfo ) => {
			setState( ( prevState ) => ( {
				...prevState,
				hasError: true,
				error,
				errorInfo,
			} ) );

			bumpStat( 'error', 'unhandled-js-error-during-render' );

			// Limit the component stack to 10 calls so we don't send too much data.
			const componentStack = errorInfo.componentStack
				.trim()
				.split( '\n' )
				.slice( 0, 10 )
				.map( ( line ) => line.trim() );

			captureException( error, {
				severity: 'critical',
				extra: {
					componentStack,
				},
			} );
		};

		const unhandledRejectionHandler = ( event: PromiseRejectionEvent ) => {
			errorHandler( event.reason, { componentStack: '' } );
		};

		const errorEventHandler = ( event: ErrorEvent ) => {
			errorHandler( event.error, { componentStack: '' } );
		};

		window.addEventListener( 'error', errorEventHandler );
		window.addEventListener(
			'unhandledrejection',
			unhandledRejectionHandler
		);

		return () => {
			window.removeEventListener( 'error', errorEventHandler );
			window.removeEventListener(
				'unhandledrejection',
				unhandledRejectionHandler
			);
		};
	}, [] );

	const handleRefresh = () => {
		window.location.reload();
	};

	const handleOpenSupport = () => {
		window.open(
			'https://wordpress.org/support/plugin/woocommerce/',
			'_blank'
		);
	};

	if ( state.hasError ) {
		return (
			<div className="woocommerce-global-error-boundary">
				<h1 className="woocommerce-global-error-boundary__heading">
					{ __( 'Oops, something went wrong', 'woocommerce' ) }
				</h1>
				<p className="woocommerce-global-error-boundary__subheading">
					{ __(
						"We're sorry for the inconvenience. Please try reloading the page, or you can get support from the community forums.",
						'woocommerce'
					) }
				</p>
				<div className="woocommerce-global-error-boundary__actions">
					<Button variant="secondary" onClick={ handleOpenSupport }>
						{ __( 'Get Support', 'woocommerce' ) }
					</Button>
					<Button variant="primary" onClick={ handleRefresh }>
						{ __( 'Reload Page', 'woocommerce' ) }
					</Button>
				</div>
				<details className="woocommerce-global-error-boundary__details">
					<summary>
						{ __( 'Click for error details', 'woocommerce' ) }
					</summary>
					<div className="woocommerce-global-error-boundary__details-content">
						<strong className="woocommerce-global-error-boundary__error">
							{ state.error && state.error.toString() }
						</strong>
						<p>
							{ state.errorInfo &&
								state.errorInfo.componentStack }
						</p>
					</div>
				</details>
			</div>
		);
	}

	return <>{ children }</>;
};
