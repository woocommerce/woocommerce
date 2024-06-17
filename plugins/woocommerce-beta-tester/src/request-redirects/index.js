/**
 * External dependencies
 */
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './data/constants';
import './data';

function RequestRedirects( {
	redirectors,
	deleteRedirector,
	isLoading,
	saveRedirector,
	toggleRedirector,
} ) {
	const [ isNewModalOpen, setNewModalOpen ] = useState( false );
	const defaultModalData = {
		index: null,
		original_endpoint: '',
		new_endpoint: '',
		username: '',
		password: '',
		enabled: true,
	};

	const [ modalData, setModalData ] = useState( defaultModalData );

	const submitAddForm = ( e ) => {
		e.preventDefault();
		saveRedirector(
			e.target.original_endpoint.value,
			e.target.new_endpoint.value,
			e.target.username.value,
			e.target.password.value,
			e.target.enabled.value === 'true' ? true : false,
			e.target?.index?.value
		);
		setNewModalOpen( false );
	};

	const showEditModal = ( index ) => {
		const redirector = redirectors[ index ];
		setModalData( {
			...redirector,
			index,
		} );
		setNewModalOpen( true );
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
		if ( redirectors.length === 0 ) {
			return (
				<tr>
					<td colSpan="7" align="center">
						No Filters Found
					</td>
				</tr>
			);
		}

		return redirectors.map( ( redirector, index ) => {
			// eslint-disable-next-line camelcase
			const {
				original_endpoint: originalEndpoint,
				new_endpoint: newEndpoint,
				enabled,
				username,
			} = redirector;

			return (
				<tr key={ index }>
					<td>{ index + 1 }</td>
					<td>{ originalEndpoint }</td>
					<td className="align-center">{ newEndpoint }</td>
					<td className="align-center">{ username }</td>
					<td className="align-center">{ enabled ? 'Yes' : 'No' }</td>
					<td className="align-center">
						<button
							className="button btn-primary"
							onClick={ () => toggleRedirector( index ) }
						>
							Toggle
						</button>
					</td>
					<td className="align-center action-btns">
						<button
							className="button"
							onClick={ () => showEditModal( index ) }
						>
							Edit
						</button>
						<button
							className="button btn-danger"
							onClick={ () => {
								if (
									confirm(
										'Are you sure you want to delete this redirector?'
									)
								) {
									deleteRedirector( index );
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
			{ isNewModalOpen && (
				<Modal
					title={ modalData.index !== null ? 'Update' : 'Add New' }
					onRequestClose={ () => {
						setNewModalOpen( false );
					} }
				>
					<form
						className="request-redirects-new-form"
						onSubmit={ submitAddForm }
					>
						{ modalData.index !== null && (
							<input
								type="hidden"
								name="index"
								value={ modalData?.index }
							/>
						) }
						<input
							type="hidden"
							name="enabled"
							value={ modalData.enabled }
						/>
						<div className="grid">
							<label htmlFor="endpoint">Redirect From</label>
							<input
								type="text"
								name="original_endpoint"
								value={ modalData?.original_endpoint }
								onChange={ ( e ) =>
									setModalData( {
										...modalData,
										original_endpoint: e.target.value,
									} )
								}
							/>
							<label htmlFor="jsonPath">Redirect To</label>
							<input
								type="text"
								name="new_endpoint"
								value={ modalData?.new_endpoint }
								onChange={ ( e ) =>
									setModalData( {
										...modalData,
										new_endpoint: e.target.value,
									} )
								}
							/>
							<label htmlFor="username">
								Username (optional)
							</label>
							<input
								type="text"
								name="username"
								value={ modalData?.username }
								onChange={ ( e ) =>
									setModalData( {
										...modalData,
										username: e.target.value,
									} )
								}
							/>
							<label htmlFor="password">
								Password (optional){ ' ' }
							</label>
							<input
								type="password"
								name="password"
								value={ modalData?.password }
								onChange={ ( e ) =>
									setModalData( {
										...modalData,
										password: e.target.value,
									} )
								}
							/>
						</div>
						<input
							type="submit"
							value="Submit"
							className="button btn-new"
						/>
					</form>
				</Modal>
			) }
			<div id="wc-admin-test-helper-request-redirects">
				<input
					type="button"
					className="button btn-primary btn-new"
					value="Add New"
					onClick={ () => {
						setModalData( defaultModalData );
						setNewModalOpen( true );
					} }
				/>
				<br />
				<br />
				<table className="wp-list-table striped table-view-list widefat">
					<thead>
						<tr>
							<td className="manage-column column-thumb">I.D</td>
							<td className="manage-column column-thumb">
								Redirect From
							</td>
							<td className="manage-column column-thumb align-center">
								Redirect To
							</td>
							<td className="manage-column column-thumb align-center">
								Username
							</td>
							<td className="manage-column column-thumb align-center">
								Enabled
							</td>
							<td className="manage-column column-thumb align-center">
								Toggle
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
		const { getRedirectors, isLoading } = select( STORE_KEY );
		const redirectors = getRedirectors();

		return {
			redirectors,
			isLoading: isLoading(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { saveRedirector, deleteRedirector, toggleRedirector } =
			dispatch( STORE_KEY );

		return {
			saveRedirector,
			deleteRedirector,
			toggleRedirector,
		};
	} )
)( RequestRedirects );
