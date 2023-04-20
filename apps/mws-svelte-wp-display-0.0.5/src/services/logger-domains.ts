// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Use to trace Domain Design Driven logs from running app
// Add your owns as you like

export const appConfigDomain = "[App-config]";
export const appDomain = "[App]";
export const appGarbageCollectorDomain = "[App-gc]";
export const i18nDomain = "[i18n]";
export const localStorageDomain = "[local-storage]";
export const navDomain = "[Navigation]";
export const serviceWorker = "[s-worker]";

let domains = {
    [appConfigDomain]: true,
    [appDomain]: true,
    [appGarbageCollectorDomain]: false,
    [i18nDomain]: true,
    [localStorageDomain]: true,
    [navDomain]: true,
    [serviceWorker]: true,
};

export type Domains = keyof typeof domains;

export const enableAllDomains = () => {
    Object.keys(domains).forEach((d) => {
        // To avoid error : domains[[i18n]] only have a getter
        // when domain have been mocked up with vi.spyOn,
        // we first delete the getter before assign
        // if (domains[d as Domains] !== undefined) {
        //     delete domains[d as Domains];
        // }
        // domains[d as Domains] = true;
        Object.defineProperty(domains, d as Domains, {value: true});
    })
}

export const disableAllDomains = () => {
    Object.keys(domains).forEach((d) => {
        // domains[d as Domains] = false; // NOP : will fail if vi.spyOn is used on it...
        Object.defineProperty(domains, d as Domains, {value: false});
    })

}

export default domains;