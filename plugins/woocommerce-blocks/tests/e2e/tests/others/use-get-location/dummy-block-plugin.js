( function () {
	const useGetLocation = require( '@woocommerce/base-hooks' ).useGetLocation;

	const { createElement: el, Fragment } = window.wp.element;
	const { registerBlockType } = window.wp.blocks;

	const baseBlock = {
		icon: 'cart',
		category: 'text',
		usesContext: [ 'templateSlug', 'postId' ],
		edit( { context, clientId } ) {
			const { type, sourceData } = useGetLocation( context, clientId );
			return el(
				Fragment,
				null,
				el( 'h2', {}, `Location type: ${ type }` ),
				el( 'h2', {}, `Source data: ${ JSON.stringify( sourceData ) }` )
			);
		},
		save() {
			return null;
		},
	};

	registerBlockType(
		'test/useGetLocation',
		Object.assign(
			{
				title: 'Test useGetLocation hook',
			},
			baseBlock
		)
	);
} )();
