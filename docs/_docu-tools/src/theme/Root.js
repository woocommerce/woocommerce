import React from 'react';
import { CookieBanner } from '../components/CookieBanner';
import MarkdownCopy from '../components/MarkdownCopy';

export default function Root({ children }) {
  return (
    <>
      {children}
      <CookieBanner />
      <MarkdownCopy />
    </>
  );
}
