/**
 * External dependencies
 */
import crypto from 'crypto';

/**
 * Generate a random hex string.
 *
 * @param size - Number of bytes to generate (default: 4)
 * @return Random hex string
 */
export const random = ( size = 4 ): string => {
	return crypto.randomBytes( size ).toString( 'hex' );
};
