/**
 * External dependencies
 */
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './data/constants';
import './data';

function RemoteInboxNotifications( {
	notifications,
	deleteNotification,
	importNotifications,
	deleteAllNotifications,
	runNotification,
	isLoading,
	notice,
	setNotice,
} ) {
	const importFromEnv = async ( env ) => {
		const urls = {
			staging:
				'https://staging.woocommerce.com/wp-json/wccom/inbox-notifications/2.0/notifications.json',
			production:
				'https://woocommerce.com/wp-json/wccom/inbox-notifications/2.0/notifications.json',
		};

		try {
			const response = await fetch( urls[ env ] );
			const data = await response.json();
			importNotifications( data );
			setNotice( {
				message: 'Notifications imported successfully.',
				status: 'success',
			} );
		} catch ( error ) {
			if ( env === 'staging' ) {
				const messages = {
					staging:
						'Failed to fetch notifications. Please make sure you are connected to Automattic proxy.',
					production: error.message,
				};
				setNotice( {
					message: messages[ env ],
					status: 'error',
				} );
			}
		}
	};
	const renderLoading = () => {
		return (
			<tr>
				<td colSpan="6" align="center">
					Loading...
				</td>
			</tr>
		);
	};

	const renderTableData = () => {
		if ( notifications.length === 0 ) {
			return (
				<tr>
					<td colSpan="5" align="center">
						No Notifications Found
					</td>
				</tr>
			);
		}

		return notifications.map( ( notification, index ) => {
			return (
				<tr key={ index }>
					<td>{ notification.note_id }</td>
					<td>{ notification.name }</td>
					<td>{ notification.type }</td>
					<td>{ notification.status }</td>
					<td className="notification-actions">
						<button
							className="button btn"
							onClick={ () => {
								runNotification( notification.name );
							} }
						>
							Run
						</button>
						<button
							className="button btn-danger"
							onClick={ () => {
								if (
									confirm(
										'Are you sure you want to delete this notification?'
									)
								) {
									deleteNotification( notification.note_id );
								}
							} }
						>
							Delete
						</button>
					</td>
				</tr>
			);
		} );
	};

	return (
		<>
			<div id="wc-admin-test-helper-remote-inbox-notifications">
				<div className="action-btns">
					<input
						type="button"
						className="button btn-danger"
						value="Delete All"
						onClick={ () => {
							if (
								confirm(
									'Are you sure you want to delete all notifications?'
								)
							) {
								deleteAllNotifications();
							}
						} }
					/>

					<input
						type="button"
						className="button btn-primary staging"
						value="Import from staging"
						onClick={ () => {
							if (
								confirm(
									'Are you sure you want to import notifications from staging? Existing notifications will be overwritten.'
								)
							) {
								importFromEnv( 'staging' );
							}
						} }
					/>
					<input
						type="button"
						className="button btn-primary"
						value="Import from production"
						onClick={ () => {
							if (
								confirm(
									'Are you sure you want to import notifications from production? Existing notifications will be overwritten.'
								)
							) {
								importFromEnv( 'production' );
							}
						} }
					/>
				</div>
				{ notice.message.length > 0 && (
					<Notice
						status={ notice.status }
						onRemove={ () => {
							setNotice( { message: '' } );
						} }
					>
						{ notice.message }
					</Notice>
				) }
				<table className="wp-list-table striped table-view-list widefat">
					<thead>
						<tr>
							<td className="manage-column column-thumb">I.D</td>
							<td className="manage-column column-thumb">Name</td>
							<td className="manage-column column-thumb align-center">
								Type
							</td>
							<td className="manage-column column-thumb align-center">
								Status
							</td>
							<td className="manage-column column-thumb align-center"></td>
						</tr>
					</thead>
					<tbody>
						{ isLoading ? renderLoading() : renderTableData() }
					</tbody>
				</table>
			</div>
		</>
	);
}

export default compose(
	withSelect( ( select ) => {
		const { getNotifications, isLoading, getNotice } = select( STORE_KEY );
		const notifications = getNotifications();
		const notice = getNotice();

		return {
			notice,
			notifications,
			isLoading: isLoading(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const {
			deleteNotification,
			importNotifications,
			deleteAllNotifications,
			runNotification,
			setNotice,
		} = dispatch( STORE_KEY );

		return {
			runNotification,
			deleteAllNotifications,
			setNotice,
			deleteNotification,
			importNotifications,
		};
	} )
)( RemoteInboxNotifications );
