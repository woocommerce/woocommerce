import { GeneratorFn, HookFn, DeepPartial, BuildOptions } from './types';
export declare class FactoryBuilder<T, F, I> {
    private generator;
    private factories;
    private sequence;
    private params;
    private afterBuildHook?;
    private onCreateHook?;
    private transientParams;
    private associations;
    constructor(generator: GeneratorFn<T, F, I>, factories: F, sequence: number, params: DeepPartial<T>, options: BuildOptions<T, I>);
    build(): T;
    create(): Promise<any>;
    setOnCreateHook: (hook: HookFn<T>) => void;
    setAfterBuildHook: (hook: HookFn<T>) => void;
    _callAfterBuildHook(object: T): void;
    _callOnCreateHook(object: T): any;
}
