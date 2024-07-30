// Reference: https://github.com/WordPress/gutenberg/blob/f91b4fb4a12e41dd39c9594f24ea1a1a4e23dade/packages/block-editor/src/components/iframe/index.js#L1
// We fork the code from the above link to reduce the unnecessary network requests and improve the performance.

/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	useState,
	createPortal,
	forwardRef,
	useMemo,
	useEffect,
	useRef,
	useContext,
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

/**
 * Internal dependencies
 */
import { ZoomOutContext } from './context/zoom-out-context';

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
	const [ bodyClasses, setBodyClasses ] = useState( [] );
	const prevContainerWidth = useRef();
	const [ contentResizeListener, { height: contentHeight } ] =
		useResizeObserver();
	const [ containerResizeListener, { width: containerWidth } ] =
		useResizeObserver();

	const { resolvedAssets } = useSelect( ( select ) => {
		const settings = select( blockEditorStore ).getSettings();
		return {
			resolvedAssets: settings.__unstableResolvedAssets,
		};
	}, [] );
	const { styles = '', scripts = '' } = resolvedAssets;

	const isZoomedOut = scale !== 1;

	useEffect( () => {
		if ( ! isZoomedOut ) {
			prevContainerWidth.current = containerWidth;
		}
	}, [ containerWidth, isZoomedOut ] );

	const setRef = useRefEffect( ( node ) => {
		node._load = () => {
			setIframeDocument( node.contentDocument );
		};
		function onLoad() {
			const { contentDocument, ownerDocument } = node;
			const { documentElement } = contentDocument;

			documentElement.classList.add( 'block-editor-iframe__html' );

			setBodyClasses(
				Array.from( ownerDocument?.body.classList ).filter(
					( name ) =>
						name.startsWith( 'admin-color-' ) ||
						name.startsWith( 'post-type-' ) ||
						name === 'wp-embed-responsive'
				)
			);
		}

		node.addEventListener( 'load', onLoad );

		return () => {
			delete node._load;
			node.removeEventListener( 'load', onLoad );
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

	useEffect( () => {
		if ( ! iframeDocument || ! isZoomedOut ) {
			return;
		}

		iframeDocument.documentElement.classList.add( 'is-zoomed-out' );

		const maxWidth = 800;
		iframeDocument.documentElement.style.setProperty(
			'--wp-block-editor-iframe-zoom-out-scale',
			scale === 'default'
				? Math.min( containerWidth, maxWidth ) /
						prevContainerWidth.current
				: scale
		);
		iframeDocument.documentElement.style.setProperty(
			'--wp-block-editor-iframe-zoom-out-frame-size',
			typeof frameSize === 'number' ? `${ frameSize }px` : frameSize
		);
		iframeDocument.documentElement.style.setProperty(
			'--wp-block-editor-iframe-zoom-out-content-height',
			`${ contentHeight }px`
		);
		iframeDocument.documentElement.style.setProperty(
			'--wp-block-editor-iframe-zoom-out-container-width',
			`${ containerWidth }px`
		);
		iframeDocument.documentElement.style.setProperty(
			'--wp-block-editor-iframe-zoom-out-prev-container-width',
			`${ prevContainerWidth.current }px`
		);

		return () => {
			iframeDocument.documentElement.classList.remove( 'is-zoomed-out' );

			iframeDocument.documentElement.style.removeProperty(
				'--wp-block-editor-iframe-zoom-out-scale'
			);
			iframeDocument.documentElement.style.removeProperty(
				'--wp-block-editor-iframe-zoom-out-frame-size'
			);
			iframeDocument.documentElement.style.removeProperty(
				'--wp-block-editor-iframe-zoom-out-content-height'
			);
			iframeDocument.documentElement.style.removeProperty(
				'--wp-block-editor-iframe-zoom-out-container-width'
			);
			iframeDocument.documentElement.style.removeProperty(
				'--wp-block-editor-iframe-zoom-out-prev-container-width'
			);
		};
	}, [
		scale,
		frameSize,
		iframeDocument,
		contentHeight,
		containerWidth,
		isZoomedOut,
	] );

	return (
		<div className="block-editor-iframe__container">
			{ containerResizeListener }
			<div
				className={ clsx(
					'block-editor-iframe__scale-container',
					isZoomedOut && 'is-zoomed-out'
				) }
				style={ {
					'--wp-block-editor-iframe-zoom-out-container-width':
						isZoomedOut && `${ containerWidth }px`,
					'--wp-block-editor-iframe-zoom-out-prev-container-width':
						isZoomedOut && `${ prevContainerWidth.current }px`,
				} }
			>
				<iframe
					{ ...props }
					style={ {
						...props.style,
						height: expand ? contentHeight : props.style?.height,
						transition: 'all .3s',
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
					} }
					ref={ useMergeRefs( [ ref, setRef ] ) }
					tabIndex={ tabIndex }
					src={ src }
					title={ __( 'Editor canvas', 'woocommerce' ) }
					name="editor-canvas"
				>
					{ iframeDocument &&
						createPortal(
							<body
								ref={ bodyRef }
								className={ clsx(
									'block-editor-iframe__body',
									'editor-styles-wrapper',
									...bodyClasses
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
			</div>
		</div>
	);
}

function IframeIfReady( props, ref ) {
	const isInitialised = useSelect(
		( select ) =>
			select( blockEditorStore ).getSettings().__internalIsInitialized,
		[]
	);

	const { isZoomedOut } = useContext( ZoomOutContext );

	// We shouldn't render the iframe until the editor settings are initialised.
	// The initial settings are needed to get the styles for the srcDoc, which
	// cannot be changed after the iframe is mounted. srcDoc is used to to set
	// the initial iframe HTML, which is required to avoid a flash of unstyled
	// content.
	if ( ! isInitialised ) {
		return null;
	}

	return (
		<Iframe
			{ ...props }
			forwardedRef={ ref }
			scale={ ! isZoomedOut ? 1 : 'default' }
		/>
	);
}

export default forwardRef( IframeIfReady );
