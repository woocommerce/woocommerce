type TinyContent = {
	getContent: () => string;
	setContent: ( str: string ) => void;
	id: string;
};

declare const tinymce: {
	get: ( str: string ) => TinyContent;
	editors: TinyContent[];
};

const getTinyContentObject = ( editorId = 'content' ) =>
	typeof tinymce === 'object'
		? tinymce.editors.find(
				( editor: { id: string } ) => editor.id === editorId
		  )
		: null;

export const setTinyContent = ( str: string, editorId?: string ) => {
	if ( ! str.length ) {
		return;
	}

	const contentTinyMCE = getTinyContentObject( editorId );

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

export const getTinyContent = ( editorId?: string ) => {
	return getTinyContentObject( editorId )?.getContent();
};
