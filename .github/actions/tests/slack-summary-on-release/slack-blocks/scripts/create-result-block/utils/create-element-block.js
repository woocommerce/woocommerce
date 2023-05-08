const createElementBlock = ( testType, result ) => {
	const { selectEmoji } = require( './select-emoji' );

	const emoji = selectEmoji( result );

	return {
		type: 'mrkdwn',
		text: `${ testType.toUpperCase() } ${ emoji }`,
	};
};

module.exports = {
	createElementBlock,
};
