const generateHookName = ( hook ) => {
	const hookName = hook.name;
	const tags = hook.doc.tags || [];

	const isDeprecated = tags.find(
		( { name: tagName } ) => tagName === 'deprecated'
	);

	// Dynamic names like `__experimental_woocommerce_{$product_type}_…` go in a
	// code span: otherwise CommonMark reads some of the underscores next to the
	// braces as emphasis, which garbles the rendered heading and its anchor.
	const displayName = hookName.includes( '{$' )
		? `\`${ hookName }\``
		: hookName;

	return [
		{
			h2: isDeprecated ? `~~${ displayName }~~` : `${ displayName }`,
		},
	];
};

module.exports = { generateHookName };
