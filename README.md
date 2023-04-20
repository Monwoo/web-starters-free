# Monwoo Web Starter (MWS) Open source Apache-2.0 (Free)
<img src="https://miguel.monwoo.com/embedded-iframes/prod/embeddable-iframe/favicomatic/favicon-96x96.png" alt="" width="42"/> [Build by Miguel Monwoo, **Copyright © MONWOO 2023**, all rights reserved.](https://moonkiosk.monwoo.com/en/categorie-produit/produced-solutions/mws_en/)

Will contain multiple free webstarters (ready to use) and some parts of opened confidential starter (Optional full code under private license per domains, or for confidential knowledge with no rights to duplicate without appropriate notice or license)

# BASED on : Turborepo starter

This is an official pnpm starter turborepo.

## What's inside?

This turborepo uses [pnpm](https://pnpm.io) as a package manager. It includes the following packages/apps:

## Create a new Svelte turbo repo :

Run the following command:

```sh
npx create-turbo@latest -e with-svelte
```

### Apps and Packages

- `mws-svelte-wp-display-0.0.5`: Free samples for [MWS Svelte-WP-display](https://moonkiosk.monwoo.com/en/produit/mws-svelte-wp-display_en/) (Paied version is confidential)

- `next-js-docs`: a [Next.js](https://nextjs.org/) app
- `next-js-web`: another [Next.js](https://nextjs.org/) app
- `next-js-ui`: a stub React component library shared by both `next-js-web` and `next-js-docs` applications
- `eslint-config-next-js`: `eslint` configurations (includes `eslint-config-next` and `eslint-config-prettier`)
- `tsconfig`: `tsconfig.json`s used throughout the monorepo

- `svelte-docs`: a [svelte-kit](https://kit.svelte.dev/) app
- `svelte-web`: another [svelte-kit](https://kit.svelte.dev/) app
- `svelte-ui`: a stub Svelte component library shared by both `web` and `docs` applications
- `eslint-config-svelte`: `eslint` configurations (includes `eslint-plugin-svelte` and `eslint-config-prettier`)

Each package/app is 100% [TypeScript](https://www.typescriptlang.org/).

### Utilities

This turborepo has some additional tools already setup for you:

- [TypeScript](https://www.typescriptlang.org/) for static type checking
- [ESLint](https://eslint.org/) for code linting
- [Prettier](https://prettier.io) for code formatting

### Build

To build all apps and packages, run the following command:

```
cd my-turborepo
pnpm run build
```

### Develop

To develop all apps and packages, run the following command:

```
cd my-turborepo
pnpm run dev
```

### Remote Caching

Turborepo can use a technique known as [Remote Caching](https://turbo.build/repo/docs/core-concepts/remote-caching) to share cache artifacts across machines, enabling you to share build caches with your team and CI/CD pipelines.

By default, Turborepo will cache locally. To enable Remote Caching you will need an account with Vercel. If you don't have an account you can [create one](https://vercel.com/signup), then enter the following commands:

```
cd my-turborepo
pnpm dlx turbo login
```

This will authenticate the Turborepo CLI with your [Vercel account](https://vercel.com/docs/concepts/personal-accounts/overview).

Next, you can link your Turborepo to your Remote Cache by running the following command from the root of your turborepo:

```
pnpm dlx turbo link
```

## Useful Links

Learn more about the power of Turborepo:

- [Tasks](https://turbo.build/repo/docs/core-concepts/monorepos/running-tasks)
- [Caching](https://turbo.build/repo/docs/core-concepts/caching)
- [Remote Caching](https://turbo.build/repo/docs/core-concepts/remote-caching)
- [Filtering](https://turbo.build/repo/docs/core-concepts/monorepos/filtering)
- [Configuration Options](https://turbo.build/repo/docs/reference/configuration)
- [CLI Usage](https://turbo.build/repo/docs/reference/command-line-reference)
