export const isURL = ( urlString: string ): boolean => {
	try {
		const url = new URL( urlString );
		return url.protocol === 'http:' || url.protocol === 'https:';
	} catch ( e ) {
		return false;
	}
};
