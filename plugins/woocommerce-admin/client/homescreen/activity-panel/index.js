/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { Accordion, AccordionPanel } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { getUnreadOrders } from './orders/utils';
import { getAllPanels } from './panels';

export const ActivityPanel = () => {
	const panels = useSelect( ( select ) => {
		const countUnreadOrders = getUnreadOrders( select );
		return getAllPanels( { countUnreadOrders } );
	} );
	return (
		<Accordion>
			<Fragment>
				{ panels.map( ( panelData ) => {
					const {
						className,
						count,
						id,
						initialOpen,
						panel,
						title,
					} = panelData;
					return (
						<AccordionPanel
							key={ id }
							className={ className }
							count={ count }
							initialOpen={ initialOpen }
							title={ title }
						>
							{ panel }
						</AccordionPanel>
					);
				} ) }
			</Fragment>
		</Accordion>
	);
};
