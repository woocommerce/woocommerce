/**
 * External dependencies
 */
// @ts-ignore
import { Card, CardBody, CardFooter, CardHeader, __experimentalHeading as Heading } from '@wordpress/components';
import { css } from '@emotion/react';

/**
 * Internal dependencies
 */
import { useLiveBranchesData } from "./hooks/live-branches";
import { BranchList } from './components/BranchList';


const cardStyle = css({
  marginTop: '32px'
})

export const App = () => {
  const prs = useLiveBranchesData();

  console.log(prs[0]);
     
  return (<>
    <Heading level={1}>
      Live Branches - Install and test WooCommerce PRs
    </Heading>
    <Card elevation={3} css={cardStyle} >
      <CardHeader><h2>Active PRs</h2></CardHeader>
      <CardBody>
        <BranchList branches={prs} />
      </CardBody>
      <CardFooter></CardFooter>
    </Card>
  </>);
}
