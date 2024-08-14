/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { ORDERS_STORE_NAME } from '@woocommerce/data';
import { ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const metaKey = '_wcpay_mode';

const Payments = () => {
	const {
		orders = [],
		isRequesting,
		isError,
	} = useSelect( ( select ) => {
		const { getOrders, hasFinishedResolution, getOrdersError } =
			select( ORDERS_STORE_NAME );

		const query = {
			page: 1,
			per_page: 10,
		};
		const orders = getOrders( query, null );
		const isRequesting = hasFinishedResolution( 'getOrders', [ query ] );

		return {
			orders,
			isError: Boolean( getOrdersError( orders ) ),
			isRequesting,
		};
	} );

	const { getOrderSuccess } = useDispatch( ORDERS_STORE_NAME );

	const isTestOrder = ( order ) =>
		order.meta_data.find( ( metaItem ) => metaItem.key === metaKey )
			?.value === 'test';

	const onToggle = async ( order, isChecked ) => {
		const data = {
			meta_data: [
				{
					key: metaKey,
					value: isChecked ? 'test' : 'live',
				},
			],
		};

		try {
			const updatedOrder = await apiFetch( {
				path: `/wc/v3/orders/${ order.id }`,
				method: 'PUT',
				data: data,
				headers: {
					'Content-Type': 'application/json',
				},
			} );
			getOrderSuccess( order.id, updatedOrder );
		} catch ( error ) {
			throw error;
		}
	};

	const renderOrders = ( orders ) => {
		return orders.map( ( order ) => {
			return (
				<tr key={ order.id }>
					<td className="manage-column column-thumb" key={ 0 }>
						{ `${ order?.billing?.first_name } ${ order?.billing?.last_name }` }
					</td>
					<td className="manage-column column-thumb" key={ 1 }>
						{ order.id }
					</td>
					<td
						className="manage-column column-thumb"
						key={ 'optionValue' }
					>
						{ order.date_created_gmt }
					</td>
					<td
						className="manage-column column-thumb align-center"
						key={ 2 }
					>
						{ order.status }
					</td>
					<td
						className="manage-column column-thumb align-center"
						key={ 3 }
					>
						{ order.total }
					</td>
					<td
						className="manage-column column-thumb align-center"
						key={ 4 }
					>
						<ToggleControl
							checked={ isTestOrder( order ) }
							onChange={ ( isChecked ) =>
								onToggle( order, isChecked )
							}
						/>
					</td>
				</tr>
			);
		} );
	};

	return (
		<>
			<h2>WooCommerce Payments</h2>
			<table className="wp-list-table striped table-view-list widefat">
				<thead>
					<tr>
						<td className="manage-column column-thumb" key={ 0 }>
							Order
						</td>
						<td className="manage-column column-thumb" key={ 1 }>
							ID
						</td>
						<td
							className="manage-column column-thumb"
							key={ 'optionValue' }
						>
							Date
						</td>
						<td
							className="manage-column column-thumb align-center"
							key={ 2 }
						>
							Status
						</td>
						<td
							className="manage-column column-thumb align-center"
							key={ 3 }
						>
							Total
						</td>
						<td
							className="manage-column column-thumb align-center"
							key={ 4 }
						>
							WCPay Test Order
						</td>
					</tr>
				</thead>
				<tbody>
					{ ! isRequesting &&
						orders?.length &&
						renderOrders( orders ) }
				</tbody>
			</table>
			{ ! isRequesting && orders?.length === 0 && (
				<p>No orders found.</p>
			) }
		</>
	);
};

export default Payments;
