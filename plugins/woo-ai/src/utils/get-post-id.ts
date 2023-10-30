export const getPostId = (): number | null => {
	const postIdEl: HTMLInputElement | null =
		document.querySelector( '#post_ID' );

	return postIdEl ? Number( postIdEl.value ) : null;
};
