export const htmlEntities = ( str: string ) =>
	str.replace(
		/[&<>'"]/g,
		( tag ) =>
			( {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				"'": '&#39;',
				'"': '&quot;',
			}[ tag ] || tag )
	);
