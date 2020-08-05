/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import PropTypes from 'prop-types';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

/**
 * Internal dependencies
 */
import Link from '../link';

/**
 * List component to display a list of items.
 */
class List extends Component {
	handleKeyDown( event, onClick ) {
		if ( typeof onClick === 'function' && event.keyCode === ENTER ) {
			onClick();
		}
	}

	getItemLinkType( item ) {
		const { href, linkType } = item;

		if ( linkType ) {
			return linkType;
		}

		return href ? 'external' : null;
	}

	render() {
		const { className, items } = this.props;
		const listClassName = classnames( 'woocommerce-list', className );

		return (
			<TransitionGroup
				component="ul"
				className={ listClassName }
				role="menu"
			>
				{ items.map( ( item, index ) => {
					const {
						after,
						before,
						className: itemClasses,
						content,
						href,
						key,
						listItemTag,
						onClick,
						target,
						title,
					} = item;
					const hasAction = typeof onClick === 'function' || href;
					const itemClassName = classnames(
						'woocommerce-list__item',
						itemClasses,
						{
							'has-action': hasAction,
						}
					);
					const InnerTag = href ? Link : 'div';

					const innerTagProps = {
						className: 'woocommerce-list__item-inner',
						onClick: typeof onClick === 'function' ? onClick : null,
						'aria-disabled': hasAction ? 'false' : null,
						tabIndex: hasAction ? '0' : null,
						role: hasAction ? 'menuitem' : null,
						onKeyDown: ( e ) =>
							hasAction ? this.handleKeyDown( e, onClick ) : null,
						target: href ? target : null,
						type: this.getItemLinkType( item ),
						href,
						'data-list-item-tag': listItemTag,
					};

					return (
						<CSSTransition
							key={ key || index }
							timeout={ 500 }
							classNames="woocommerce-list__item"
						>
							<li className={ itemClassName }>
								<InnerTag { ...innerTagProps }>
									{ before && (
										<div className="woocommerce-list__item-before">
											{ before }
										</div>
									) }
									<div className="woocommerce-list__item-text">
										<span className="woocommerce-list__item-title">
											{ title }
										</span>
										{ content && (
											<span className="woocommerce-list__item-content">
												{ content }
											</span>
										) }
									</div>
									{ after && (
										<div className="woocommerce-list__item-after">
											{ after }
										</div>
									) }
								</InnerTag>
							</li>
						</CSSTransition>
					);
				} ) }
			</TransitionGroup>
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
			 * Content displayed after the list item text.
			 */
			after: PropTypes.node,
			/**
			 * Content displayed before the list item text.
			 */
			before: PropTypes.node,
			/**
			 * Additional class name to style the list item.
			 */
			className: PropTypes.string,
			/**
			 * Content displayed beneath the list item title.
			 */
			content: PropTypes.oneOfType( [
				PropTypes.string,
				PropTypes.node,
			] ),
			/**
			 * Href attribute used in a Link wrapped around the item.
			 */
			href: PropTypes.string,
			/**
			 * Called when the list item is clicked.
			 */
			onClick: PropTypes.func,
			/**
			 * Target attribute used for Link wrapper.
			 */
			target: PropTypes.string,
			/**
			 * Title displayed for the list item.
			 */
			title: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
		} )
	).isRequired,
};

export default List;
