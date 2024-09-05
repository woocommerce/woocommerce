/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';

type UseProductSlugHook = {
	updateProductSlug: ( title: string, postId: number ) => Promise< void >;
};

declare global {
	interface Window {
		ajaxurl?: string;
	}
}

export const useProductSlug = (): UseProductSlugHook => {
	const slugInputRef = useRef< HTMLInputElement >(
		document.querySelector( '#post_name' )
	);

	const updateSlugInDOM = ( responseData: string ) => {
		const editSlugBox = document.getElementById( 'edit-slug-box' );
		if ( editSlugBox ) {
			editSlugBox.innerHTML = responseData;

			const newSlug = document.getElementById(
				'editable-post-name-full'
			)?.innerText;
			if ( newSlug && slugInputRef.current ) {
				slugInputRef.current.value = newSlug;
				slugInputRef.current.setAttribute( 'value', newSlug );
			}
		}
	};

	const updateProductSlug = async (
		title: string,
		postId: number
	): Promise< void > => {
		const ajaxUrl: string = window?.ajaxurl || '/wp-admin/admin-ajax.php';
		const samplePermalinkNonce = document
			.getElementById( 'samplepermalinknonce' )
			?.getAttribute( 'value' );

		if ( ! samplePermalinkNonce ) {
			throw new Error( 'Nonce could not be found in the DOM' );
		}

		const formData = new FormData();
		formData.append( 'action', 'sample-permalink' );
		formData.append( 'post_id', postId.toString() );
		formData.append( 'new_title', title );
		formData.append( 'new_slug', title );
		formData.append( 'samplepermalinknonce', samplePermalinkNonce );

		try {
			const response = await fetch( ajaxUrl, {
				method: 'POST',
				body: formData,
			} );

			const responseData = await response.text();

			if ( responseData !== '-1' ) {
				updateSlugInDOM( responseData );
			} else {
				throw new Error( 'Invalid response!' );
			}
		} catch ( e ) {
			throw new Error( "Couldn't update the slug!" );
		}
	};

	return {
		updateProductSlug,
	};
};
