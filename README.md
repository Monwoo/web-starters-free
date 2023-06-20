# Monwoo Web Starters (MWS) Apache-2.0 (Free)
<img src="https://miguel.monwoo.com/embedded-iframes/prod/embeddable-iframe/favicomatic/favicon-96x96.png" alt="" width="42"/> [Build by Miguel Monwoo, **Open Source Apache-2.0 with Copyright © MONWOO 2023**](https://moonkiosk.monwoo.com/en/categorie-produit/produced-solutions/mws_en/)

Contain multiple free webstarters and parts of opened confidential starters (Optional full code under private license per domains, or for confidential knowledge with no rights to duplicate without appropriate notice or license)

## Apps and Packages

### Monwoo Web Starters
- `mws-svelte-wp-display-0.0.5`: Free samples for [MWS Svelte-WP-display](https://moonkiosk.monwoo.com/en/produit/mws-svelte-wp-display_en/) (Paied version is confidential. Need paid licence to be fully integrated)
- `mws-sf-pdf-billings`: Open Source Apache-2.0 PDF Billings Server in Symfony 5. Provide a PHP local tool for pre-fillable self signed PDF billing templates.


### Next JS example
- `next-js-docs`: a [Next.js](https://nextjs.org/) app
- `next-js-web`: another [Next.js](https://nextjs.org/) app
- `next-js-ui`: a stub React component library shared by both `next-js-web` and `next-js-docs` applications
- `next-js-eslint-config`: `eslint` configurations (includes `eslint-config-next` and `eslint-config-prettier`). WARNING : package name set to `eslint-config-next-js` for config compatibility purpose.
- `next-js-tsconfig`: `tsconfig.json` used throughout the monorepo `next-js` apps and packages

### Svelte example
- `svelte-docs`: a [svelte-kit](https://kit.svelte.dev/) app
- `svelte-web`: another [svelte-kit](https://kit.svelte.dev/) app
- `svelte-ui`: a Svelte component library shared by `svelte-web` and `svelte-docs` applications
- `svelte-eslint-config`: `eslint` configurations (includes `eslint-plugin-svelte` and `eslint-config-prettier`). WARNING : package name set to `eslint-config-svelte` for config compatibility purpose.
- `svelte-tsconfig`: `tsconfig.json` used throughout the monorepo `svelte` apps and packages

### Supports

- You can use regular features of : [github.com/Monwoo/web-starters-free/issues](https://github.com/Monwoo/web-starters-free/issues)

To support us and/or help us open more software, send a subvention with :
- [www.monwoo.com/don](https://www.monwoo.com/don)

### Build

```bash
# install :
pnpm install

# launch turbo for dev :
turbo dev

# launch turbo to build all apps
# and packages (if build script inside package.json) :
turbo build

# Check lint rules for all apps and packages :
turbo lint

# Format :

# TIPS : use versioning, push to have clean repo,
#        then format and check new possible changes

# Sync versioning
git add -A && git commit -m "[MWS] <pending modifications details>"
git push
# Format all apps and packages
turbo format
# Check and review changes before next commit :
git diff

```

# BASED on : Turborepo starter

This starter come from an official pnpm starter turborepo.

## What's inside?

This turborepo uses [pnpm](https://pnpm.io) as a package manager. It includes the following packages/apps:

## Create a new Svelte turbo repo :

Run the following command:

```sh
npx create-turbo@latest -e with-svelte
```

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
