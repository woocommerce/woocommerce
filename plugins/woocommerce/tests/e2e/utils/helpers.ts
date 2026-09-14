/**
 * External dependencies
 */
import crypto from 'crypto';

const random = ( size = 4 ) => {
	return crypto.randomBytes( size ).toString( 'hex' );
};

export { random };
