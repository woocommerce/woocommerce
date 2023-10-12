/**
 * Loads an image from a given source asynchronously.
 *
 * @param src The image source to load.
 * @return A promise that resolves to the loaded image.
 */
export const loadImage = ( src: string ): Promise< HTMLImageElement > => {
	return new Promise( ( resolve, reject ) => {
		const img = new Image();
		img.onload = () => resolve( img );
		img.onerror = ( error ) =>
			reject(
				new Error( `Failed to load image at ${ src }: ${ error }` )
			);
		img.src = src;
	} );
};

/**
 * Draws a line between the positions of 4 control boxes to manipulate images.
 *
 * @param ctx        The canvas context to draw on.
 * @param positions  The positions to draw the line between.
 * @param squareSize The size of the square to draw the line in.
 * @param factor     The factor to scale the line by.
 */
const drawLineJoiners = (
	ctx: CanvasRenderingContext2D,
	positions: { x: number; y: number }[],
	squareSize: number,
	factor = 1
) => {
	ctx.setLineDash( [ 5 / factor, 5 / factor ] );
	ctx.lineWidth = 4 / factor;
	ctx.beginPath();
	ctx.moveTo(
		positions[ 0 ].x + squareSize / 2,
		positions[ 0 ].y + squareSize / 2
	);
	ctx.lineTo(
		positions[ 1 ].x + squareSize / 2,
		positions[ 1 ].y + squareSize / 2
	);
	ctx.moveTo(
		positions[ 0 ].x + squareSize / 2,
		positions[ 0 ].y + squareSize / 2
	);
	ctx.lineTo(
		positions[ 2 ].x + squareSize / 2,
		positions[ 2 ].y + squareSize / 2
	);
	ctx.moveTo(
		positions[ 1 ].x + squareSize / 2,
		positions[ 1 ].y + squareSize / 2
	);
	ctx.lineTo(
		positions[ 3 ].x + squareSize / 2,
		positions[ 3 ].y + squareSize / 2
	);
	ctx.moveTo(
		positions[ 2 ].x + squareSize / 2,
		positions[ 2 ].y + squareSize / 2
	);
	ctx.lineTo(
		positions[ 3 ].x + squareSize / 2,
		positions[ 3 ].y + squareSize / 2
	);
	ctx.stroke();
	ctx.closePath();
};

/**
 * Draws the control boxes to manipulate images.
 *
 * @param positions  The positions to draw the control boxes at.
 * @param ctx        The canvas context to draw on.
 * @param squareSize The size of the square to draw the control boxes in.
 * @param factor     The factor to scale the control boxes by.
 */
const drawBoxControls = (
	positions: { x: number; y: number }[],
	ctx: CanvasRenderingContext2D,
	squareSize: number,
	factor = 1
) => {
	positions.forEach( ( pos ) => {
		ctx.setLineDash( [] );
		ctx.fillStyle = 'white';
		ctx.strokeStyle = 'black';
		ctx.lineWidth = 4 / factor;
		ctx.fillRect( pos.x, pos.y, squareSize, squareSize );
		ctx.strokeRect( pos.x, pos.y, squareSize, squareSize );
	} );
};

/**
 *  Draws the image controls (4 control boxes and 4 lines between them)
 *
 * @param ctx                  The canvas context to draw on.
 * @param scalingFactor        The factor to scale the line by.
 * @param imgPosition          The position of the image.
 * @param imgPosition.x        The x position of the image.
 * @param imgPosition.y        The y position of the image.
 * @param imgSize              The size of the image.
 * @param imgSize.width        The width of the image.
 * @param imgSize.height       The height of the image.
 * @param draggingConernerSize The size of the dragging corner.
 */
export const drawImageControls = (
	ctx: CanvasRenderingContext2D,
	scalingFactor: number,
	imgPosition: { x: number; y: number },
	imgSize: { width: number; height: number },
	draggingConernerSize: number
) => {
	const squareSize = draggingConernerSize / scalingFactor;
	const positions = [
		{
			x: imgPosition.x - squareSize / 2,
			y: imgPosition.y - squareSize / 2,
		},
		{
			x: imgPosition.x + imgSize.width - squareSize / 2,
			y: imgPosition.y - squareSize / 2,
		},
		{
			x: imgPosition.x - squareSize / 2,
			y: imgPosition.y + imgSize.height - squareSize / 2,
		},
		{
			x: imgPosition.x + imgSize.width - squareSize / 2,
			y: imgPosition.y + imgSize.height - squareSize / 2,
		}, // SE
	];

	drawLineJoiners( ctx, positions, squareSize, scalingFactor );
	drawBoxControls( positions, ctx, squareSize, scalingFactor );
};
