import { useGetLocation } from '@woocommerce/base-hooks';

( function () {
	const { createElement: el, Fragment } = wp.element;
	const { registerBlockType } = wp.blocks;

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
		'test/use-get-location',
		Object.assign(
			{
				title: 'Test useGetLocation hook',
			},
			baseBlock
		)
	);
} )();
