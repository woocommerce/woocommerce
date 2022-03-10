/**
 * External dependencies
 */
import { createElement, Component, createRef } from '@wordpress/element';
import PropTypes from 'prop-types';

class ScrollTo extends Component {
	constructor( props ) {
		super( props );
		this.scrollTo = this.scrollTo.bind( this );
	}

	componentDidMount() {
		setTimeout( this.scrollTo, 250 );
	}

	scrollTo() {
		const { offset } = this.props;
		if ( this.ref.current && this.ref.current.offsetTop ) {
			window.scrollTo(
				0,
				this.ref.current.offsetTop + parseInt( offset, 10 )
			);
		} else {
			setTimeout( this.scrollTo, 250 );
		}
	}

	render() {
		const { children } = this.props;
		this.ref = createRef();
		return <span ref={ this.ref }>{ children }</span>;
	}
}

ScrollTo.propTypes = {
	/**
	 * The offset from the top of the component.
	 */
	offset: PropTypes.string,
};

ScrollTo.defaultProps = {
	offset: '0',
};

export default ScrollTo;
