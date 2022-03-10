export function validateComponent( component ) {
	return ( props, propName, componentName ) => {
		// Not a required prop, we can drop early.
		if ( ! props[ propName ] ) {
			return;
		}
		if (
			! props[ propName ].type ||
			props[ propName ].type !== component
		) {
			return new Error(
				`Invalid ${ propName } passed to ${ componentName }. Must be ` +
					'`' +
					component.name +
					'`'
			);
		}
	};
}
