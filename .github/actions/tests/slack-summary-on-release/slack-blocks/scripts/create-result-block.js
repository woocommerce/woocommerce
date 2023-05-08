module.exports = () => {
	const { API_RESULT, E2E_RESULT, TEST_NAME } = process.env;
	const { initContextBlock } = require( './init-context-block' );
	const { createElementBlock } = require( './create-element-block' );

	const contextBlock = initContextBlock( TEST_NAME );
	const apiElementBlock = createElementBlock( 'API', API_RESULT );
	const e2eElementBlock = createElementBlock( 'E2E', E2E_RESULT );

	contextBlock.elements.push( apiElementBlock );
	contextBlock.elements.push( e2eElementBlock );

	return contextBlock;
};
