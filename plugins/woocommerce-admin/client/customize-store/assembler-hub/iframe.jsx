// Reference: https://github.com/WordPress/gutenberg/blob/f91b4fb4a12e41dd39c9594f24ea1a1a4e23dade/packages/block-editor/src/components/iframe/index.js#L1
// We fork the code from the above link to reduce the unnecessary network requests and improve the performance.
// Some of the code is not used in the project and is removed.
// We've also made some changes to the code to make it work with the Zoom Out feature.

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
	readonly,
	forwardedRef: ref,
	title = __( 'Editor canvas', 'woocommerce' ),
	...props
} ) {
	const { resolvedAssets } = useSelect( ( select ) => {
		const { getSettings } = select( blockEditorStore );
		const settings = getSettings();
		return {
			resolvedAssets: settings.__unstableResolvedAssets,
		};
	}, [] );

	const { styles = '', scripts = '' } = resolvedAssets;
	const [ iframeDocument, setIframeDocument ] = useState();
	const prevContainerWidth = useRef( 0 );
	const [ bodyClasses, setBodyClasses ] = useState( [] );
	const [ contentResizeListener, { height: contentHeight } ] =
		useResizeObserver();
	const [ containerResizeListener, { width: containerWidth } ] =
		useResizeObserver();

	const setRef = useRefEffect( ( node ) => {
		node._load = () => {
			setIframeDocument( node.contentDocument );
		};
		function onLoad() {
			const { contentDocument, ownerDocument } = node;
			const { documentElement } = contentDocument;

			documentElement.classList.add( 'block-editor-iframe__html' );

			// Ideally ALL classes that are added through get_body_class should
			// be added in the editor too, which we'll somehow have to get from
			// the server in the future (which will run the PHP filters).
			setBodyClasses(
				Array.from( ownerDocument?.body.classList ).filter(
					( name ) =>
						name.startsWith( 'admin-color-' ) ||
						name.startsWith( 'post-type-' ) ||
						name === 'wp-embed-responsive'
				)
			);

			contentDocument.dir = ownerDocument.dir;
		}

		node.addEventListener( 'load', onLoad );

		return () => {
			delete node._load;
			node.removeEventListener( 'load', onLoad );
		};
	}, [] );

	const [ iframeWindowInnerHeight, setIframeWindowInnerHeight ] = useState();

	const iframeResizeRef = useRefEffect( ( node ) => {
		const nodeWindow = node.ownerDocument.defaultView;

		setIframeWindowInnerHeight( nodeWindow.innerHeight );
		const onResize = () => {
			setIframeWindowInnerHeight( nodeWindow.innerHeight );
		};
		nodeWindow.addEventListener( 'resize', onResize );
		return () => {
			nodeWindow.removeEventListener( 'resize', onResize );
		};
	}, [] );

	const [ windowInnerWidth, setWindowInnerWidth ] = useState();

	const windowResizeRef = useRefEffect( ( node ) => {
		const nodeWindow = node.ownerDocument.defaultView;

		setWindowInnerWidth( nodeWindow.innerWidth );
		const onResize = () => {
			setWindowInnerWidth( nodeWindow.innerWidth );
		};
		nodeWindow.addEventListener( 'resize', onResize );
		return () => {
			nodeWindow.removeEventListener( 'resize', onResize );
		};
	}, [] );

	const isZoomedOut = scale !== 1;

	useEffect( () => {
		if ( ! isZoomedOut && ! prevContainerWidth.current ) {
			prevContainerWidth.current = containerWidth;
		}
	}, [ containerWidth, isZoomedOut ] );

	const disabledRef = useDisabled( { isDisabled: ! readonly } );
	const bodyRef = useMergeRefs( [
		contentRef,
		disabledRef,
		// Avoid resize listeners when not needed, these will trigger
		// unnecessary re-renders when animating the iframe width, or when
		// expanding preview iframes.
		isZoomedOut ? iframeResizeRef : null,
	] );

	// Correct doctype is required to enable rendering in standards
	// mode. Also preload the styles to avoid a flash of unstyled
	// content.
	const html = `<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<script>window.frameElement._load()</script>
		<style>
			html{
				height: auto !important;
				min-height: 100%;
			}
			/* Lowest specificity to not override global styles */
			:where(body) {
				margin: 0;
				/* Default background color in case zoom out mode background
				colors the html element */
				background-color: white;
			}
		</style>
		${ styles }
		${ scripts }
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

	useEffect( () => {
		if ( ! iframeDocument || ! isZoomedOut ) {
			return;
		}

		const maxWidth = 800;

		iframeDocument.documentElement.classList.add( 'is-zoomed-out' );

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
			'--wp-block-editor-iframe-zoom-out-inner-height',
			`${ iframeWindowInnerHeight }px`
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
				'--wp-block-editor-iframe-zoom-out-inner-height'
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
		iframeWindowInnerHeight,
		contentHeight,
		containerWidth,
		windowInnerWidth,
		isZoomedOut,
	] );

	const iframe = (
		<>
			{ /* eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions */ }
			<iframe
				{ ...props }
				style={ {
					border: 0,
					...props.style,
					height: props.style?.height,
					transition: 'all .3s',
				} }
				ref={ useMergeRefs( [ ref, setRef ] ) }
				tabIndex={ tabIndex }
				// Correct doctype is required to enable rendering in standards
				// mode. Also preload the styles to avoid a flash of unstyled
				// content.
				src={ src }
				title={ title }
				name="editor-canvas"
			>
				{ iframeDocument &&
					createPortal(
						// We want to prevent React events from bubbling throught the iframe
						// we bubble these manually.
						/* eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions */
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
		</>
	);

	return (
		<div className="block-editor-iframe__container" ref={ windowResizeRef }>
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
				{ iframe }
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

	const zoomOutProps = isZoomedOut
		? {
				scale: 'default',
				frameSize: '48px',
		  }
		: {};

	// We shouldn't render the iframe until the editor settings are initialised.
	// The initial settings are needed to get the styles for the srcDoc, which
	// cannot be changed after the iframe is mounted. srcDoc is used to to set
	// the initial iframe HTML, which is required to avoid a flash of unstyled
	// content.
	if ( ! isInitialised ) {
		return null;
	}

	const iframeProps = {
		...props,
		...zoomOutProps,
	};

	return <Iframe { ...iframeProps } forwardedRef={ ref } />;
}

export default forwardRef( IframeIfReady );
