// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Biblio
// https://stackoverflow.com/questions/72735025/how-to-test-logger-log-output-with-jest
// https://dev.to/zirkelc/how-to-test-logger-log-5fhd
// https://stackoverflow.com/questions/71027434/jest-mock-an-an-error-thrown-and-get-the-actual-error-from-the-catch
// https://bambielli.com/til/2018-01-07-mocking-constructors/
// https://crazyming.medium.com/the-difference-between-jest-spyon-and-jest-mock-98c9cff23c8f
// https://gist.github.com/rexebin/71416657c6842d756fb732dad43932df
//   expect(vm.$el.querySelector('.Message')).toMatchSnapshot() ?
// https://github.com/facebook/jest/issues/7396
// https://stackoverflow.com/questions/48219267/how-to-spy-on-a-class-constructor-jest
// https://stackoverflow.com/questions/42766986/typescript-anonymous-class
// https://stackoverflow.com/questions/33345737/how-to-format-a-stacktrace-using-error-preparestacktrace
// https://v8.dev/docs/stack-trace-api
// https://stackoverflow.com/questions/41707191/javascript-descriptors-enumerable-configurable-writable-are-not-true-by-default
// https://262.ecma-international.org/7.0/#sec-createdataproperty
// https://www.tektutorialshub.com/javascript/javascript-property-descriptors-enumerable-writable-configurable/
// https://jestjs.io/docs/es6-class-mocks
// https://www.abrahamberg.com/blog/how-to-mock-class-constructor-with-parameters-jest-and-typescript/
// https://stackoverflow.com/questions/60530831/mock-typescript-class-with-private-constructor-using-jest
// https://stackoverflow.com/questions/58200661/is-there-a-way-to-spy-on-a-promise-constructor

import { vi } from 'vitest'

import logger, { Logger } from '@app/services/logger';
import domains, {
    enableAllDomains, disableAllDomains,
    appConfigDomain, appDomain, appGarbageCollectorDomain,
    i18nDomain, localStorageDomain, navDomain, serviceWorker
} from '@app/services/logger-domains';
import appEnv from '@app/stores/app-env';
import chalk from 'chalk';
chalk.level = 1; // Use colours in the VS Code Debug Window

