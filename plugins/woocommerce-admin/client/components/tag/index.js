/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import classnames from 'classnames';
import { Button, Dashicon, IconButton, Popover } from '@wordpress/components';
import PropTypes from 'prop-types';
import { withState, withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * This component can be used to show an item styled as a "tag", optionally with an `X` + "remove"
 * or with a popover that is shown on click.
 *
 * @return { object } -
 */
const Tag = ( {
	id,
	instanceId,
	isVisible,
	label,
	popoverContents,
	remove,
	screenReaderLabel,
	setState,
	className,
} ) => {
	screenReaderLabel = screenReaderLabel || label;
	if ( ! label ) {
		// A null label probably means something went wrong
		// @todo Maybe this should be a loading indicator?
		return null;
	}
	const classes = classnames( 'woocommerce-tag', className, {
		'has-remove': !! remove,
	} );
	const labelId = `woocommerce-tag__label-${ instanceId }`;
	const labelTextNode = (
		<Fragment>
			<span className="screen-reader-text">{ screenReaderLabel }</span>
			<span aria-hidden="true">{ label }</span>
		</Fragment>
	);

	return (
		<span className={ classes }>
			{ popoverContents ? (
				<Button
					className="woocommerce-tag__text"
					id={ labelId }
					onClick={ () => setState( () => ( { isVisible: true } ) ) }
					isToggled={ isVisible }
				>
					{ labelTextNode }
				</Button>
			) : (
				<span className="woocommerce-tag__text" id={ labelId }>
					{ labelTextNode }
				</span>
			) }
			{ popoverContents &&
				isVisible && (
					<Popover onClose={ () => setState( () => ( { isVisible: false } ) ) }>
						{ popoverContents }
					</Popover>
				) }
			{ remove && (
				<IconButton
					className="woocommerce-tag__remove"
					icon={ <Dashicon icon="dismiss" size={ 20 } /> }
					onClick={ remove( id ) }
					label={ sprintf( __( 'Remove %s', 'wc-admin' ), label ) }
					aria-describedby={ labelId }
				/>
			) }
		</span>
	);
};

Tag.propTypes = {
	/**
	 * The ID for this item, used in the remove function.
	 */
	id: PropTypes.number,
	/**
	 * The name for this item, displayed as the tag's text.
	 */
	label: PropTypes.string.isRequired,
	/**
	 * Contents to display on click in a popover
	 */
	popoverContents: PropTypes.node,
	/**
	 * A function called when the remove X is clicked. If not used, no X icon will display.
	 */
	remove: PropTypes.func,
	/**
	 * A more descriptive label for screen reader users. Defaults to the `name` prop.
	 */
	screenReaderLabel: PropTypes.string,
};

export default withState( {
	isVisible: false,
} )( withInstanceId( Tag ) );
