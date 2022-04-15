/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

export default {
	calendarLabel: __( 'Calendar', 'woocommerce' ),
	closeDatePicker: __( 'Close', 'woocommerce' ),
	focusStartDate: __(
		'Interact with the calendar and select start and end dates.',
		'woocommerce'
	),
	clearDate: __( 'Clear Date', 'woocommerce' ),
	clearDates: __( 'Clear Dates', 'woocommerce' ),
	jumpToPrevMonth: __(
		'Move backward to switch to the previous month.',
		'woocommerce'
	),
	jumpToNextMonth: __(
		'Move forward to switch to the next month.',
		'woocommerce'
	),
	enterKey: __( 'Enter key', 'woocommerce' ),
	leftArrowRightArrow: __( 'Right and left arrow keys', 'woocommerce' ),
	upArrowDownArrow: __( 'up and down arrow keys', 'woocommerce' ),
	pageUpPageDown: __( 'page up and page down keys', 'woocommerce' ),
	homeEnd: __( 'Home and end keys', 'woocommerce' ),
	escape: __( 'Escape key', 'woocommerce' ),
	questionMark: __( 'Question mark', 'woocommerce' ),
	selectFocusedDate: __( 'Select the date in focus.', 'woocommerce' ),
	moveFocusByOneDay: __(
		'Move backward (left) and forward (right) by one day.',
		'woocommerce'
	),
	moveFocusByOneWeek: __(
		'Move backward (up) and forward (down) by one week.',
		'woocommerce'
	),
	moveFocusByOneMonth: __( 'Switch months.', 'woocommerce' ),
	moveFocustoStartAndEndOfWeek: __(
		'Go to the first or last day of a week.',
		'woocommerce'
	),
	returnFocusToInput: __( 'Return to the date input field.', 'woocommerce' ),
	keyboardNavigationInstructions: __(
		'Press the down arrow key to interact with the calendar and select a date.',
		'woocommerce'
	),
	chooseAvailableStartDate: ( { date } ) =>
		sprintf( __( 'Select %s as a start date.', 'woocommerce' ), date ),
	chooseAvailableEndDate: ( { date } ) =>
		sprintf( __( 'Select %s as an end date.', 'woocommerce' ), date ),
	chooseAvailableDate: ( { date } ) => date,
	dateIsUnavailable: ( { date } ) =>
		sprintf( __( '%s is not selectable.', 'woocommerce' ), date ),
	dateIsSelected: ( { date } ) =>
		sprintf( __( 'Selected. %s', 'woocommerce' ), date ),
};
