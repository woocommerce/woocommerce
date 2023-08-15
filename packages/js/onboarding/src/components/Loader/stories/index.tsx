/**
 * Internal dependencies
 */
import { Loader } from '../';

export const ExampleLoader = () => (
	<Loader>
        <Loader.Layout>
            <Loader.Illustration>
                <img src="https://placekitten.com/200/200" />
            </Loader.Illustration>
            <Loader.Title>Very Impressive Title</Loader.Title>
            <Loader.ProgressBar progress={ 30 } />
            <Loader.Sequence interval={ 1000 }>
                <Loader.Subtext>
                    Message 1
                </Loader.Subtext>
                <Loader.Subtext>
                    Message 2
                </Loader.Subtext>
                <Loader.Subtext>
                    Message 3
                </Loader.Subtext>
            </Loader.Sequence>
        </Loader.Layout>
    </Loader>
);

export default {
	title: 'WooCommerce Admin/Onboarding/Loader',
	component: Loader,
};
