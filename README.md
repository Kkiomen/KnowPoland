# Know Poland

**A country guide to Poland for people who want to understand where they are going.**

Live at [knowpoland.com](https://knowpoland.com) · in English and Polish (*Poznaj Polskę*)

Most travel sites tell you what to see. This one tries to tell you where you are: a thousand years of history told so that it explains the country you will actually land in, and an honest account of how people live here now, with the things that do not work included.

It is written for somebody who knows nothing about Poland. Nobody is assumed to know who Piłsudski was, what Solidarność was, or why 1939 and 1989 matter.

---

## What is on the site

| Section | What it covers |
|---|---|
| **History** | 49 articles from the tribes before the first state to Poland in the European Union, including the partitions, both world wars, the Holocaust, communism and 1989 |
| **Places** | 16 cities and landscapes, each with its own history, a walk that fits in a day and a gallery |
| **Poland today** | Safety, health care, the state in a phone, prices, live exchange rates from the National Bank of Poland |
| **Everyday life** | Greetings, pan and pani, pronunciation, phrases, customs, getting around, and the sentences best left unsaid |
| **Food** | Seventeen dishes with recipes, and the meals that hold the year together |
| **Polish roots** | How to trace an ancestor who emigrated: records, archives, surnames |
| **Start here** | A forty minute reading path for a first visit |

## How the writing works

The site has rules, and they are the reason it exists:

- **Every fact can be checked.** Dates, figures and claims come from named sources, and each longer article ends with a section saying what came from where. A number that ages, a price or a poll, carries the date it was true.
- **What historians reject does not appear.** A discredited theory is not given space, not even to be argued with. Founding legends stay, labelled plainly as stories people tell, with the archaeology next to them.
- **No gaps in the story.** Nobody walks on stage unannounced: every person, state, war and treaty is named and introduced the first time it appears.
- **Maps are real maps.** Where a page needs one, it shows a map a cartographer or historian actually made, captioned with its maker and date, and openable full screen.
- **One voice.** One person writes the site, so it says *I*, never the editorial *we*.
- **Sensitive history is handled as history.** The Second World War, the Holocaust and relations with the neighbours are written about factually, without nationalist framing, and memorial sites are never presented as attractions.

## Stack

- [Laravel 13](https://laravel.com) on PHP 8.4
- [Inertia v3](https://inertiajs.com) with [Vue 3](https://vuejs.org) and TypeScript
- [Tailwind CSS v4](https://tailwindcss.com), built with Vite
- [Pest](https://pestphp.com) for tests
- No database content: every page is a Vue component, and every word on it lives in a translation file

## Running it locally

You need PHP 8.4, Composer and Node.

```bash
git clone https://github.com/Kkiomen/KnowPoland.git
cd KnowPoland
composer setup
```

`composer setup` installs both sets of dependencies, creates `.env` from `.env.example`, generates the key, runs the migrations and builds the frontend.

`.env.example` is the production template, so switch the copy to local values:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

Then start the server, the queue and Vite together:

```bash
composer dev
```

and open [http://localhost:8000](http://localhost:8000). Add `?lang=pl` to any address for the Polish version.

## Languages

No text is written into a component. Every heading, sentence, button label, alt text and meta description lives in `lang/<locale>/site.php`, and the Vue side reads it with `t('group.key')`.

**Adding a language means copying `lang/en/site.php` to `lang/<locale>/site.php` and translating it.** The language switcher, the `hreflang` tags and the sitemap pick it up on their own. Nothing else changes.

A missing key renders as the key itself, so a gap is visible on the page rather than silent.

## Search engines

The app renders on the client, and link scrapers do not run JavaScript, so everything a crawler reads is printed on the server in `resources/views/app.blade.php`: title, description, canonical, `hreflang` alternates, Open Graph and Twitter cards, and JSON-LD structured data.

Each page registers itself in `app/Support/PageSeo.php`. `/sitemap.xml` builds itself from the router, listing every page once per language.

## Images

Every photograph exists twice in `public/images`: a JPEG and a smaller AVIF beside it. Every `<img>` sits inside a `<picture>` that offers the AVIF first, so a modern browser never downloads the JPEG and an old one still gets a picture.

**A new photograph needs its AVIF too.** A `<source>` pointing at a file that is not there does not fall back to the JPEG, it shows nothing, and the test suite fails on a JPEG without its AVIF.

Photographs and maps come mostly from Wikimedia Commons and carry their own licences, credited on the page where they appear.

## Tests

```bash
php artisan test --compact
```

Beyond the usual feature tests, the suite guards the things that are easy to break without noticing: every page has a title and description of the right length in every language, no semicolon appears anywhere in the copy and no long dash in a title or description, every JPEG has its AVIF, the error page is kept out of the index, and a stray Vite hot file cannot take the live site down.

## Deploying

The production server has no Node, so **the compiled frontend is committed** in `public/build`. Run `npm run build` before committing any change to `resources/js` or `resources/css`.

On the server:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan poland:warm
```

`poland:warm` fetches the exchange rates and statistics the *Poland today* page prints, so the first reader after a deployment does not wait for those services to answer.

### HTTPS and the one canonical address

- Point the domain's document root at `public/`, never at the project root, or `.env` becomes downloadable.
- Get a certificate from the host (Let's Encrypt in most panels, `certbot` on a VPS). Behind Cloudflare, use the *Full (strict)* SSL mode.
- In `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://knowpoland.com`, `SESSION_SECURE_COOKIE=true`.

The rest is in the application. In production every request that is not `https://knowpoland.com` gets a permanent redirect there, keeping the path and the language, and every secure response carries a one year `Strict-Transport-Security` header. Forwarded headers from a proxy are trusted, so TLS ending at Cloudflare or a load balancer does not cause a loop, and `/up` answers over plain http for health checks. `public/.htaccess` repeats the redirect for images and other static files on Apache.

To check: `curl -I http://www.knowpoland.com` should answer `301` with `Location: https://knowpoland.com/`.

Reader counting is off by default. Set `ANALYTICS_SCRIPT` and `ANALYTICS_DOMAIN` to load a cookie-free counter such as Plausible, and the site still needs no consent banner.

## Licence

No licence has been chosen for the code yet. Until one is, all rights are reserved. The images belong to their authors under the licences credited on the site.
