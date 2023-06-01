export const getPostId = () =>
	Number(
		( document.querySelector( '#post_ID' ) as HTMLInputElement )?.value
	);
