// Reference: https://github.com/Automattic/wp-calypso/blob/d3c9b16fb99ce242f61baa21119b7c20f8823be6/packages/global-styles/src/components/global-styles-variation-container/index.tsx#L19
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import {
	__unstableEditorStyles as EditorStyles,
	privateApis as blockEditorPrivateApis,
	// @ts-ignore no types exist yet.
} from '@wordpress/block-editor';
import { useRefEffect } from '@wordpress/compose';
import { MutableRefObject, useMemo } from 'react';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import './style.scss';
import Iframe from '../../../iframe';

const { useGlobalStylesOutput } = unlock( blockEditorPrivateApis );

interface Props {
	width: number | null;
	height: number;
	inlineCss?: string;
	containerResizeListener: JSX.Element;
	children: JSX.Element;
	iframeInstance?: MutableRefObject< HTMLObjectElement | null >;
	onFocusOut?: () => void;
}

export const GlobalStylesVariationIframe = ( {
	width,
	height,
	inlineCss,
	containerResizeListener,
	children,
	onFocusOut,
	iframeInstance,
	...props
}: Props ) => {
	const [ styles ] = useGlobalStylesOutput();
	// Reset leaked styles from WP common.css and remove main content layout padding and border.
	const editorStyles = useMemo( () => {
		if ( styles ) {
			return [
				...styles,
				...( inlineCss
					? [
							{
								css: inlineCss,
								isGlobalStyles: true,
							},
					  ]
					: [] ),
				{
					css: 'html{overflow:hidden}body{min-width: 0;padding: 0;border: none;transform:scale(1);}',
					isGlobalStyles: true,
				},
			];
		}
		return styles;
	}, [ inlineCss, styles ] );

	return (
		<Iframe
			ref={ iframeInstance }
			className="global-styles-variation-container__iframe"
			style={ {
				height,
				visibility: width ? 'visible' : 'hidden',
			} }
			tabIndex={ -1 }
			loadStyles={ false }
			contentRef={ useRefEffect( ( bodyElement ) => {
				// Disable moving focus to the writing flow wrapper if the focus disappears
				// See https://github.com/WordPress/gutenberg/blob/aa8e1c52c7cb497e224a479673e584baaca97113/packages/block-editor/src/components/writing-flow/use-tab-nav.js#L136
				const handleFocusOut = ( event: Event ) => {
					event.stopImmediatePropagation();
					// Explicitly call the focusOut handler, if available.
					onFocusOut?.();
				};
				bodyElement.addEventListener( 'focusout', handleFocusOut );
				return () => {
					bodyElement.removeEventListener(
						'focusout',
						handleFocusOut
					);
				};
			}, [] ) }
			scrolling="no"
			{ ...props }
		>
			<EditorStyles styles={ editorStyles ?? [] } />
			{ containerResizeListener }
			{ children }
		</Iframe>
	);
};
