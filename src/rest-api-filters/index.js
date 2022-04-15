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

function RestAPIFilters( {
	filters,
	deleteFilter,
	isLoading,
	saveFilter,
	toggleFilter,
} ) {
	const [ isNewModalOpen, setNewModalOpen ] = useState( false );

	const submitAddForm = ( e ) => {
		e.preventDefault();
		saveFilter(
			e.target.endpoint.value,
			e.target.dotNotation.value,
			e.target.replacement.value
		);
		setNewModalOpen( false );
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
		if ( filters.length === 0 ) {
			return (
				<tr>
					<td colSpan="7" align="center">
						No Filters Found
					</td>
				</tr>
			);
		}

		return filters.map( ( filter, index ) => {
			// eslint-disable-next-line camelcase
	                const {
				endpoint,
				dot_notation: dotNotation,
				replacement,
				enabled,
			} = filter;

			// eslint-disable-next-line camelcase
			const dotNotation = dot_notation;
			return (
				<tr key={ index }>
					<td key={ 0 }>{ index + 1 }</td>
					<td key={ 1 }>{ endpoint }</td>
					<td key={ 'optionValue' }>{ dotNotation }</td>
					<td className="align-center" key={ 2 }>
						{ replacement + '' }
					</td>
					<td className="align-center" key={ 3 }>
						{ enabled + '' }
					</td>
					<td className="align-center" key={ 4 }>
						<button
							className="button btn-primary"
							onClick={ () => toggleFilter( index ) }
						>
							Toggle
						</button>
					</td>
					<td className="align-center" key={ 5 }>
						<button
							className="button btn-danger"
							onClick={ () => deleteFilter( index ) }
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
					title={ 'New Filter' }
					onRequestClose={ () => {
						setNewModalOpen( false );
					} }
				>
					<form
						className="rest-api-filter-new-form"
						onSubmit={ submitAddForm }
					>
						<div className="grid">
							<label htmlFor="endpoint">Endpoint</label>
							<input type="text" name="endpoint" autoFocus />
							<label htmlFor="jsonPath">Dot Notation</label>
							<input type="text" name="dotNotation" />
							<label htmlFor="replacement">Replacement </label>
							<input type="text" name="replacement" />
						</div>
						<input
							type="submit"
							value="Create New Filter"
							className="button btn-new"
						/>
					</form>
				</Modal>
			) }
			<div id="wc-admin-test-helper-rest-api-filters">
				<input
					type="button"
					className="button btn-primary btn-new"
					value="New Filter"
					onClick={ () => setNewModalOpen( true ) }
				/>
				<br />
				<br />
				<table className="wp-list-table striped table-view-list widefat">
					<thead>
						<tr>
							<td
								className="manage-column column-thumb"
								key={ 0 }
							>
								I.D
							</td>
							<td
								className="manage-column column-thumb"
								key={ 1 }
							>
								Endpoint
							</td>
							<td
								className="manage-column column-thumb"
								key={ 'optionValue' }
							>
								Dot Notation
							</td>
							<td
								className="manage-column column-thumb align-center"
								key={ 2 }
							>
								Replacement
							</td>
							<td
								className="manage-column column-thumb align-center"
								key={ 3 }
							>
								Enabled
							</td>
							<td
								className="manage-column column-thumb align-center"
								key={ 3 }
							>
								Toggle
							</td>
							<td
								className="manage-column column-thumb align-center"
								key={ 4 }
							></td>
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
		const { getFilters, isLoading } = select( STORE_KEY );
		const filters = getFilters();

		return {
			filters,
			isLoading: isLoading(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { saveFilter, deleteFilter, toggleFilter } = dispatch(
			STORE_KEY
		);

		return {
			saveFilter,
			deleteFilter,
			toggleFilter,
		};
	} )
)( RestAPIFilters );
