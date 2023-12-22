import { setupStore } from './utils.mjs';

const prompt = process.argv[ 2 ];

( async () => {
	try {
		const res = await setupStore( prompt );

		console.log( JSON.stringify( res ) );
	} catch ( e ) {}
} )();
