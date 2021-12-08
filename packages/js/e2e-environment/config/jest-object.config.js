/**
 * External Dependencies
 */
const { E2E_RETRY_TIMES } = process.env;

const setupJestRetries = ( retries = 2 ) => {
	const retryTimes = E2E_RETRY_TIMES ? E2E_RETRY_TIMES : retries;

	jest.retryTimes( retryTimes );
};

// If more methods are added to setupJestObject, it should be include in the readme
const setupJestObject = () => {
	setupJestRetries();
};

module.exports = {
	setupJestObject,
	setupJestRetries,
};
