// TODO: Modify Gutenberg's ResizableFrame component for use in the Assembler Hub and remove this file.
// Reference: https://github.com/WordPress/gutenberg/tree/v16.4.0/packages/edit-site/src/components/resizable-frame/index.js

/**
 * External dependencies
 */
import clsx from 'clsx';
import { useState, useRef, createContext } from '@wordpress/element';
import {
	ResizableBox,
	Tooltip,
	Popover,
	__unstableMotion as motion,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

// Removes the inline styles in the drag handles.
const HANDLE_STYLES_OVERRIDE = {
	position: undefined,
	userSelect: undefined,
	cursor: undefined,
	width: undefined,
	height: undefined,
	top: undefined,
	right: undefined,
	bottom: undefined,
	left: undefined,
};

// The minimum width of the frame (in px) while resizing.
const FRAME_MIN_WIDTH = 320;
// The reference width of the frame (in px) used to calculate the aspect ratio.
const FRAME_REFERENCE_WIDTH = 1300;
// 9 : 19.5 is the target aspect ratio enforced (when possible) while resizing.
const FRAME_TARGET_ASPECT_RATIO = 9 / 19.5;

// Default size for the `frameSize` state.
const INITIAL_FRAME_SIZE = { width: '100%', height: '100%' };

function calculateNewHeight( width, initialAspectRatio ) {
	const lerp = ( a, b, amount ) => {
		return a + ( b - a ) * amount;
	};

	// Calculate the intermediate aspect ratio based on the current width.
	const lerpFactor =
		1 -
		Math.max(
			0,
			Math.min(
				1,
				( width - FRAME_MIN_WIDTH ) /
					( FRAME_REFERENCE_WIDTH - FRAME_MIN_WIDTH )
			)
		);

	// Calculate the height based on the intermediate aspect ratio
	// ensuring the frame arrives at the target aspect ratio.
	const intermediateAspectRatio = lerp(
		initialAspectRatio,
		FRAME_TARGET_ASPECT_RATIO,
		lerpFactor
	);

	return width / intermediateAspectRatio;
}

export const IsResizingContext = createContext( false );

function ResizableFrame( {
	isFullWidth,
	isOversized,
	setIsOversized,
	isReady,
	children,
	/** The default (unresized) width/height of the frame, based on the space availalbe in the viewport. */
	defaultSize,
	innerContentStyle,
	isHandleVisibleByDefault = false,
} ) {
	const [ frameSize, setFrameSize ] = useState( INITIAL_FRAME_SIZE );
	// The width of the resizable frame when a new resize gesture starts.
	const [ startingWidth, setStartingWidth ] = useState();
	const [ isResizing, setIsResizing ] = useState( false );
	const [ shouldShowHandle, setShouldShowHandle ] = useState( false );
	const [ resizeRatio, setResizeRatio ] = useState( 1 );
	const frameTransition = { type: 'tween', duration: isResizing ? 0 : 0.5 };
	const [ hasHandlerDragged, setHasHandlerDragged ] = useState( false );
	const frameRef = useRef( null );
	const resizableHandleHelpId = useInstanceId(
		ResizableFrame,
		'edit-site-resizable-frame-handle-help'
	);
	const defaultAspectRatio = defaultSize.width / defaultSize.height;

	const handleResizeStart = ( _event, _direction, ref ) => {
		// Remember the starting width so we don't have to get `ref.offsetWidth` on
		// every resize event thereafter, which will cause layout thrashing.
		setStartingWidth( ref.offsetWidth );
		setIsResizing( true );
	};

	// Calculate the frame size based on the window width as its resized.
	const handleResize = ( _event, _direction, _ref, delta ) => {
		const normalizedDelta = delta.width / resizeRatio;
		const deltaAbs = Math.abs( normalizedDelta );
		const maxDoubledDelta =
			delta.width < 0 // is shrinking
				? deltaAbs
				: ( defaultSize.width - startingWidth ) / 2;
		const deltaToDouble = Math.min( deltaAbs, maxDoubledDelta );
		const doubleSegment = deltaAbs === 0 ? 0 : deltaToDouble / deltaAbs;
		const singleSegment = 1 - doubleSegment;

		setResizeRatio( singleSegment + doubleSegment * 2 );

		const updatedWidth = startingWidth + delta.width;

		setIsOversized( updatedWidth > defaultSize.width );

		// Width will be controlled by the library (via `resizeRatio`),
		// so we only need to update the height.
		setFrameSize( {
			height: isOversized
				? '100%'
				: calculateNewHeight( updatedWidth, defaultAspectRatio ),
		} );
	};

	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	const handleResizeStop = ( _event, _direction, ref ) => {
		setIsResizing( false );
		if ( ! hasHandlerDragged ) {
			setHasHandlerDragged( true );
		}

		if ( isOversized ) {
			setIsOversized( false );
			setFrameSize( INITIAL_FRAME_SIZE );
		}
	};

	// Handle resize by arrow keys
	const handleResizableHandleKeyDown = ( event ) => {
		if ( ! [ 'ArrowLeft', 'ArrowRight' ].includes( event.key ) ) {
			return;
		}

		event.preventDefault();

		const step = 20 * ( event.shiftKey ? 5 : 1 );
		const delta = step * ( event.key === 'ArrowLeft' ? 1 : -1 );
		const newWidth = Math.min(
			Math.max(
				FRAME_MIN_WIDTH,
				frameRef.current.resizable.offsetWidth + delta
			),
			defaultSize.width
		);

		setFrameSize( {
			width: newWidth,
			height: calculateNewHeight( newWidth, defaultAspectRatio ),
		} );
	};

	const frameAnimationVariants = {
		default: {
			flexGrow: 0,
			height: frameSize.height,
		},
		fullWidth: {
			flexGrow: 1,
			height: frameSize.height,
		},
	};

	const resizeHandleVariants = {
		hidden: {
			opacity: 0,
			left: 0,
		},
		visible: {
			opacity: 0.6,
			left: -10,
		},
		active: {
			opacity: 1,
			left: -10,
		},
	};
	const currentResizeHandleVariant = ( () => {
		if ( isResizing || isHandleVisibleByDefault ) {
			return 'active';
		}
		return shouldShowHandle ? 'visible' : 'hidden';
	} )();

	const resizeHandler = (
		/* Disable reason: role="separator" does in fact support aria-valuenow */
		/* eslint-disable-next-line jsx-a11y/role-supports-aria-props */
		<motion.button
			key="handle"
			role="separator"
			aria-orientation="vertical"
			className={ clsx( 'edit-site-resizable-frame__handle', {
				'is-resizing': isResizing,
			} ) }
			variants={ resizeHandleVariants }
			animate={ currentResizeHandleVariant }
			aria-label={ __( 'Drag to resize', 'woocommerce' ) }
			aria-describedby={ resizableHandleHelpId }
			aria-valuenow={
				frameRef.current?.resizable?.offsetWidth || undefined
			}
			aria-valuemin={ FRAME_MIN_WIDTH }
			aria-valuemax={ defaultSize.width }
			onKeyDown={ handleResizableHandleKeyDown }
			initial="hidden"
			exit="hidden"
			whileFocus="active"
			whileHover="active"
			children={
				isHandleVisibleByDefault &&
				! hasHandlerDragged && (
					<Popover
						className="woocommerce-assembler-hub__resizable-frame__drag-handler"
						position="middle right"
					>
						{ __( 'Drag to resize', 'woocommerce' ) }
					</Popover>
				)
			}
		/>
	);

	return (
		<ResizableBox
			as={ motion.div }
			ref={ frameRef }
			initial={ false }
			variants={ frameAnimationVariants }
			animate={ isFullWidth ? 'fullWidth' : 'default' }
			onAnimationComplete={ ( definition ) => {
				if ( definition === 'fullWidth' )
					setFrameSize( { width: '100%', height: '100%' } );
			} }
			transition={ frameTransition }
			size={ frameSize }
			enable={ {
				top: false,
				right: false,
				bottom: false,
				// Resizing will be disabled until the editor content is loaded.
				left: isReady,
				topRight: false,
				bottomRight: false,
				bottomLeft: false,
				topLeft: false,
			} }
			resizeRatio={ resizeRatio }
			handleClasses={ undefined }
			handleStyles={ {
				left: HANDLE_STYLES_OVERRIDE,
				right: HANDLE_STYLES_OVERRIDE,
			} }
			minWidth={ FRAME_MIN_WIDTH }
			maxWidth={ '100%' }
			maxHeight={ '100%' }
			onFocus={ () => setShouldShowHandle( true ) }
			onBlur={ () => setShouldShowHandle( false ) }
			onMouseOver={ () => setShouldShowHandle( true ) }
			onMouseOut={ () => setShouldShowHandle( false ) }
			handleComponent={ {
				left: (
					<>
						{ isHandleVisibleByDefault ? (
							<div>{ resizeHandler }</div>
						) : (
							<Tooltip
								position="middle right"
								text={ __( 'Drag to resize', 'woocommerce' ) }
							>
								{ resizeHandler }
							</Tooltip>
						) }
						<div hidden id={ resizableHandleHelpId }>
							{ __(
								'Use left and right arrow keys to resize the canvas. Hold shift to resize in larger increments.',
								'woocommerce'
							) }
						</div>
					</>
				),
			} }
			onResizeStart={ handleResizeStart }
			onResize={ handleResize }
			onResizeStop={ handleResizeStop }
			className={ clsx( 'edit-site-resizable-frame__inner', {
				'is-resizing': isResizing,
			} ) }
		>
			<motion.div
				className="customize-your-store-edit-site-resizable-frame__inner-content"
				animate={ {
					borderRadius: isFullWidth ? 0 : 8,
				} }
				transition={ frameTransition }
				style={ innerContentStyle }
			>
				<IsResizingContext.Provider value={ isResizing }>
					{ children }
				</IsResizingContext.Provider>
			</motion.div>
		</ResizableBox>
	);
}

export default ResizableFrame;
