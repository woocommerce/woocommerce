/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Component } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
} from '@wordpress/components';
import { isEqual, isFunction } from 'lodash';
import PropTypes from 'prop-types';
import { getIdsFromQuery, updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import CompareButton from './button';
import Search from '../search';
import { Text } from '../experimental';

export { default as CompareButton } from './button';

/**
 * Displays a card + search used to filter results as a comparison between objects.
 */
export class CompareFilter extends Component {
	constructor( { getLabels, param, query } ) {
		super( ...arguments );
		this.state = {
			selected: [],
		};
		this.clearQuery = this.clearQuery.bind( this );
		this.updateQuery = this.updateQuery.bind( this );
		this.updateLabels = this.updateLabels.bind( this );
		this.onButtonClicked = this.onButtonClicked.bind( this );
		if ( query[ param ] ) {
			getLabels( query[ param ], query ).then( this.updateLabels );
		}
	}

	componentDidUpdate(
		{ param: prevParam, query: prevQuery },
		{ selected: prevSelected }
	) {
		const { getLabels, param, query } = this.props;
		const { selected } = this.state;
		if (
			prevParam !== param ||
			( prevSelected.length > 0 && selected.length === 0 )
		) {
			this.clearQuery();
			return;
		}

		const prevIds = getIdsFromQuery( prevQuery[ param ] );
		const currentIds = getIdsFromQuery( query[ param ] );
		if ( ! isEqual( prevIds.sort(), currentIds.sort() ) ) {
			getLabels( query[ param ], query ).then( this.updateLabels );
		}
	}

	clearQuery() {
		const { param, path, query } = this.props;

		this.setState( {
			selected: [],
		} );

		updateQueryString( { [ param ]: undefined }, path, query );
	}

	updateLabels( selected ) {
		this.setState( { selected } );
	}

	updateQuery() {
		const { param, path, query } = this.props;
		const { selected } = this.state;
		const idList = selected.map( ( p ) => p.key );
		updateQueryString( { [ param ]: idList.join( ',' ) }, path, query );
	}

	onButtonClicked( e ) {
		this.updateQuery( e );
		if ( isFunction( this.props.onClick ) ) {
			this.props.onClick( e );
		}
	}

	render() {
		const { labels, type, autocompleter } = this.props;
		const { selected } = this.state;
		return (
			<Card className="woocommerce-filters__compare">
				<CardHeader>
					<Text
						variant="subtitle.small"
						weight="600"
						size="14"
						lineHeight="20px"
					>
						{ labels.title }
					</Text>
				</CardHeader>
				<CardBody>
					<Search
						autocompleter={ autocompleter }
						type={ type }
						selected={ selected }
						placeholder={ labels.placeholder }
						onChange={ ( value ) => {
							this.setState( { selected: value } );
						} }
					/>
				</CardBody>
				<CardFooter justify="flex-start">
					<CompareButton
						count={ selected.length }
						helpText={ labels.helpText }
						onClick={ this.onButtonClicked }
					>
						{ labels.update }
					</CompareButton>
					{ selected.length > 0 && (
						<Button isLink={ true } onClick={ this.clearQuery }>
							{ __( 'Clear all', 'woocommerce' ) }
						</Button>
					) }
				</CardFooter>
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
		 * Label for the search placeholder.
		 */
		placeholder: PropTypes.string,
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
	/**
	 * The custom autocompleter to be forwarded to the `Search` component.
	 */
	autocompleter: PropTypes.object,
};

CompareFilter.defaultProps = {
	labels: {},
	query: {},
};
