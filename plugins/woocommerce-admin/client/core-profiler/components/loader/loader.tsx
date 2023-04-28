/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import { CoreProfilerStateMachineContext } from '../..';

type Props = {
	title: string | JSX.Element;
	progress?: number;
	className?: string;
	paragraphs?: Array< {
		text: string | JSX.Element;
		duration?: number;
	} >;
};

export const Loader = ( { context }: CoreProfilerStateMachineContext ) => {
	return (
		<div
			className={ classNames(
				'woocommerce-profiler-loader',
				context.loader.className
			) }
		>
			<h1 className="woocommerce-profiler-loader__title">
				{ context.loader.title }
			</h1>
		</div>
	);
};
