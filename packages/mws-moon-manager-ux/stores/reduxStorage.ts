// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
import { writable } from 'svelte/store';

// TODO : fix typescript build, wrong 
// packages/mws-moon-manager-ux/tsconfig.json etc...
export let state = writable({} as any);
