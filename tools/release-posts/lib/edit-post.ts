
export const editPostHTML = ( postContent, postVariables ) => {
	return postContent.replaceAll( /<!-- release:([a-z]+) -->.*?<!-- \/release:\1 -->/gm, ( match, key ) => {
		return `<!-- release:${ key } -->${ postVariables[ key ] || '' }<!-- /release:${ key } -->`;
	} );
};
