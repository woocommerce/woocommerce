/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { Dashicon, IconButton } from '@wordpress/components';
import PropTypes from 'prop-types';

class AgendaHeader extends Component {
	constructor( props ) {
		super( ...arguments );
		this.state = {
			opened: props.initialOpen === undefined ? false : props.initialOpen,
		};
		this.toggle = this.toggle.bind( this );
	}

	toggle( event ) {
		event.preventDefault();
		event.stopPropagation();
		if ( this.props.opened === undefined ) {
			this.setState( state => ( {
				opened: ! state.opened,
			} ) );
		}

		if ( this.props.onToggle ) {
			this.props.onToggle();
		}
	}

	renderLink() {
		const { title, className, count, href } = this.props;
		const classes = classnames( 'woo-dash__agenda-group-wrapper', className );

		return (
			<a className={ classes } href={ href }>
				<div className="woo-dash__agenda-group-title is-link">
					<h3>
						{ count && <span>{ count }</span> }
						{ title }
					</h3>
					<span className="woo-dash__agenda-group-arrow">
						<Dashicon icon="arrow-right-alt2" />
					</span>
				</div>
			</a>
		);
	}

	render() {
		const { title, children, opened, className, count, href } = this.props;

		// Render a link instead of an accordion if `href` is passed.
		if ( href ) {
			return this.renderLink();
		}

		const isOpened = opened === undefined ? this.state.opened : opened;
		const classes = classnames( 'woo-dash__agenda-group-wrapper', className, {
			'is-opened': isOpened,
		} );
		const icon = `arrow-${ isOpened ? 'up-alt2' : 'down-alt2' }`;

		/* eslint-disable jsx-a11y/click-events-have-key-events */
		/* eslint-disable jsx-a11y/no-static-element-interactions */
		return (
			<div className={ classes }>
				<div onClick={ this.toggle } className="woo-dash__agenda-group-title is-accordion">
					<h3>
						{ count && <span>{ count }</span> }
						{ title }
					</h3>
					<IconButton onClick={ this.toggle } aria-expanded={ isOpened } icon={ icon } />
				</div>

				{ isOpened &&
					children && <div className="woo-dash__agenda-group-content"> { children } </div> }
			</div>
		);
		/* eslint-enable jsx-a11y/click-events-have-key-events */
		/* eslint-enable jsx-a11y/no-static-element-interactions */
	}
}

AgendaHeader.propTypes = {
	title: PropTypes.string.isRequired,
	className: PropTypes.string,
	count: PropTypes.number,
	initialOpen: PropTypes.bool,
	children: PropTypes.node,
	href: PropTypes.string,
};

export default AgendaHeader;
