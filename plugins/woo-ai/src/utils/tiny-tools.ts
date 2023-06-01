type TinyContent = {
	getContent: () => string;
	setContent: ( str: string ) => void;
};

declare const tinymce: { get: ( str: string ) => TinyContent };

const getTinyContentObject = () =>
	typeof tinymce === 'object' ? tinymce.get( 'content' ) : null;

export const setTinyContent = ( str: string ) => {
	if ( ! str.length ) {
		return;
	}

	const contentTinyMCE = getTinyContentObject();
	if ( contentTinyMCE ) {
		contentTinyMCE.setContent( str );
	} else {
		{
			const el: HTMLInputElement | null = document.querySelector(
				'#wp-content-editor-container .wp-editor-area'
			);
			if ( el ) {
				el.value = str;
			}
		}
	}
};

export const getTinyContent = () => {
	return getTinyContentObject()?.getContent();
};
