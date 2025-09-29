import React from 'react';
import { CookieBanner } from '../components/CookieBanner';

export default function Root({ children }) {
  return (
    <>
      {children}
      <CookieBanner />
    </>
  );
}
