type TinyContent = {
	getContent: () => string;
	setContent: ( str: string ) => void;
};

declare const tinymce: { get: ( str: string ) => TinyContent };

export const useTinyEditor = () => {
	const getTinyContentObject = () =>
		typeof tinymce === 'object' ? tinymce.get( 'content' ) : null;

	const setTinyContent = ( str: string ) => {
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

	const getTinyContent = () => {
		return getTinyContentObject()?.getContent();
	};

	return { setContent: setTinyContent, getContent: getTinyContent };
};
