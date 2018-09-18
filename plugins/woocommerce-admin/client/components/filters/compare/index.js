/** @format */
/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { isEqual } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import { getIdsFromQuery, updateQueryString } from 'lib/nav-utils';
import Search from 'components/search';

/**
 * Displays a card + search used to filter results as a comparison between objects.
 */
class CompareFilter extends Component {
	constructor( { getLabels, param, query } ) {
		super( ...arguments );
		this.state = {
			selected: [],
		};

		this.updateQuery = this.updateQuery.bind( this );
		this.updateLabels = this.updateLabels.bind( this );

		if ( query[ param ] ) {
			getLabels( query[ param ] ).then( this.updateLabels );
		}
	}

	componentDidUpdate( { param: prevParam, query: prevQuery } ) {
		const { getLabels, param, path, query } = this.props;
		if ( prevParam !== param ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { selected: [] } );
			/* eslint-enable react/no-did-update-set-state */
			updateQueryString( { [ param ]: '' }, path, query );
			return;
		}

		const prevIds = getIdsFromQuery( prevQuery[ param ] );
		const currentIds = getIdsFromQuery( query[ param ] );
		if ( ! isEqual( prevIds.sort(), currentIds.sort() ) ) {
			getLabels( query[ param ] ).then( this.updateLabels );
		}
	}

	updateLabels( data ) {
		const selected = data.map( p => ( { id: p.id, label: p.name } ) );
		this.setState( { selected } );
	}

	updateQuery() {
		const { param, path, query } = this.props;
		const { selected } = this.state;
		const idList = selected.map( p => p.id );
		updateQueryString( { [ param ]: idList.join( ',' ) }, path, query );
	}

	render() {
		const { labels, type } = this.props;
		const { selected } = this.state;
		return (
			<Card title={ labels.title } className="woocommerce-filters__compare">
				<div className="woocommerce-filters__compare-body">
					<Search
						type={ type }
						selected={ selected }
						onChange={ value => {
							this.setState( { selected: value } );
						} }
					/>
				</div>
				<div className="woocommerce-filters__compare-footer">
					<Button onClick={ this.updateQuery } isDefault>
						{ labels.update }
					</Button>
				</div>
			</Card>
		);
	}
}

CompareFilter.propTypes = {
	/**
	 * Function used to fetch object labels via an API request, returns a Promise.
	 */
	getLabels: PropTypes.func.isRequired,
	/**
	 * Object of localized labels.
	 */
	labels: PropTypes.shape( {
		/**
		 * Label for the card title.
		 */
		title: PropTypes.string,
		/**
		 * Label for button which updates the URL/report.
		 */
		update: PropTypes.string,
	} ),
	/**
	 * The parameter to use in the querystring.
	 */
	param: PropTypes.string.isRequired,
	/**
	 * The `path` parameter supplied by React-Router
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
	/**
	 * Which type of autocompleter should be used in the Search
	 */
	type: PropTypes.string.isRequired,
};

CompareFilter.defaultProps = {
	labels: {},
	query: {},
};

export default CompareFilter;
