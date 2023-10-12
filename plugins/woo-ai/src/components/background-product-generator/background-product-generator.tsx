/**
 * External dependencies
 */
import { useRef, useEffect, useState, MouseEvent, useCallback } from 'react';

/**
 * Internal dependencies
 */
import { drawImageControls, loadImage } from './utils';

const backgroundSize = 1024;
const draggingConernerSize = 20;
type position = 'nw' | 'ne' | 'sw' | 'se';

/**
 * Renders a scene onto a canvas element using the given context.
 */
function renderScene(
	ctx: CanvasRenderingContext2D,
	background: HTMLImageElement,
	productImage: HTMLImageElement,
	productPosition: { x: number; y: number },
	productSize: { width: number; height: number },
	scalingFactor: number,
	hasShadow: boolean,
	onSave?: ( dataUrl: string ) => void
) {
	ctx.drawImage( background, 0, 0, backgroundSize, backgroundSize );

	// Set shadow properties
	if ( hasShadow ) {
		ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
		ctx.shadowBlur = 15;
		ctx.shadowOffsetX = 5;
		ctx.shadowOffsetY = 5;
	}

	// Draw the product image
	ctx.drawImage(
		productImage,
		productPosition.x,
		productPosition.y,
		productSize.width,
		productSize.height
	);

	// Clear shadow properties so they don't affect other elements
	if ( hasShadow ) {
		ctx.shadowColor = 'transparent';
		ctx.shadowBlur = 0;
		ctx.shadowOffsetX = 0;
		ctx.shadowOffsetY = 0;
	}

	// Draw other elements like box squares, etc.
	if ( onSave ) {
		const dataUrl = ctx.canvas.toDataURL();
		onSave( dataUrl );
	}
	drawImageControls(
		ctx,
		scalingFactor,
		productPosition,
		productSize,
		draggingConernerSize
	);
}

