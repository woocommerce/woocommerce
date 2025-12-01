const getHookFunction = ( hookType ) => {
	if ( hookType === 'action' ) {
		return 'do_action';
	}
	if ( hookType === 'action_reference' ) {
		return 'do_action_ref_array';
	}
	if ( hookType === 'filter_reference' ) {
		return 'apply_filters_ref_array';
	}
	return 'apply_filters';
};

const generateIntroduction = ( hook ) => {
	const hookName = hook.name;
	const hookType = hook.type;
	const hookFunction = getHookFunction( hookType );
	const tags = hook.doc.tags || [];

	const deprecated =
		tags.filter( ( { name: tagName } ) => tagName === 'deprecated' )[ 0 ] ||
		undefined;
	const internal =
		tags.filter( ( { name: tagName } ) => tagName === 'internal' )[ 0 ] ||
		undefined;
	const paramDocs =
		tags.filter( ( { name: tagName } ) => tagName === 'param' ) || [];

	const hookParams = paramDocs.map( ( { variable, types }, index ) => {
		const formattedType = types.join( '|' );
		const formattedVariable = variable ? variable : `$argument${ index }`;
		return `${ formattedType } ${ formattedVariable }`;
	} );

	const hookParamPrefix = hookFunction.includes( 'ref_array' ) ? '[ ' : '';
	const hookParamSuffix = hookFunction.includes( 'ref_array' ) ? ' ]' : '';

	const formattedHookParams = hookParams.length
		? ', ' + hookParamPrefix + hookParams.join( ', ' ) + hookParamSuffix
		: '';

	return [
		{ p: hook.doc.description },
		{
			code: {
				language: 'php',
				content: `${ hookFunction }( '${ hookName }'${ formattedHookParams } )`,
			},
		},
		deprecated
			? {
					p: `**Deprecated: ${
						deprecated.content
							? deprecated.content
							: 'This hook is deprecated and will be removed'
					}**`,
			  }
			: null,
		internal
			? {
					p: `**Note: ${
						internal.content
							? internal.content
							: 'This hook is for internal use only'
					}**`,
			  }
			: null,
	];
};

module.exports = { generateIntroduction };
