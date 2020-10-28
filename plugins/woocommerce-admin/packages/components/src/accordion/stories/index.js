/**
 * External dependencies
 */
import { Accordion, AccordionPanel } from '@woocommerce/components';

export const Basic = () => (
	<Accordion>
		<AccordionPanel
			className="class-name"
			count={ 15 }
			title="Panel 1"
			initialOpen={ true }
		>
			<span>Panel 1 content</span>
		</AccordionPanel>
		<AccordionPanel
			className="class-name"
			count={ 20 }
			title="Panel 2"
			initialOpen={ false }
		>
			<span>Panel 2 content</span>
		</AccordionPanel>
	</Accordion>
);

export default {
	title: 'WooCommerce Admin/components/Accordion',
	component: Accordion,
};
