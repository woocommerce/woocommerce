/**
 * External dependencies
 */
import { createElement, useEffect, useRef } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils';

type InfoViewProps = {
	text: string;
	className?: string;
	css?: string;
};

export const InfoView = ( { text, className, css = '' }: InfoViewProps ) => {
	const ref = useRef< HTMLDivElement >( null );

	useEffect( () => {
		if ( ref.current ) {
			ref.current.style.cssText = css;
		}
	}, [ css ] );

	return (
		<div
			ref={ ref }
			className={ clsx( 'woocommerce-settings-info-view', className ) }
			dangerouslySetInnerHTML={ {
				__html: sanitizeHTML( text ?? '' ),
			} }
		/>
	);
};
