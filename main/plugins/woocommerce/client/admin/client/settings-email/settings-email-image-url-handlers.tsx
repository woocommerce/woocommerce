export const selectImage = (
	inputId: string,
	setImageUrl: ( imageUrl: string ) => void
) => {
	let mediaSelector: wp.media.frame | undefined =
		window.wp.media.frames?.img_select;
	if ( mediaSelector ) {
		mediaSelector.open();
		return;
	}
	mediaSelector = window.wp.media( {
		library: {
			type: 'image',
		},
	} );
	const select_image = () => {
		if ( ! mediaSelector ) {
			return;
		}
		const sel = mediaSelector.state().get( 'selection' );
		if ( ! sel ) {
			return;
		}
		sel.each( ( item: { attributes: { url: string } } ) => {
			const url = item.attributes.url;
			const inputElement = document.getElementById(
				inputId
			) as HTMLInputElement;
			inputElement.value = url;
			inputElement.dispatchEvent( new Event( 'change' ) );
			setImageUrl( url );
		} );
	};
	mediaSelector.on( 'select', select_image );
	mediaSelector.open();
};

export const removeImage = (
	inputId: string,
	setImageUrl: ( imageUrl: string ) => void
) => {
	const inputElement = document.getElementById( inputId ) as HTMLInputElement;
	inputElement.value = '';
	inputElement.dispatchEvent( new Event( 'change' ) );
	setImageUrl( '' );
};
