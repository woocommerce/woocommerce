/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wordpress/components';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import DatePickerContent from './content';
import DropdownButton from '../dropdown-button';

const shortDateFormat = __( 'MM/DD/YYYY', 'woocommerce' );

/**
 * Select a range of dates or single dates.
 */
class DateRangeFilterPicker extends Component {
	constructor( props ) {
		super( props );
		this.state = this.getResetState();

		this.update = this.update.bind( this );
		this.onSelect = this.onSelect.bind( this );
		this.isValidSelection = this.isValidSelection.bind( this );
		this.resetCustomValues = this.resetCustomValues.bind( this );
	}

	formatDate( date, format ) {
		if (
			date &&
			date._isAMomentObject &&
			typeof date.format === 'function'
		) {
			return date.format( format );
		}

		return '';
	}

	getResetState() {
		const { period, compare, before, after } = this.props.dateQuery;

		return {
			period,
			compare,
			before,
			after,
			focusedInput: 'startDate',
			afterText: this.formatDate( after, shortDateFormat ),
			beforeText: this.formatDate( before, shortDateFormat ),
			afterError: null,
			beforeError: null,
		};
	}

	update( update ) {
		this.setState( update );
	}

	onSelect( selectedTab, onClose ) {
		const { isoDateFormat, onRangeSelect } = this.props;
		return ( event ) => {
			const { period, compare, after, before } = this.state;
			const data = {
				period: selectedTab === 'custom' ? 'custom' : period,
				compare,
			};
			if ( selectedTab === 'custom' ) {
				data.after = this.formatDate( after, isoDateFormat );
				data.before = this.formatDate( before, isoDateFormat );
			} else {
				data.after = undefined;
				data.before = undefined;
			}
			onRangeSelect( data );
			onClose( event );
		};
	}

	getButtonLabel() {
		const { primaryDate, secondaryDate } = this.props.dateQuery;
		return [
			`${ primaryDate.label } (${ primaryDate.range })`,
			`${ __( 'vs.', 'woocommerce' ) } ${ secondaryDate.label } (${
				secondaryDate.range
			})`,
		];
	}

	isValidSelection( selectedTab ) {
		const { compare, after, before } = this.state;
		if ( selectedTab === 'custom' ) {
			return compare && after && before;
		}
		return true;
	}

	resetCustomValues() {
		this.setState( {
			after: null,
			before: null,
			focusedInput: 'startDate',
			afterText: '',
			beforeText: '',
			afterError: null,
			beforeError: null,
		} );
	}

	render() {
		const {
			period,
			compare,
			after,
			before,
			focusedInput,
			afterText,
			beforeText,
			afterError,
			beforeError,
		} = this.state;

		const {
			isViewportMobile,
			focusOnMount = true,
			popoverProps = { inline: true },
		} = this.props;
		const contentClasses = classnames(
			'woocommerce-filters-date__content',
			{
				'is-mobile': isViewportMobile,
			}
		);
		return (
			<div className="woocommerce-filters-filter">
				<span className="woocommerce-filters-label">
					{ __( 'Date range', 'woocommerce' ) }:
				</span>
				<Dropdown
					contentClassName={ contentClasses }
					position="bottom"
					expandOnMobile
					focusOnMount={ focusOnMount }
					popoverProps={ popoverProps }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<DropdownButton
							onClick={ onToggle }
							isOpen={ isOpen }
							labels={ this.getButtonLabel() }
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<DatePickerContent
							period={ period }
							compare={ compare }
							after={ after }
							before={ before }
							onUpdate={ this.update }
							onClose={ onClose }
							onSelect={ this.onSelect }
							isValidSelection={ this.isValidSelection }
							resetCustomValues={ this.resetCustomValues }
							focusedInput={ focusedInput }
							afterText={ afterText }
							beforeText={ beforeText }
							afterError={ afterError }
							beforeError={ beforeError }
							shortDateFormat={ shortDateFormat }
						/>
					) }
				/>
			</div>
		);
	}
}

DateRangeFilterPicker.propTypes = {
	/**
	 * Callback called when selection is made.
	 */
	onRangeSelect: PropTypes.func.isRequired,
	/**
	 * The date query string represented in object form.
	 */
	dateQuery: PropTypes.shape( {
		period: PropTypes.string.isRequired,
		compare: PropTypes.string.isRequired,
		before: PropTypes.object,
		after: PropTypes.object,
		primaryDate: PropTypes.shape( {
			label: PropTypes.string.isRequired,
			range: PropTypes.string.isRequired,
		} ).isRequired,
		secondaryDate: PropTypes.shape( {
			label: PropTypes.string.isRequired,
			range: PropTypes.string.isRequired,
		} ).isRequired,
	} ).isRequired,
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
} )( DateRangeFilterPicker );
