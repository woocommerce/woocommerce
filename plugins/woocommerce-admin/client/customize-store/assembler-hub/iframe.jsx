// Reference: https://github.com/WordPress/gutenberg/blob/f91b4fb4a12e41dd39c9594f24ea1a1a4e23dade/packages/block-editor/src/components/iframe/index.js#L1
// We fork the code from the above link to reduce the unnecessary network requests and improve the performance.

/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	useState,
	createPortal,
	forwardRef,
	useMemo,
	useEffect,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	useResizeObserver,
	useMergeRefs,
	useRefEffect,
	useDisabled,
} from '@wordpress/compose';
import { __experimentalStyleProvider as StyleProvider } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import { store as blockEditorStore } from '@wordpress/block-editor';

function Iframe( {
	contentRef,
	children,
	tabIndex = 0,
	scale = 1,
	frameSize = 0,
	expand = false,
	readonly,
	forwardedRef: ref,
	loadStyles = true,
	loadScripts = false,
	...props
} ) {
	const [ iframeDocument, setIframeDocument ] = useState();

	const { resolvedAssets } = useSelect( ( select ) => {
		const settings = select( blockEditorStore ).getSettings();

		return {
			resolvedAssets: settings.__unstableResolvedAssets,
		};
	}, [] );
	const { styles = '', scripts = '' } = resolvedAssets;

	const [ contentResizeListener, { height: contentHeight } ] =
		useResizeObserver();
	const setRef = useRefEffect( ( node ) => {
		node._load = () => {
			setIframeDocument( node.contentDocument );
		};
	}, [] );

	const disabledRef = useDisabled( { isDisabled: ! readonly } );
	const bodyRef = useMergeRefs( [ contentRef, disabledRef ] );

	// Correct doctype is required to enable rendering in standards
	// mode. Also preload the styles to avoid a flash of unstyled
	// content.
	const html = `<!doctype html>
<html>
	<head>
		<script>window.frameElement._load()</script>
		<style>html{height:auto!important;min-height:100%;}body{margin:0}</style>
		${ loadStyles ? styles : '' }
		${ loadScripts ? scripts : '' }
	</head>
	<body>
		<script>document.currentScript.parentElement.remove()</script>
	</body>
</html>`;

	const [ src, cleanup ] = useMemo( () => {
		const _src = URL.createObjectURL(
			new window.Blob( [ html ], { type: 'text/html' } )
		);
		return [ _src, () => URL.revokeObjectURL( _src ) ];
	}, [ html ] );

	useEffect( () => cleanup, [ cleanup ] );

	// We need to counter the margin created by scaling the iframe. If the scale
	// is e.g. 0.45, then the top + bottom margin is 0.55 (1 - scale). Just the
	// top or bottom margin is 0.55 / 2 ((1 - scale) / 2).
	const marginFromScaling = ( contentHeight * ( 1 - scale ) ) / 2;

	return (
		<>
			<iframe
				{ ...props }
				style={ {
					...props.style,
					height: expand ? contentHeight : props.style?.height,
					marginTop:
						scale !== 1
							? -marginFromScaling + frameSize
							: props.style?.marginTop,
					marginBottom:
						scale !== 1
							? -marginFromScaling + frameSize
							: props.style?.marginBottom,
					transform:
						scale !== 1
							? `scale( ${ scale } )`
							: props.style?.transform,
					transition: 'all .3s',
				} }
				ref={ useMergeRefs( [ ref, setRef ] ) }
				tabIndex={ tabIndex }
				// Correct doctype is required to enable rendering in standards
				// mode. Also preload the styles to avoid a flash of unstyled
				// content.
				src={ src }
				title={ __( 'Editor canvas', 'woocommerce' ) }
			>
				{ iframeDocument &&
					createPortal(
						<body
							ref={ bodyRef }
							className={ classnames(
								'block-editor-iframe__body',
								'editor-styles-wrapper'
							) }
						>
							{ contentResizeListener }
							<StyleProvider document={ iframeDocument }>
								{ children }
							</StyleProvider>
						</body>,
						iframeDocument.documentElement
					) }
			</iframe>
		</>
	);
}

function IframeIfReady( props, ref ) {
	const isInitialised = useSelect(
		( select ) =>
			select( blockEditorStore ).getSettings().__internalIsInitialized,
		[]
	);

	// We shouldn't render the iframe until the editor settings are initialised.
	// The initial settings are needed to get the styles for the srcDoc, which
	// cannot be changed after the iframe is mounted. srcDoc is used to to set
	// the initial iframe HTML, which is required to avoid a flash of unstyled
	// content.
	if ( ! isInitialised ) {
		return null;
	}

	return <Iframe { ...props } forwardedRef={ ref } />;
}

export default forwardRef( IframeIfReady );
