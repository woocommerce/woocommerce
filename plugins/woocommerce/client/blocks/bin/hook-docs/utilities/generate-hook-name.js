const generateHookName = ( hook ) => {
	const hookName = hook.name;
	const tags = hook.doc.tags || [];

	const isDeprecated = tags.find(
		( { name: tagName } ) => tagName === 'deprecated'
	);

	return [
		{
			h2: isDeprecated ? `~~${ hookName }~~` : `${ hookName }`,
		},
	];
};

module.exports = { generateHookName };
