
export type EditPostVariables = {
	hooks?: string;
	database?: string;
	templates?: string;
	contributors?: string;
};

export const editPostHTML = ( postContent: string, postVariables: EditPostVariables ) => {
	return postContent.replaceAll( /<!-- release:([a-z]+) -->.*?<!-- \/release:\1 -->/gm, ( match, key: string ) => {
		return `<!-- release:${ key } -->${ postVariables[ key as keyof EditPostVariables ] || '' }<!-- /release:${ key } -->`;
	} );
};
