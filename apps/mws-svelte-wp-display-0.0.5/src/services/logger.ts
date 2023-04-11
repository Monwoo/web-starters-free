// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

import LoggerDomains, { appDomain, type Domains } from "@app/services/logger-domains";
import appEnv from '@app/stores/app-env';
import _ from 'lodash';
import chalk from 'chalk';
chalk.level = 1; // Use colours in the VS Code Debug Window

// https://stackoverflow.com/questions/20524700/custom-console-log-function-a-console-log-wrapper
// https://developer.chrome.com/docs/devtools/console/log/
// https://developer.chrome.com/docs/devtools/javascript/
// https://stackoverflow.com/questions/13227489/how-can-one-get-the-file-path-of-the-caller-function-in-node-js
// https://www.telerik.com/blogs/how-to-style-console-log-contents-in-chrome-devtools
// https://codingbeautydev.com/blog/javascript-cannot-access-before-initialization
// https://github.com/sveltejs/kit/issues/1538
// https://kit.svelte.dev/docs/modules#$app-navigation-beforenavigate
// https://lucasfcosta.com/2017/02/17/JavaScript-Errors-and-Stack-Traces.html
// https://stackoverflow.com/questions/69418568/how-to-execute-code-on-sveltekit-app-start-up
// https://stackoverflow.com/questions/63598211/how-to-use-error-capturestacktrace-in-node-js
// https://stackoverflow.com/questions/59625425/understanding-error-capturestacktrace-and-stack-trace-persistance
// https://console.spec.whatwg.org/#trace
// https://hamednourhani.gitbooks.io/typescript-book/content/docs/tips/bind.html
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind
// https://www.npmjs.com/package/debug
// https://stackoverflow.com/questions/221294/how-do-i-get-a-timestamp-in-javascript
// https://subscription.packtpub.com/book/web-development/9781785880087/1/ch01lvl1sec04/enhancing-stack-trace-output
// https://stackoverflow.com/questions/39875871/how-to-remove-all-getters-and-setters-from-an-object-in-javascript-and-keep-pure

const csl = console;

export type ForceConfig = {
    showCallerScript?: boolean,
    showDomains?: boolean,
    traceInSSR?: boolean,
    shouldTrace?: boolean,
    stackTraceLimit?: number,
    benchTimeoutInMs?: number,
}
export class Logger {
    logLevel: number = 0;
    benchTimeoutInMs: number = 1000 * 60 * 5; // Time out for bench reportings in millisecondes
    showCallerScript: boolean = false;
    showDomains: boolean = true;
    /** use true if you want to get log traces for server side outputs in your terminal */
    traceInSSR: boolean = false;
    /** use true if you want to get log traces */
    shouldTrace: boolean = true;
    self: Logger & typeof csl = this as any;
    static _originalCslLog: typeof console.log | null = null;
    static _originalSelf_Log: typeof Logger.inst._log | null = null;

    constructor() {
        this.self = {
            ...csl,
            ...this,
        };
        this.initConfig();
    }

