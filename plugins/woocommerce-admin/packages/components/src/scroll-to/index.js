/** @format */
/**
 * External dependencies
 */
import { Component, createRef } from '@wordpress/element';

class ScrollTo extends Component {
	constructor() {
		super();
		this.scrollTo = this.scrollTo.bind( this );
	}

	componentDidMount() {
		setTimeout( this.scrollTo, 250 );
	}

	scrollTo() {
		if ( this.ref.current && this.ref.current.offsetTop ) {
			window.scrollTo( 0, this.ref.current.offsetTop );
		} else {
			setTimeout( this.scrollTo, 250 );
		}
	}

	render() {
		this.ref = createRef();
		return (
			<span ref={ this.ref }></span>
		);
	}
}

export default ScrollTo;
