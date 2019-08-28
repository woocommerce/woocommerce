/** @format */
/**
 * Internal dependencies
 */
import { H, Search, Section } from '@woocommerce/components';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

export default withState( {
	selected: [],
	inlineSelected: [],
} )( ( { selected, inlineSelected, setState } ) => (
	<div>
		<H>Tags Below Input</H>
		<Section component={ false }>
			<Search
				type="products"
				placeholder="Search for a product"
				selected={ selected }
				onChange={ items => setState( { selected: items } ) }
			/>
		</Section>
		<H>Tags Inline with Input</H>
		<Section component={ false }>
			<Search
				type="products"
				placeholder="Search for a product"
				selected={ inlineSelected }
				onChange={ items => setState( { inlineSelected: items } ) }
				inlineTags
			/>
		</Section>
	</div>
) );
