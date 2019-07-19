/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import PropTypes from 'prop-types';

/**
 * List component to display a list of items.
 */
class List extends Component {
    handleKeyDown( event, onClick ) {
		if ( 'function' === typeof onClick && event.keyCode === ENTER ) {
			onClick();
		}
    }

	render() {
		const { className, items } = this.props;
		const listClassName = classnames( 'woocommerce-list', className );

		return (
			<ul className={ listClassName }>
				{ items.map( ( item, i ) => {
					const { after, before, className: itemClasses, description, onClick, title } = item;
					const hasAction = 'function' === typeof onClick;
					const itemClassName = classnames( 'woocommerce-list__item', itemClasses, {
						'has-action': hasAction,
					} );

					return (
						<li
							className={ itemClassName }
							key={ i }
							onClick={ hasAction ? onClick : null }
							onKeyDown={ ( e ) => hasAction ? this.handleKeyDown( e, onClick ) : null }
							role="menuitem"
							aria-disabled="false"
							tabIndex="0"
						>
                            { before &&
                                <div className="woocommerce-list__item-before">
                                    { before }
                                </div>
                            }
                            <div className="woocommerce-list__item-text">
                                <span className="woocommerce-list__item-title">
                                    { title }
                                </span>
                                { description &&
                                    <span className="woocommerce-list__item-description">
                                        { description }
                                    </span>
                                }
                            </div>
                            { after &&
                                <div className="woocommerce-list__item-after">
                                    { after }
                                </div>
                            }
						</li>
                    );
                } ) }
			</ul>
		);
	}
}

List.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * An array of list items.
	 */
	items: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Title displayed for the list item.
			 */
			title: PropTypes.string.isRequired,
			/**
			 * Description displayed beneath the list item title.
			 */
			description: PropTypes.string,
			/**
			 * Content displayed before the list item text.
			 */
			before: PropTypes.node,
			/**
			 * Content displayed after the list item text.
			 */
			after: PropTypes.node,
			/**
			 * Content displayed after the list item text.
			 */
			onClick: PropTypes.func,
			/**
			 * Additional class name to style the list item.
			 */
			className: PropTypes.string,
		} )
	).isRequired,
};

export default List;
