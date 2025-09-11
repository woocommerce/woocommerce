const crypto = require( 'crypto' );

const random = ( size = 4 ) => {
	return crypto.randomBytes( size ).toString( 'hex' );
};

module.exports = {
	random,
};
