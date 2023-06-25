/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */

export type DisplayStateProps = {
	state?: 'visible' | 'visually-hidden' | 'hidden';
	children: React.ReactNode;
} & React.HTMLAttributes< HTMLDivElement >;

export const DisplayState: React.FC< DisplayStateProps > = ( {
	state = 'visible',
	children,
} ) => {
	if ( state === 'visible' ) {
		return <>{ children }</>;
	}

	if ( state === 'visually-hidden' ) {
		return <div style={ { display: 'none' } }>{ children }</div>;
	}

	return null;
};
