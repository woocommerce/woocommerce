/**
 * Error boundary that catches render-time errors thrown anywhere in the React
 * tree and surfaces them in-place instead of unmounting the whole app.
 *
 * Without this, a JS error inside any panel would silently blank the entire
 * mount point — making "saw content for a second then it disappeared" failures
 * impossible to diagnose.
 */

import { Component } from '@wordpress/element';
import type { ReactNode, ErrorInfo } from 'react';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface Props {
	label: string;
	children: ReactNode;
}

interface State {
	error: Error | null;
}

export class ErrorBoundary extends Component< Props, State > {
	constructor( props: Props ) {
		super( props );
		this.state = { error: null };
	}

	static getDerivedStateFromError( error: Error ): State {
		return { error };
	}

	componentDidCatch( error: Error, info: ErrorInfo ): void {
		// eslint-disable-next-line no-console
		console.error( '[wc-react-order-edit] caught render error in', this.props.label, error, info );
	}

	render() {
		if ( this.state.error ) {
			return (
				<Notice status="error" isDismissible={ false }>
					<strong>
						{ /* translators: %s: name of the React subtree where the error was thrown */ }
						{ __( 'Error in', 'woocommerce' ) } { this.props.label }:
					</strong>{ ' ' }
					{ this.state.error.message || String( this.state.error ) }
				</Notice>
			);
		}
		return this.props.children;
	}
}
