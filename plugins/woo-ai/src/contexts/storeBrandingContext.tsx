import React, { createContext, useContext, useState, useEffect, FC } from 'react';
import { getToneOfVoice, getBusinessDescription } from '../utils/branding';

// Define the shape of the context state
interface IBrandingContext {
  toneOfVoice: string;
  businessDescription: string;
}

// Initialize the context with undefined
const BrandingContext = createContext<IBrandingContext | undefined>(undefined);

interface IProps {
  children: React.ReactNode;
}

export const BrandingProvider: FC<IProps> = ({ children }) => {
  const [toneOfVoice, setToneOfVoice] = useState<string>('neutral');
  const [businessDescription, setBusinessDescription] = useState<string>('');

  useEffect(() => {
    getToneOfVoice().then((value) => setToneOfVoice(value));
    getBusinessDescription().then((value) => setBusinessDescription(value));
  }, []);

  return (
    <BrandingContext.Provider value={{ toneOfVoice, businessDescription }}>
      {children}
    </BrandingContext.Provider>
  );
};

export function useStoreBranding(): IBrandingContext {
  const context = useContext(BrandingContext);
  if (!context) {
    throw new Error('useStoreBranding must be used within a BrandingProvider');
  }
  return context;
}