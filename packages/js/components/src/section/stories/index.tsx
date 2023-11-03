/**
 * External dependencies
 */
import { H, Section } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

export const Basic = () => (
	<div>
		<H>Header using a contextual level (h3)</H>
		<Section component="article">
			<p>This is an article component wrapper.</p>
			<H>Another header with contextual level (h4)</H>
			<Section component={ false }>
				<p>There is no wrapper component here.</p>
				<H>This is an h5</H>
			</Section>
		</Section>
	</div>
);

export default {
	title: 'WooCommerce Admin/components/Section',
	component: Section,
};
