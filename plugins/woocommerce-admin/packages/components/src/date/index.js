/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { format as formatDate } from '@wordpress/date';

/**
 * Use the `Date` component to display accessible dates or times.
 *
 * @param root0
 * @param root0.date
 * @param root0.machineFormat
 * @param root0.screenReaderFormat
 * @param root0.visibleFormat
 * @return {Object} -
 */
const Date = ( { date, machineFormat, screenReaderFormat, visibleFormat } ) => {
	return (
		<time dateTime={ formatDate( machineFormat, date ) }>
			<span aria-hidden="true">
				{ formatDate( visibleFormat, date ) }
			</span>
			<span className="screen-reader-text">
				{ formatDate( screenReaderFormat, date ) }
			</span>
		</time>
	);
};

Date.propTypes = {
	/**
	 * Date to use in the component.
	 */
	date: PropTypes.oneOfType( [ PropTypes.string, PropTypes.object ] )
		.isRequired,
	/**
	 * Date format used in the `datetime` prop of the `time` element.
	 */
	machineFormat: PropTypes.string,
	/**
	 * Date format used for screen readers.
	 */
	screenReaderFormat: PropTypes.string,
	/**
	 * Date format displayed in the page.
	 */
	visibleFormat: PropTypes.string,
};

Date.defaultProps = {
	machineFormat: 'Y-m-d H:i:s',
	screenReaderFormat: 'F j, Y',
	visibleFormat: 'Y-m-d',
};

export default Date;
