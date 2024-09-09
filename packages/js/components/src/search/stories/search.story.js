/**
 * External dependencies
 */
import { H, Search, Section } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const SearchExample = () => {
	const [ selected, setSelected ] = useState( [] );
	const [ inlineSelected, setInlineSelect ] = useState( [] );

	return (
		<div>
			<H>Tags Below Input</H>
			<Section component={ false }>
				<Search
					type="products"
					placeholder="Search for a product"
					selected={ selected }
					onChange={ ( items ) => setSelected( items ) }
				/>
			</Section>
			<H>Tags Inline with Input</H>
			<Section component={ false }>
				<Search
					type="products"
					placeholder="Search for a product"
					selected={ inlineSelected }
					onChange={ ( items ) => setInlineSelect( items ) }
					inlineTags
				/>
			</Section>
		</div>
	);
};

export const Basic = () => <SearchExample />;

export default {
	title: 'WooCommerce Admin/components/Search',
	component: Search,
};
