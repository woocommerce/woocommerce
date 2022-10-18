/**
 * External dependencies
 */
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { Modal, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from './data/constants';
import { default as OptionEditor } from './OptionEditor';
import './data';

function shorten( input ) {
	if ( input.length > 20 ) {
		return input.substring( 0, 20 ) + '...';
	}

	return input;
}

function Options( {
	options,
	getOptions,
	deleteOption,
	isLoading,
	invalidateResolution,
	getOptionForEditing,
	editingOption,
	saveOption,
	notice,
	setNotice,
} ) {
	const [ isEditModalOpen, setEditModalOpen ] = useState( false );

	const deleteOptionByName = ( optionName ) => {
		// eslint-disable-next-line no-alert
		if ( confirm( 'Are you sure you want to delete this option?' ) ) {
			deleteOption( optionName );
		}
	};

	const openEditModal = ( optionName ) => {
		invalidateResolution( STORE_KEY, 'getOptionForEditing', [
			optionName,
		] );

		getOptionForEditing( optionName );
		setEditModalOpen( true );
	};

	const handleSaveOption = ( optionName, newValue ) => {
		saveOption( optionName, newValue );
		setEditModalOpen( false );
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
		if ( options.length === 0 ) {
			return (
				<tr>
					<td colSpan="6" align="center">
						No Options Found
					</td>
				</tr>
			);
		}

		return options.map( ( option ) => {
			// eslint-disable-next-line camelcase
			const { option_id, option_name, option_value, autoload } = option;

			// eslint-disable-next-line camelcase
			const optionId = option_id;
			// eslint-disable-next-line camelcase
			const optionName = option_name;
			// eslint-disable-next-line camelcase
			const optionValue = shorten( option_value );

			return (
				<tr key={ optionId }>
					<td key={ 0 }>{ optionId }</td>
					<td key={ 1 }>{ optionName }</td>
					<td key={ 'optionValue' }>{ optionValue }</td>
					<td className="align-center" key={ 2 }>
						{ autoload }
					</td>
					<td className="align-center" key={ 3 }>
						<button
							className="button btn-danger"
							onClick={ () => deleteOptionByName( optionName ) }
						>
							Delete
						</button>
					</td>
					<td className="align-center" key={ 4 }>
						<button
							className="button btn-primary"
							onClick={ () => openEditModal( optionName ) }
						>
							Edit
						</button>
					</td>
				</tr>
			);
		} );
	};

	const searchOption = ( event ) => {
		event.preventDefault();
		const keyword = event.target.search.value;

		// Invalidate resolution of the same selector + arg
		// so that entering the same keyword always works
		invalidateResolution( STORE_KEY, 'getOptions', [ keyword ] );

		getOptions( keyword );
	};

	return (
		<>
			{ isEditModalOpen && (
				<Modal
					title={ editingOption.name }
					onRequestClose={ () => {
						setEditModalOpen( false );
					} }
				>
					<OptionEditor
						option={ editingOption }
						onSave={ handleSaveOption }
					></OptionEditor>
				</Modal>
			) }
			<div id="wc-admin-test-helper-options">
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
				<form onSubmit={ searchOption }>
					<div className="search-box">
						<label
							className="screen-reader-text"
							htmlFor="post-search-input"
						>
							Search options:
						</label>
						<input type="search" name="search" />
						<input
							type="submit"
							id="search-submit"
							className="button"
							value="Search options"
						/>
					</div>
				</form>
				<div className="clear"></div>
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
								Name
							</td>
							<td
								className="manage-column column-thumb"
								key={ 'optionValue' }
							>
								Value
							</td>
							<td
								className="manage-column column-thumb align-center"
								key={ 2 }
							>
								Autoload
							</td>
							<td
								className="manage-column column-thumb align-center"
								key={ 3 }
							>
								Delete
							</td>
							<td
								className="manage-column column-thumb align-center"
								key={ 4 }
							>
								Edit
							</td>
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
		const {
			getOptions,
			getOptionForEditing,
			getNotice,
			isLoading,
		} = select( STORE_KEY );
		const options = getOptions();
		const editingOption = getOptionForEditing();
		const notice = getNotice();

		return {
			options,
			getOptions,
			isLoading: isLoading(),
			editingOption,
			getOptionForEditing,
			notice,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { deleteOption, saveOption, setNotice } = dispatch( STORE_KEY );
		const { invalidateResolution } = dispatch( 'core/data' );

		return {
			deleteOption,
			invalidateResolution,
			saveOption,
			setNotice,
		};
	} )
)( Options );
