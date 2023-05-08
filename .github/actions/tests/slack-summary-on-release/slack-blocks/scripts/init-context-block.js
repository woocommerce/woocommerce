const initContextBlock = ( testName ) => {
	return {
		type: 'context',
		elements: [
			{
				type: 'mrkdwn',
				text: `*${ testName }*`,
			},
			{
				type: 'mrkdwn',
				text: ' ',
			},
			{
				type: 'mrkdwn',
				text: ' ',
			},
		],
	};
};

module.exports = () => {
	initContextBlock;
};