    initConfig = (force:ForceConfig = { }, defaultLowStackTraceLimit = 12) => {
        this.self.logLevel = (appEnv.LOG_LEVEL == undefined)
            ? 2 : appEnv.LOG_LEVEL; // from 0 (no log, prod) To 3 (very verbose logs included)    
        this.self.showCallerScript = force.showCallerScript ?? (this.self.logLevel > 2);
        this.self.showDomains = force.showDomains ?? this.self.showDomains;
        // Need level 4 or more to force TraceInSSR
        this.self.traceInSSR = force.traceInSSR ?? (this.self.logLevel > 3 && !appEnv.browser);
        // shouldTrace 'true' will trace with console.trace(),
        // or will switch to SSR logs if traceInSSR is true
        this.self.shouldTrace = force.shouldTrace ?? appEnv.browser;

        // Can also be setup with node parameter : --stack-trace-limit=21
        Error.stackTraceLimit = force.stackTraceLimit
        ?? ((this.self.logLevel > 2) ? Infinity : defaultLowStackTraceLimit);

        this.self.benchTimeoutInMs = force.benchTimeoutInMs ?? this.self.benchTimeoutInMs;
    
        // Do not remove logs for nodes scripts, but only for production browser
        // otherwise, test servers will not LOGS servers logs TOO... (not showing urls etc)
        if (appEnv.browser && (this.self.logLevel < 1) && !appEnv.dev) {
            // cleaning logs for regular window console too
            if (!Logger._originalCslLog) {
                Logger._originalCslLog = csl.log;
            }
            if (!Logger._originalSelf_Log) {
                Logger._originalSelf_Log = this.self._log;
            }
            csl.log = (...a) => { };
            this.self._log = (...a) => { };
        } else {
            if (Logger._originalCslLog) {
                csl.log = Logger._originalCslLog;
            }
            if (Logger._originalSelf_Log) {
                this.self._log = Logger._originalSelf_Log;
            }
        }
        this.self.log("Did init logger : ", JSON.stringify({
            logLevel: this.self.logLevel,
            // showCallerScript: this.self.showCallerScript, // Guessable from output
            // showDomains: this.self.showDomains, // Guessable from output
            // shouldTrace: this.self.shouldTrace, // Guessable from output
            // traceInSSR: this.self.traceInSSR, // Guessable from output
            stackTraceLimit: Error.stackTraceLimit === Infinity ? 'Infinity' : Error.stackTraceLimit,
            // benchTimeoutInMs: this.self.benchTimeoutInMs, // warn msg will be shown if reached
        }).replaceAll('":', ': ').replaceAll('","', ', ').replace(/"|{|}/g, ""));
        this.self.logVV("Logger details :", this.self);
    }

    _log = (logDomains: Domains[], color: string, style: string, ...args: any[]) => {
        let domainAllowed = false;
        const domainPrompt = logDomains.sort().reduce((p, d) => {
            domainAllowed ||= LoggerDomains[d];
            return LoggerDomains[d] ? `${p}${d}` : p;
        }, "");
        if (!domainAllowed) {
            return;
        }
        class LoggerErr extends Error {
            constructor(message: string) {
                super(message);
            }
        }
        let e: Error;
        try { throw new LoggerErr('LogTrace') } catch (err: any) {
            e = err;
        }
        const callerIndex = 3;
        let caller_line = e.stack?.split("\n")[callerIndex];
        let index = (caller_line?.indexOf("at ")) || -1;
        index = index >= 0 ? index : 0;
        let line = caller_line?.slice(index + 2, caller_line.length) || "";
        line = line.split('/svelte-frontend/')[1] ?? line;
        let firstArg = args.shift();
        if (appEnv.browser) {
            this.self.showDomains && csl.log(`%c${domainPrompt}`, `color:${color}`);
            this.self.showCallerScript && csl.log(`%c[at ${line}]`, `color:${color}`);
        } else {
            // csl.log(''); // Log one empty space before each log bloc in server console
            this.self.showDomains && csl.log(chalk.underline.cyanBright(domainPrompt));
            this.self.showCallerScript && csl.log(chalk.grey.underline(`[at ${line}]`));
        }
        if (typeof (firstArg) == 'string') {
            let txt = '';
            while (typeof (firstArg) == 'string') {
                txt = txt + firstArg.trimEnd() + ' ';
                firstArg = args.shift();
            }

            csl.log(`%c${txt}`, `color:${color};${style}`);
            if (undefined !== firstArg) csl.log(firstArg, ...args);
        } else {
            csl.log(firstArg, ...args);
        }
        if (this.self.shouldTrace && !this.self.traceInSSR) {
            csl.groupCollapsed('%c Trace', 'color: #000000; font-size: 6pt;');
            csl.trace();
            csl.groupEnd();
        }
        if (this.self.traceInSSR) {
            // csl.trace(); // will feed ERROR channel in console mode (SSR), not Standard INPUT like .log...
            // SO we change LOG trace in vitest using Error.stack
            const stack = (new Error).stack?.split('\n').slice(3).join('\n');
            csl.log(chalk.grey("Trace:\n" + stack));
        }
    };
    /**
     * Classic logs to `'[App]'` domain
     *
     */
    log = (...args: any[]) => {
        if (this.self.logLevel > 0) {
            this.self._log([appDomain], 'white', 'font-size: 14pt;', ...args);
        }
    };
    /**
     * Verbose logs to `'[App]'` domain
     *
     */
    logV = (...args: any[]) => {
        if (this.self.logLevel > 1) {
            this.self._log([appDomain], 'lightgrey', 'font-size: 12pt;', ...args);
        }
    };
    /**
     * Very Verbose logs to `'[App]'` domain
     *
     */
    logVV = (...args: any[]) => {
        if (this.self.logLevel > 2) {
            this.self._log([appDomain], 'grey', 'font-size: 10pt;', ...args);
        }
    };
    /**
     * Adds a domain log.
     *
     * An example of overriding logging to domains :
     * - at your import section :
     * ```js
     * // transform regular console with our logger :
     * import console from '@app/services/logger';
     * ```
     * - in some code related to `'[App-config]'` domain :
     * ```js
     * // In your code, log to domains :
     * console.logToDomains(['[App-config]'], "Logging for [App-config] domain");
     * ```
     *
     * @param logDomains List of domains to log to.
     * @param args Regular log arguments
     */
    logToDomains = (logDomains: Domains[], ...args: any[]) => {
        if (this.self.logLevel > 0) {
            this.self._log(logDomains, 'white', 'font-size: 14pt;', ...args);
        }
    };
    /**
     * Adds a Verbose domain log.
     */
    logToDomainsV = (logDomains: Domains[], ...args: any[]) => {
        if (this.self.logLevel > 1) {
            this.self._log(logDomains, 'lightgrey', 'font-size: 12pt;', ...args);
        }
    };
    /**
     * Adds a Very Verbose domain log.
     */
    logToDomainsVV = (logDomains: Domains[], ...args: any[]) => {
        if (this.self.logLevel > 2) {
            this.self._log(logDomains, 'grey', 'font-size: 10pt;', ...args);
        }
    };

    isPerformanceSupported = () => (
        window.performance &&
        window.performance.now &&
        window.performance.timing &&
        window.performance.timing.navigationStart
    );

    timeStampInMs = () => (
        this.self.isPerformanceSupported() ?
            window.performance.now() +
            window.performance.timing.navigationStart :
            Date.now()
    );

    bench = (feature: string, domains: Domains[] = [appDomain]) => {
        const benchStart = this.self.timeStampInMs();
        let solve: (extraCmt?: string) => void;
        // let fail: (reason?: any) => void;
        const bencher = new Promise((resolve, reject) => {
            solve = resolve;
            // fail = reject;
        }).then((extraCmt => {
            const deltaSec = (this.self.timeStampInMs() - benchStart) / 1000;
            this.self._log(
                domains, 'gold', 'font-size: 14pt;',
                `BENCH [ ${deltaSec.toFixed(4)} s ] for ${feature}`,
                extraCmt
            );
        }));
        (async () => {
            const timeout = setTimeout(() => {
                this.self.error(`BENCH FAIL after "${this.self.benchTimeoutInMs}" ms for "${feature}"`);
                // fail(new Error('BENCH Did timeout')); // THROW FROM ASYNC stack... bad... so we 'solve' instead
                solve('ERROR : BENCH Did timeout');
            }, this.self.benchTimeoutInMs);
            await bencher;
            clearTimeout(timeout);
        })(); // Launch async process for bencher init

        // We wait for the solve to be ready, otherwise we may not be able to solve it
        return (extraCmt = '') => solve(extraCmt);
    }

    static inst: Logger;
    static extended: Logger & typeof csl;
    static instance() {
        if (!Logger.extended) {
            Logger.inst = new Logger();
            Logger.extended = Logger.inst.self;
        }
        return Logger.extended;
    }
}

let logger = Logger.instance();

logger.log('Logger.ts Loaded with log level : ', logger.logLevel);

export default logger;