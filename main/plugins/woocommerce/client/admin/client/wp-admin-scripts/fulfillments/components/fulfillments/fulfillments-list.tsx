/**
 * Internal dependencies
 */
import FulfillmentEditor from './fulfillment-editor';
import { useFulfillmentDrawerContext } from '../../context/drawer-context';

export default function FulfillmentsList() {
	const { fulfillments, openSection, setOpenSection, isEditing } =
		useFulfillmentDrawerContext();

	return (
		fulfillments.length > 0 && (
			<div className="woocommerce-fulfillment-stored-fulfillments-list">
				{ fulfillments.map( ( fulfillment, index ) => (
					<FulfillmentEditor
						index={ index }
						disabled={
							isEditing &&
							openSection !== 'fulfillment-' + fulfillment.id
						}
						expanded={
							openSection === 'fulfillment-' + fulfillment.id
						}
						onExpand={ () =>
							setOpenSection( 'fulfillment-' + fulfillment.id )
						}
						onCollapse={ () => setOpenSection( '' ) }
						key={ fulfillment.id }
						fulfillment={ fulfillment }
					/>
				) ) }
			</div>
		)
	);
}
