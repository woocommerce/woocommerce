/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */

export type DisplayStateProps = {
	state?: 'visible' | 'visually-hidden' | 'hidden';
	children: React.ReactNode;
} & React.HTMLAttributes< HTMLDivElement >;

export const DisplayState = ( {
	state = 'visible',
	children,
	...props
}: DisplayStateProps ) => {
	if ( state === 'visible' ) {
		return <div { ...props }>{ children }</div>;
	}

	if ( state === 'visually-hidden' ) {
		return (
			<div { ...props } style={ { display: 'none' } }>
				{ children }
			</div>
		);
	}

	return null;
};
