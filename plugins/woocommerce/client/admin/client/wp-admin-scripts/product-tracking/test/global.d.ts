declare const productScreen: { name: string };
declare const global: typeof globalThis & {
  productScreen: typeof productScreen;
};
