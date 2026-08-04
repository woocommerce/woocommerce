import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';

import { config } from '../config.js';

function padTo( png, width, height ) {
	if ( png.width === width && png.height === height ) {
		return png;
	}
	const out = new PNG( { width, height } );
	out.data.fill( 255 );
	PNG.bitblt( png, out, 0, 0, png.width, png.height, 0, 0 );
	return out;
}

export function diffImages( editorBuffer, emailBuffer ) {
	const a = PNG.sync.read( editorBuffer );
	const b = PNG.sync.read( emailBuffer );
	const width = Math.max( a.width, b.width );
	const height = Math.max( a.height, b.height );
	const pa = padTo( a, width, height );
	const pb = padTo( b, width, height );

	const diff = new PNG( { width, height } );
	const diffPixels = pixelmatch( pa.data, pb.data, diff.data, width, height, {
		threshold: config.pixelThreshold,
	} );

	return {
		diffBuffer: PNG.sync.write( diff ),
		diffPixels,
		totalPixels: width * height,
		diffPct: +( ( 100 * diffPixels ) / ( width * height ) ).toFixed( 2 ),
		sizes: {
			editor: { width: a.width, height: a.height },
			email: { width: b.width, height: b.height },
		},
	};
}