export const BackgroundProductGenerator = ( {
	onSave,
	backgroundImageSrc,
	productImageSrc,
	hasShadow = true,
}: {
	onSave?: ( dataUrl: string ) => void;
	backgroundImageSrc: string;
	productImageSrc: string;
	hasShadow?: boolean;
} ) => {
	const canvasRef = useRef< HTMLCanvasElement >( null );
	const [ imgPosition, setImgPosition ] = useState( { x: 0, y: 0 } );
	const [ scalingFactor, setScalingFactor ] = useState( 1 );
	const [ imgSize, setImgSize ] = useState( { width: 0, height: 0 } );
	const [ dragging, setDragging ] = useState( false );
	const [ resizing, setResizing ] = useState< null | position >( null );
	const [ initialOffset, setInitialOffset ] = useState( { x: 0, y: 0 } );
	const [ backgroundImage, setBackgroundImage ] =
		useState< HTMLImageElement | null >( null );
	const [ productImage, setProductImage ] =
		useState< HTMLImageElement | null >( null );

	useEffect( () => {
		if ( onSave && canvasRef.current && backgroundImage && productImage ) {
			const ctx = canvasRef.current.getContext( '2d' );
			if ( ! ctx ) return;

			renderScene(
				ctx,
				backgroundImage,
				productImage,
				imgPosition,
				imgSize,
				scalingFactor,
				hasShadow,
				onSave
			);
		}
	}, [
		backgroundImage,
		hasShadow,
		imgPosition,
		imgSize,
		onSave,
		productImage,
		scalingFactor,
	] );

	useEffect( () => {
		if ( canvasRef.current ) {
			const boundingRect = canvasRef.current.getBoundingClientRect();
			setScalingFactor( boundingRect.width / canvasRef.current.width );
		}
	}, [] );

	const convertToCanvasCoordinates = useCallback(
		( clientX: number, clientY: number ) => {
			if ( ! canvasRef.current ) return { x: 0, y: 0 };

			const rect = canvasRef.current.getBoundingClientRect();
			const x =
				( ( clientX - rect.left ) * canvasRef.current.width ) /
				rect.width;
			const y =
				( ( clientY - rect.top ) * canvasRef.current.height ) /
				rect.height;

			return { x, y };
		},
		[]
	);

	useEffect( () => {
		const loadImagesAndDraw = async () => {
			try {
				const canvas = canvasRef.current;
				const ctx = canvas?.getContext( '2d' );
				if ( ! ctx || ! canvas ) return;

				// Load both images
				const [ backgroundLoaded, productImageLoaded ] =
					await Promise.all( [
						loadImage( backgroundImageSrc ),
						loadImage( productImageSrc ),
					] );

				setBackgroundImage( backgroundLoaded );
				setProductImage( productImageLoaded );

				// Draw the background image
				if ( ! backgroundLoaded || ! productImageLoaded ) return;

				let productImageWidth = productImageLoaded.naturalWidth;
				let productImageHeight = productImageLoaded.naturalHeight;

				// Scale down image if it is greater than backgroundSize, else keep it as is
				if (
					productImageWidth > backgroundSize ||
					productImageHeight > backgroundSize
				) {
					const initialScalingFactor = Math.min(
						( backgroundSize * 0.75 ) / productImageWidth,
						( backgroundSize * 0.75 ) / productImageHeight
					);
					productImageWidth *= initialScalingFactor;
					productImageHeight *= initialScalingFactor;
				}

				// Center the product image
				const centerX = ( backgroundSize - productImageWidth ) / 2;
				const centerY = ( backgroundSize - productImageHeight ) / 2;

				setImgSize( {
					width: productImageWidth,
					height: productImageHeight,
				} );

				setImgPosition( { x: centerX, y: centerY } );

				renderScene(
					ctx,
					backgroundLoaded,
					productImageLoaded,
					{ x: centerX, y: centerY },
					{ width: productImageWidth, height: productImageHeight },
					scalingFactor,
					hasShadow
				);
			} catch ( error ) {}
		};

		loadImagesAndDraw();
	}, [ backgroundImageSrc, hasShadow, productImageSrc, scalingFactor ] );

	useEffect( () => {
		if ( ! canvasRef.current ) return;
		const ctx = canvasRef.current.getContext( '2d' );
		if ( ! ctx ) return;
		if ( ! backgroundImage || ! productImage ) return;

		renderScene(
			ctx,
			backgroundImage,
			productImage,
			imgPosition,
			imgSize,
			scalingFactor,
			hasShadow
		);
	}, [
		backgroundImage,
		hasShadow,
		imgPosition,
		imgSize,
		productImage,
		scalingFactor,
	] );

	const handleMouseDown = ( e: MouseEvent< HTMLCanvasElement > ) => {
		if ( ! canvasRef.current ) return;

		const { x, y } = convertToCanvasCoordinates( e.clientX, e.clientY );
		setInitialOffset( { x: x - imgPosition.x, y: y - imgPosition.y } );

		if (
			x >= imgPosition.x &&
			x <= imgPosition.x + imgSize.width &&
			y >= imgPosition.y &&
			y <= imgPosition.y + imgSize.height
		) {
			setDragging( true );
		}

		const cornerSize = draggingConernerSize;
		const clickedNW =
			x >= imgPosition.x - cornerSize / 2 &&
			x <= imgPosition.x + cornerSize / 2 &&
			y >= imgPosition.y - cornerSize / 2 &&
			y <= imgPosition.y + cornerSize / 2;
		const clickedNE =
			x >= imgPosition.x + imgSize.width - cornerSize / 2 &&
			x <= imgPosition.x + imgSize.width + cornerSize / 2 &&
			y >= imgPosition.y - cornerSize / 2 &&
			y <= imgPosition.y + cornerSize / 2;
		const clickedSW =
			x >= imgPosition.x - cornerSize / 2 &&
			x <= imgPosition.x + cornerSize / 2 &&
			y >= imgPosition.y + imgSize.height - cornerSize / 2 &&
			y <= imgPosition.y + imgSize.height + cornerSize / 2;
		const clickedSE =
			x >= imgPosition.x + imgSize.width - cornerSize / 2 &&
			x <= imgPosition.x + imgSize.width + cornerSize / 2 &&
			y >= imgPosition.y + imgSize.height - cornerSize / 2 &&
			y <= imgPosition.y + imgSize.height + cornerSize / 2;

		if ( clickedNW ) setResizing( 'nw' );
		else if ( clickedNE ) setResizing( 'ne' );
		else if ( clickedSW ) setResizing( 'sw' );
		else if ( clickedSE ) setResizing( 'se' );
	};

	const handleMouseMove = ( e: MouseEvent ) => {
		const { x, y } = convertToCanvasCoordinates( e.clientX, e.clientY );
		const aspectRatio = imgSize.width / imgSize.height;

		if ( dragging && canvasRef.current ) {
			const newX = x - initialOffset.x;
			const newY = y - initialOffset.y;
			setImgPosition( { x: newX, y: newY } );
		}

		if ( resizing && canvasRef.current ) {
			const { x: xPosition } = convertToCanvasCoordinates(
				e.clientX,
				e.clientY
			);

			let newWidth = imgSize.width;
			let newHeight = imgSize.height;
			let newX = imgPosition.x;
			let newY = imgPosition.y;

			if ( resizing === 'nw' ) {
				newWidth += imgPosition.x - xPosition;
				newHeight = newWidth / aspectRatio;
				newX = xPosition;
				newY = imgPosition.y + imgSize.height - newHeight;
			} else if ( resizing === 'ne' ) {
				newWidth = xPosition - imgPosition.x;
				newHeight = newWidth / aspectRatio;
				newY = imgPosition.y + imgSize.height - newHeight;
			} else if ( resizing === 'sw' ) {
				newWidth = imgPosition.x + imgSize.width - xPosition;
				newHeight = newWidth / aspectRatio;
				newX = xPosition;
			} else if ( resizing === 'se' ) {
				newWidth = xPosition - imgPosition.x;
				newHeight = newWidth / aspectRatio;
			}

			setImgSize( { width: newWidth, height: newHeight } );
			setImgPosition( { x: newX, y: newY } );
		}
	};

	const handleMouseUp = () => {
		setDragging( false );
		setResizing( null );
	};

	return (
		<canvas
			className="background-image-canvas"
			ref={ canvasRef }
			width={ backgroundSize }
			height={ backgroundSize }
			onMouseDown={ handleMouseDown }
			onMouseMove={ handleMouseMove }
			onMouseUp={ handleMouseUp }
			onMouseLeave={ handleMouseUp }
		/>
	);
};