describe("[logger.test.ts] tests", async () => {
    it('Regular checks', async () => {
        enableAllDomains();
        const logLvlSpy = vi.spyOn(appEnv, 'LOG_LEVEL', 'get')
            .mockReturnValue(2);
        console.log(appEnv, appEnv.LOG_LEVEL);
        logger.initConfig();

        expect(logger.logLevel).toBe(2);
        expect(logger.showCallerScript).toBe(false);
        expect(logger.traceInSSR).toBe(false);

        const logSpy = vi.spyOn(console, 'log');
        logger.log("=== Simple test log for logger.test.ts");
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(2);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[App]'));
        expect(logSpy.mock.calls[1][0]).toContain('=== Simple test log for logger.test.ts');

        logSpy.mockRestore();
        logger.logV("=== Verbose test log for logger.test.ts");
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(2);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[App]'));
        expect(logSpy.mock.calls[1][0]).toContain('=== Verbose test log for logger.test.ts');

        logSpy.mockRestore();
        logger.logVV("=== Very Verbose test log for logger.test.ts");
        expect(logSpy).not.toHaveBeenCalled(); // Indeed, logLevel is too low to show them

        logSpy.mockRestore();
        logger.logLevel = 3;
        logger.logVV("=== Very Verbose test log for logger.test.ts");
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(2);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[App]'));
        expect(logSpy.mock.calls[1][0]).toContain('=== Very Verbose test log for logger.test.ts');

        logSpy.mockRestore();
        logger.logToDomains(
            ['[App-config]'], "=== Simple test log to domain App-config for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(2);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[App-config]'));
        expect(logSpy.mock.calls[1][0]).toContain('=== Simple test log to domain App-config for logger.test.ts');

        logSpy.mockRestore();
        logger.logToDomainsV(
            ['[i18n]'], "=== Verbose test log to domain i18n for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(2);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[i18n]'));
        expect(logSpy.mock.calls[1][0]).toContain('=== Verbose test log to domain i18n for logger.test.ts');

        logSpy.mockRestore();
        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(2);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[i18n][local-storage]'));
        expect(logSpy.mock.calls[1][0]).toContain('=== Very Verbose test log to domains i18n and local-storage for logger.test.ts');

        logSpy.mockRestore();
        logger.showCallerScript = true;
        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[i18n][local-storage]'));
        expect(logSpy.mock.calls[1][0]).toContain('[at ');
        expect(logSpy.mock.calls[2][0]).toContain('=== Very Verbose test log to domains i18n and local-storage for logger.test.ts');

        logSpy.mockRestore();
        const i18nDomainSpy = vi.spyOn(domains, '[i18n]', 'get').mockReturnValue(false);
        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts",
            "With 2nd txt"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[local-storage]'));
        expect(logSpy.mock.calls[1][0]).toContain('[at ');
        expect(logSpy.mock.calls[2][0]).toContain('=== Very Verbose test log to domains i18n and local-storage for logger.test.ts');

        const localStorageDomainSpy = vi.spyOn(domains, '[local-storage]', 'get').mockReturnValue(false);
        console.log(domains); // For info on how mock handle stuff
        console.log(domains['[i18n]']);
        console.log(domains['[local-storage]']);

        logSpy.mockRestore();
        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts"
        );
        expect(logSpy).not.toHaveBeenCalled();

        logSpy.mockRestore();
        logger.logToDomains(
            [appDomain, appConfigDomain, i18nDomain, localStorageDomain],
            { d: 'directObject' },
            "With 2nd txt"
        );
        expect(logSpy).toHaveBeenCalledWith(chalk.underline.cyanBright('[App-config][App]'));
        expect(logSpy).toHaveBeenCalledTimes(3);

        vi.clearAllMocks();
        vi.resetAllMocks();
        vi.restoreAllMocks();

        localStorageDomainSpy.mockReset();
        i18nDomainSpy.mockClear().mockReset().mockRestore();

        i18nDomainSpy.mockReset().mockReturnValue(true);

        // For info on how mock handle stuff
        logger.logToDomains([i18nDomain], 'Tested domains : ', domains);
        expect(logSpy).toHaveBeenCalledTimes(4);
        // console.trace() is outputed in ERROR logs, so not in SSR logs, but in SSR Error output
        expect(logSpy.mock.calls[3][0].toString()).not.toContain('src/services/logger.test.ts');

        logLvlSpy.mockReturnValue(4); // Add SSR trace logs on top of level 3 debugs
        logger.log("AppEnv", appEnv, appEnv.LOG_LEVEL);
        logger.initConfig();

        logSpy.mockRestore();
        logger.logToDomains([i18nDomain], 'A log with full SSR trace');
        expect(logSpy).toHaveBeenCalledTimes(4);
        logger.log(logSpy.mock.calls[3][0]);
        expect(logSpy.mock.calls[3][0]).toContain('src/services/logger.test.ts'); // check call stack src file presence

        logSpy.mockRestore();
        disableAllDomains();
        logger.logToDomains([i18nDomain], '01 - A log with full SSR trace, domains disabled, should not show');
        logger.logToDomainsV([i18nDomain], '02 - A log with full SSR trace, domains disabled, should not show');
        logger.logToDomainsVV([i18nDomain], '03 - A log with full SSR trace, domains disabled, should not show');
        logger.log([i18nDomain], '04 - A log with full SSR trace, domains disabled, should not show');
        logger.logV([i18nDomain], '05 - A log with full SSR trace, domains disabled, should not show');
        logger.logVV([i18nDomain], '06 - A log with full SSR trace, domains disabled, should not show');
        expect(logSpy).toHaveBeenCalledTimes(0);

        Object.defineProperty(domains, i18nDomain, {value: true});
        logger.logToDomains([i18nDomain], '01 - A log with full SSR trace, i18n domain enabled, should show');
        expect(logSpy).toHaveBeenCalledTimes(4);
    });

    it('Browser side checks', async () => {
        enableAllDomains(); // Will break with mock reset ? (property already exist, only have a getter)
        const logLvlSpy = vi.spyOn(appEnv, 'LOG_LEVEL', 'get')
            .mockReturnValue(undefined); // We are in 'DEV', logLevel will default to '2'
        vi.spyOn(appEnv, 'browser', 'get').mockReturnValue(true);
        logger.initConfig();
        logger.log("App Env", appEnv, appEnv.LOG_LEVEL);
        expect(logger.logLevel).toBe(2);
        expect(logger.showCallerScript).toBe(false);
        expect(logger.traceInSSR).toBe(false);

        const logSpy = vi.spyOn(console, 'log');
        logger.log("=== Simple test log for logger.test.ts");
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith('%c[App]', 'color:white');
        expect(logSpy.mock.calls[1][0]).toContain('=== Simple test log for logger.test.ts');

        logSpy.mockRestore();
        logger.logV("=== Verbose test log for logger.test.ts");
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith('%c[App]', 'color:lightgrey');
        expect(logSpy.mock.calls[1][0]).toContain('=== Verbose test log for logger.test.ts');

        logSpy.mockRestore();
        logger.logVV("=== Very Verbose test log for logger.test.ts");
        expect(logSpy).not.toHaveBeenCalled(); // Indeed, logLevel is too low to show them

        logSpy.mockRestore();
        logger.logLevel = 3;
        logger.logVV("=== Very Verbose test log for logger.test.ts");
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith('%c[App]', 'color:grey');
        expect(logSpy.mock.calls[1][0]).toContain('=== Very Verbose test log for logger.test.ts');

        logSpy.mockRestore();
        logger.logToDomains(
            ['[App-config]'], "=== Simple test log to domain App-config for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith('%c[App-config]', 'color:white');
        expect(logSpy.mock.calls[1][0]).toContain('=== Simple test log to domain App-config for logger.test.ts');

        logSpy.mockRestore();
        logger.logToDomainsV(
            ['[i18n]'], "=== Verbose test log to domain i18n for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith('%c[i18n]', 'color:lightgrey');
        expect(logSpy.mock.calls[1][0]).toContain('=== Verbose test log to domain i18n for logger.test.ts');

        logSpy.mockRestore();
        vi.spyOn(domains, '[local-storage]', 'get').mockReturnValue(true);

        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(3);
        expect(logSpy).toHaveBeenCalledWith('%c[i18n][local-storage]', 'color:grey');
        expect(logSpy.mock.calls[1][0]).toContain('=== Very Verbose test log to domains i18n and local-storage for logger.test.ts');

        logSpy.mockRestore();
        logger.showCallerScript = true;
        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(4);
        expect(logSpy).toHaveBeenCalledWith('%c[i18n][local-storage]', 'color:grey');
        expect(logSpy.mock.calls[1][0]).toContain('%c[at ');
        expect(logSpy.mock.calls[2][0]).toContain('=== Very Verbose test log to domains i18n and local-storage for logger.test.ts');

        logSpy.mockRestore();
        const i18nDomainSpy = vi.spyOn(domains, '[i18n]', 'get').mockReturnValue(false);
        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts",
            "With 2nd txt"
        );
        expect(logSpy).toHaveBeenCalled();
        expect(logSpy).toHaveBeenCalledTimes(4);
        expect(logSpy).toHaveBeenCalledWith('%c[local-storage]', 'color:grey');
        expect(logSpy.mock.calls[1][0]).toContain('%c[at ');
        expect(logSpy.mock.calls[2][0]).toContain('=== Very Verbose test log to domains i18n and local-storage for logger.test.ts');

        const localStorageDomainSpy = vi.spyOn(domains, '[local-storage]', 'get').mockReturnValue(false);
        console.log("domains", domains); // For info on how mock handle stuff
        console.log(domains['[i18n]']);
        console.log(domains['[local-storage]']);

        logSpy.mockRestore();
        logger.logToDomainsVV(
            ['[local-storage]', '[i18n]'],
            "=== Very Verbose test log to domains i18n and local-storage for logger.test.ts"
        );
        expect(logSpy).not.toHaveBeenCalled();

        logSpy.mockRestore();
        logger.logToDomains(
            [appDomain, appConfigDomain, i18nDomain, localStorageDomain, appGarbageCollectorDomain, navDomain, serviceWorker],
            { d: 'directObject' },
            "With 2nd txt"
        );
        expect(logSpy).toHaveBeenCalledWith('%c[App-config][App-gc][App][Navigation][s-worker]', 'color:white');
        expect(logSpy).toHaveBeenCalledTimes(4);

        vi.clearAllMocks();
        vi.resetAllMocks();
        vi.restoreAllMocks();

        localStorageDomainSpy.mockReset();
        i18nDomainSpy.mockClear().mockReset().mockRestore();

        i18nDomainSpy.mockReset().mockReturnValue(true);

        logSpy.mockRestore();
        // For info on how mock handle stuff
        logger.logToDomains([i18nDomain], 'Tested domains : ', domains);
        expect(logSpy).toHaveBeenCalledTimes(5);
    });

    it('Bench checks', async () => {
        // OK test
        const benchEnd = logger.bench('Logger testing bench 1st try');
        await new Promise(resolve => setTimeout(resolve, 200));
        benchEnd();

        // Timeout test
        logger.benchTimeoutInMs = 100;
        const benchEnd2 = logger.bench('Logger testing bench 2nd try (should timeout)');
        await new Promise(resolve => setTimeout(resolve, 200));
        benchEnd2();
        await new Promise(r => (setTimeout(r, 200)));

        // TIPS : mock class methods or static attributes
        // vi.spyOn(Promise.prototype, 'catch')
        // .mockImplementation(async (handler) => {
        //     console.log('mocked Promise catch function', handler);
        // }); // comment this line if just want to "spy"
        // vi.spyOn(Logger, 'instance')
        // .mockImplementation(() => {
        //     console.log('mocked Logger static methode');
        //     return logger;
        // });
        // vi.spyOn(Logger, 'extended', 'get')
        // .mockImplementation(() => {
        //     console.log('mocked Logger static attribute');
        //     return logger;
        // });

        // TIPS : below to ovewrite global Promise constructor :
        // const promiseConstructor = vi.spyOn((Promise as any), 'constructor'); // no error, but not counting next constructor
        // const promiseConstructor = vi.spyOn(global, 'Promise', 'get'); // RangeError: Maximum call stack size exceeded

        // type PromiseResolver = (resolve: (value: unknown) => void, reject: (reason?: any) => void) => void;
        const originalPromise = Promise;
        // const t:PromiseConstructor;

        // const promiseConstructor = vi.spyOn(global, 'Promise', 'get')
        // .mockImplementation(() => {
        //     const stack = (new Error).stack?.split("\n").map(l => l.split(" at ")[1]?.split(" (")[0]);
        //     const shouldMock = stack && 0 !== stack.filter(
        //         f => /bench/.test(f)
        //     ).length;
        //     if (!shouldMock) {
        //         return originalPromise;
        //     }
        //     return originalPromise;
        //     // return class extends originalPromise {
        //     //     constructor(executor:any) {
        //     //         super(executor);
        //     //         logger.log(chalk.green("#BENCH - NEW promise from CONSTRUCTOR", typeof executor));
        //     //     }
        //     // };
        // }); // RangeError: Maximum call stack size exceeded

        // global.Promise = vi.fn().mockImplementation((resolver) => {
        //     return new originalPromise(resolver);
        // }); // TypeError: Promise.resolve is not a function (from node_modules/parse5/dist/cjs/index.js)

        const promiseConstructor = vi.fn();
        let startNullReplace = 1;
        global.Promise = class extends Promise<any> {
            readonly prototype: Promise<any>;
            constructor(originalExecutor: any) {
                const stack = (new Error).stack?.split("\n").map(l => l.split(" at ")[1]?.split(" (")[0]);
                const shouldMock = stack && 0 !== stack.filter(
                    f => /bench/.test(f) || /logger.test.ts/.test(f)
                ).length;
                let executor = originalExecutor;
                if (shouldMock) {
                    logger.log(chalk.green("#BENCH - NEW promise from CONSTRUCTOR", typeof executor));
                    console.log("stack", (new Error).stack);
                    if (--startNullReplace < 0) {
                        logger.log(chalk.green("#BENCH - NEW promise with custom resolve and reject", typeof executor));
                        executor = (a: any, r: any) => originalExecutor(() => {
                            logger.log(chalk.green("#BENCH - resolving promise"));
                            return a(); // accept
                        }, () => {
                            logger.log(chalk.green("#BENCH - rejecting promise"));
                            return r(); // reject
                        });
                    }
                    promiseConstructor();
                }
                super(executor);
                this.prototype = this;
            }
        };
        await new Promise(resolve => (resolve && setTimeout(resolve, 0)));
        expect(promiseConstructor).toHaveBeenCalledTimes(1);

        // // TIPS : BELOW will not work since we adjust Promise for .toThrow too..
        // expect(async () => await Promise.reject())
        //     .toThrow();
        // // TIPS : below do not trigger our callback
        // try { await Promise.reject() } catch (e) { }
        // try { new Promise((a, r) => r()); } catch (e) { }
        promiseConstructor.mockRestore();
        await new Promise((a, r) => r()).catch(()=>{});
        expect(promiseConstructor).toHaveBeenCalledTimes(2);

        promiseConstructor.mockRestore();
        expect(promiseConstructor).toHaveBeenCalledTimes(0);
        const benchEnd3 = logger.bench('Logger testing bench 3rd try, async issue tests');
        benchEnd3(); // 1 call for 'new', + on call for chaining 'then'
        expect(promiseConstructor).toHaveBeenCalledTimes(2);

        promiseConstructor.mockClear();
        global.Promise = originalPromise; // Bring back regular Promise

        vi.spyOn(window, 'performance', 'get').mockReturnValue({
            now: () => Date.now(),
            timing: {
                navigationStart: Date.now(),
            },
        });
        expect(logger.isPerformanceSupported()).toBeTruthy();
        const benchEnd6 = logger.bench('Logger testing bench 4th try');
        await new Promise(resolve => setTimeout(resolve, 10));
        benchEnd6();
    });

    it('Fails checks', async () => {
        const RealError = Error; // using error directly will infinit loop
        const constructorSpy = vi.spyOn(global, 'Error');
        constructorSpy.mockImplementation(() => {
            return new class extends RealError {
                constructor(message: string) {
                    super(message);
                    Error.prepareStackTrace = (_, callsites) => {
                        let stk = '';
                        callsites.forEach(callsite => {
                            stk += callsite.getLineNumber() + ':';
                            stk += callsite.getColumnNumber() + ' ';
                        });
                        return "test logger.test.ts \n some \n fake \n stackTrace \n injection " + stk;
                    };
                }
            }('Mocked Error by logger.test.ts')
        });
        logger.traceInSSR = true;
        const logSpy = vi.spyOn(console, 'log');
        logger.log('Should have changed error throw msg');
        expect(logSpy).toHaveBeenCalledTimes(4); // We show trace, so will call 4 times
        expect(logSpy.mock.calls[1][0]).toContain('[at tackTrace ]');

        logSpy.mockRestore();
        constructorSpy.mockClear();
        constructorSpy.mockImplementation(() => {
            return new class extends RealError {
                constructor(message: string) {
                    super(message);
                    Error.prepareStackTrace = (_, callsites) => {
                        return null;
                    };
                }
            }('Mocked Error by logger.test.ts')
        });
        logger.log('Should have test callstack line issue ');
        expect(logSpy).toHaveBeenCalledTimes(4); // We show trace, so will call 4 times

        logger.traceInSSR = false;
        logger.showCallerScript = false;
    });

    it('With custom env configs', async () => {
        // Use class exported TYPE to gain coverage :
        const myLogger: Logger = Logger.inst;
        const logLvlSpy = vi.spyOn(appEnv, 'LOG_LEVEL', 'get')
            .mockReturnValue(3);
        myLogger.log("appEnv logger level : ", appEnv.LOG_LEVEL);
        logger.initConfig();
        logger.log("Logger level : ", logger.logLevel, logger.showCallerScript);

        const logSpy = vi.spyOn(console, 'log');
        logger.log('Should be in verbose level directly');
        expect(logSpy).toHaveBeenCalledTimes(3); // We do not show trace, so will call 3 times

        logSpy.mockRestore();
        logger.log('Should be in verbose level directly', ' yep', 42, { hello: 33 });
        expect(logSpy).toHaveBeenCalledTimes(4); // We do not show trace, 
        // so will call 4 times)

        logSpy.mockClear();
        logLvlSpy.mockClear();
    })

    it('As prod with no logs', async () => {
        // WARNING : TEST LAST since will remove the console.log outputs...
        const logLvlEnvSpy = vi.spyOn(appEnv, 'LOG_LEVEL', 'get').mockReturnValue(0);
        const devEnvSpy = vi.spyOn(appEnv, 'dev', 'get').mockReturnValue(false);
        const logSpy = vi.spyOn(console, 'log');
        logger.initConfig();
        // Even with level 3, have been init with dev false AND logLvl 0,
        // removing log console... can be reset on RELOAD only... (initConfig())
        logger.logLevel = 3;
        logger.logToDomains(
            [appDomain, appConfigDomain, i18nDomain, localStorageDomain],
            "Will be outputed"
        );
        expect(logSpy).toHaveBeenCalledTimes(2);

        vi.spyOn(appEnv, 'browser', 'get').mockReturnValue(true);
        logSpy.mockClear();
        logger.initConfig();
        // Even with level 3, have been init with dev false AND logLvl 0,
        // removing log console... can be reset on RELOAD only... (initConfig())
        logger.logLevel = 3;
        logger.logToDomains(
            [appDomain, appConfigDomain, i18nDomain, localStorageDomain],
            "Should not be outputed"
        );
        expect(logSpy).toHaveBeenCalledTimes(0);


        // ++ : no console.log anymore, our spy should not increment
        logSpy.mockReset();
        console.log("Console.log should not work anymore");
        expect(logSpy).toHaveBeenCalledTimes(0);

        logSpy.mockReset();
        logLvlEnvSpy.mockReset();
        devEnvSpy.mockReset();

        vi.spyOn(appEnv, 'LOG_LEVEL', 'get').mockReturnValue(0);
        vi.spyOn(appEnv, 'dev', 'get').mockReturnValue(true);
        logger.initConfig(); // Will not bring back console.log for dev even if log is 0...
        const logSpy3 = vi.spyOn(console, 'log');
        console.log("Regular console.log should work back for dev env");
        expect(logSpy3).toHaveBeenCalledTimes(1);
        logSpy3.mockReset();
        logger.log("Logger logger.log should not work due to current LOG_LEVEL");
        expect(logSpy3).toHaveBeenCalledTimes(0);
    });
})
