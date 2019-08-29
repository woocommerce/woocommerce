/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { find, get } from 'lodash';

/**
 * Internal dependencies
 */
import ComponentExample from './example';
import ComponentDocs from './docs';
import { Card, Link } from '@woocommerce/components';
import examples from './examples.json';
import './style.scss';

const camelCaseToSlug = name => {
	return name.replace( /\.?([A-Z])/g, ( x, y ) => '-' + y.toLowerCase() ).replace( /^-/, '' );
};

const getExampleData = example => {
	const componentName = get( example, 'component' );
	const filePath = get( example, 'filePath', camelCaseToSlug( componentName ) );
	const render = get( example, 'render', `My${ componentName }` );

	return {
		componentName,
		filePath,
		render,
	};
};

export default class extends Component {
	render() {
		const { params: { component } } = this.props;
		const className = classnames( 'woocommerce_devdocs', {
			'is-single': component,
			'is-list': ! component,
		} );

		let exampleList = examples;
		if ( component ) {
			const example = find( examples, ex => component === camelCaseToSlug( ex.component ) );
			exampleList = [ example ];
		}

		return (
			<div className={ className }>
				{ exampleList.map( example => {
					const { componentName, filePath, render, docPath } = getExampleData( example );
					const cardClasses = classnames(
						'woocommerce-devdocs__card',
						`woocommerce-devdocs__card--${ filePath }`,
						'woocommerce-analytics__card'
					);
					return (
						<Fragment key={ componentName }>
							<Card
								key={ `${ componentName }-example` }
								className={ cardClasses }
								title={
									component ? (
										componentName
									) : (
										<Link
											href={ `admin.php?page=wc-admin&path=/devdocs/${ filePath }` }
											type="wc-admin"
										>
											{ componentName }
										</Link>
									)
								}
								action={
									component ? (
										<Link href={ '?page=wc-admin&path=/devdocs' } type="wc-admin">
											Full list
										</Link>
									) : null
								}
							>
								<ComponentExample
									asyncName={ componentName }
									component={ componentName }
									filePath={ filePath }
									render={ render }
								/>
							</Card>
							{ component && (
								<ComponentDocs
									key={ `${ componentName }-readme` }
									componentName={ componentName }
									filePath={ filePath }
									docPath={ docPath }
								/>
							) }
						</Fragment>
					);
				} ) }
			</div>
		);
	}
}
