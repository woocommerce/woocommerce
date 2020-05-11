import { DeepPartial, GeneratorFn, BuildOptions } from './types';
export interface AnyFactories {
    [key: string]: Factory<any>;
}
export declare class Factory<T, F = any, I = any> {
    private generator;
    private nextId;
    private factories?;
    constructor(generator: GeneratorFn<T, F, I>);
    /**
     * Define a factory. This factory needs to be registered with
     * `register` before use.
     * @param generator - your factory function
     */
    static define<T, F = any, I = any>(generator: GeneratorFn<T, F, I>): Factory<T, F, I>;
    /**
     * Define a factory that does not need to be registered with `register`. The
     * factory will not have access the `factories` parameter. This can be useful
     * for one-off factories in individual tests
     * @param generator - your factory
     * function
     */
    static defineUnregistered<T, I = any>(generator: GeneratorFn<T, null, I>): Factory<T, null, I>;
    /**
     * Build an object using your factory
     * @param params
     * @param options
     */
    build(params?: DeepPartial<T>, options?: BuildOptions<T, I>): T;
    buildList(number: number, params?: DeepPartial<T>, options?: BuildOptions<T, I>): T[];
    /**
     * Asynchronously create an object using your factory. Relies on the `create`
     * function defined in the factory
     * @param params
     * @param options
     */
    create(params?: DeepPartial<T>, options?: BuildOptions<T, I>): Promise<T>;
    createList(number: number, params?: DeepPartial<T>, options?: BuildOptions<T, I>): Promise<T[]>;
    setFactories(factories: F): void;
    _throwFactoriesUndefined(): never;
}
