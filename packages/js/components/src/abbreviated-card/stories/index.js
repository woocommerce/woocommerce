/**
 * External dependencies
 */
import { page } from '@wordpress/icons';
/**
 * Internal dependencies
 */
import AbbreviatedCard from '..';

export const Basic = () => (
	<AbbreviatedCard icon={ page } href="#">
		<h3>Title</h3>
		<p>Abbreviated card content</p>
	</AbbreviatedCard>
);

export default {
	title: 'WooCommerce Admin/components/AbbreviatedCard',
	component: AbbreviatedCard,
};
