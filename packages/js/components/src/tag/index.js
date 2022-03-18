/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createElement, Fragment, useState } from '@wordpress/element';
import classnames from 'classnames';
import { Button, Popover } from '@wordpress/components';
import { Icon, cancelCircleFilled } from '@wordpress/icons';
import { decodeEntities } from '@wordpress/html-entities';
import PropTypes from 'prop-types';
import { withInstanceId } from '@wordpress/compose';

/**
 * This component can be used to show an item styled as a "tag", optionally with an `X` + "remove"
 * or with a popover that is shown on click.
 *
 * @param {Object}        props
 * @param {number|string} props.id
 * @param {string}        props.instanceId
 * @param {string}        props.label
 * @param {Object}        props.popoverContents
 * @param {Function}      props.remove
 * @param {string}        props.screenReaderLabel
 * @param {string}        props.className
 * @return {Object} -
 */
const Tag = ( {
	id,
	instanceId,
	label,
	popoverContents,
	remove,
	screenReaderLabel,
	className,
} ) => {
	const [ isVisible, setIsVisible ] = useState( false );

	screenReaderLabel = screenReaderLabel || label;
	if ( ! label ) {
		// A null label probably means something went wrong
		// @todo Maybe this should be a loading indicator?
		return null;
	}
	label = decodeEntities( label );
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
					onClick={ () => setIsVisible( true ) }
				>
					{ labelTextNode }
				</Button>
			) : (
				<span className="woocommerce-tag__text" id={ labelId }>
					{ labelTextNode }
				</span>
			) }
			{ popoverContents && isVisible && (
				<Popover onClose={ () => setIsVisible( false ) }>
					{ popoverContents }
				</Popover>
			) }
			{ remove && (
				<Button
					className="woocommerce-tag__remove"
					onClick={ remove( id ) }
					label={ sprintf(
						__( 'Remove %s', 'woocommerce-admin' ),
						label
					) }
					aria-describedby={ labelId }
				>
					<Icon
						icon={ cancelCircleFilled }
						size={ 20 }
						className="clear-icon"
					/>
				</Button>
			) }
		</span>
	);
};

Tag.propTypes = {
	/**
	 * The ID for this item, used in the remove function.
	 */
	id: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),

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

export default withInstanceId( Tag );
