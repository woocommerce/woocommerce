/**
 * External dependencies
 */
import { Tag } from '@woocommerce/components';

export const Basic = () => (
	<>
		<Tag label="My tag" id={ 1 } />
		<Tag label="Removable tag" id={ 2 } remove={ () => {} } />
		<Tag
			label="Tag with popover"
			popoverContents={ <p>This is a popover</p> }
		/>
	</>
);

export default {
	title: 'WooCommerce Admin/components/Tag',
	component: Tag,
};
