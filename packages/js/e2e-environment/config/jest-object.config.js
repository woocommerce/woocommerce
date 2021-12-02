/**
 * External Dependencies
 */
const { E2E_RETRY_TIMES } = process.env;

const setupJestRetries = () => {
	const retryTimes = E2E_RETRY_TIMES ? E2E_RETRY_TIMES : 3;

	jest.retryTimes( retryTimes );
};

const setupJestObject = () => {
	setupJestRetries();
};

module.exports = setupJestObject;
