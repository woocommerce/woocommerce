/**
 * Internal dependencies
 */
import ExternalLinkCard from '../';

export default {
	title: 'WooCommerce Blocks/editor-components/ExternalLinkCard',
	component: ExternalLinkCard,
};

export const Default = () => (
	<ExternalLinkCard
		href="#link"
		title="Card Title"
		description="This is a description of the link."
	/>
);
