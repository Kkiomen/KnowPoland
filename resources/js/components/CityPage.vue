<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import CityLocatorMap from '@/components/CityLocatorMap.vue';
import PlaceGallery, { type Shot } from '@/components/PlaceGallery.vue';
import PlaceRoute, { type Stop } from '@/components/PlaceRoute.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { group, t } from '@/i18n';

/**
 * Every city page, in the layout the mockups settled on: bands read top to
 * bottom, the same rhythm as the history articles, so a city does not arrive
 * looking like a different website.
 *
 * A city differs from another only in its copy, its photographs and where it
 * sits, so all three arrive as props and nothing here knows which city it is
 * rendering. Sections a city has nothing to say about, such as neighbours or
 * further reading, are left out of the copy and disappear on their own.
 *
 * The name says city, but a place is whatever the copy makes of it: a castle
 * town, a mine, a forest or a whole lake district all fit, because the bands
 * only ask for a story, a route and photographs.
 */
const props = defineProps<{
    /** Translation group and image filename prefix, for example 'gdansk'. */
    city: string;
    /** Degrees east and north, for the locator pin. */
    lon: number;
    lat: number;
}>();

type Fact = { k: string; v: string };
type Moment = { year: string; note: string };
type Myth = { tag: string; title: string; note: string };
type Neighbour = {
    photo: string;
    name: string;
    query: string;
    reach: string;
    lede: string;
    note: string;
    credit: string;
};
type Reading = { title: string; note: string; href: string };

const key = (path: string): string => `${props.city}.${path}`;
const text = (path: string): string => t(key(path));

const facts = computed<Fact[]>(() => group<Fact[]>(key('facts.items')) ?? []);
const history = computed<Moment[]>(
    () => group<Moment[]>(key('history.items')) ?? [],
);
const myths = computed<Myth[]>(() => group<Myth[]>(key('myths.items')) ?? []);
const stops = computed<Stop[]>(() => group<Stop[]>(key('route.stops')) ?? []);
const shots = computed<Shot[]>(() => group<Shot[]>(key('gallery.shots')) ?? []);
const neighbours = computed<Neighbour[]>(
    () => group<Neighbour[]>(key('neighbours.items')) ?? [],
);
const reading = computed<Reading[]>(
    () => group<Reading[]>(key('reading.items')) ?? [],
);

/** Same endpoint as the route stops: shows the place and offers the way there. */
const directions = (query: string): string =>
    `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(query)}`;
</script>

<template>
    <Head :title="text('meta.title')">
        <meta
            head-key="description"
            name="description"
            :content="text('meta.description')"
        />
    </Head>

    <SiteHeader />

    <main class="bg-ink">
        <!-- The title card -->
        <section class="relative overflow-hidden">
            <picture>
                <source
                    type="image/avif"
                    :srcset="`/images/${city}-panorama-sm.avif 960w, /images/${city}-panorama-lg.avif 1280w`"
                    sizes="100vw"
                />
                <img
                    :src="`/images/${city}-panorama-sm.jpg`"
                    :srcset="`/images/${city}-panorama-sm.jpg 960w, /images/${city}-panorama-lg.jpg 1280w`"
                    sizes="100vw"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover opacity-25"
                    loading="eager"
                    decoding="async"
                />
            </picture>
            <div
                class="from-ink via-ink/75 absolute inset-0 bg-linear-to-r to-transparent"
            />
            <div
                class="from-ink/50 to-ink absolute inset-0 bg-linear-to-b via-transparent"
            />

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 pt-32 pb-16 sm:pt-36"
            >
                <p
                    class="text-bone-muted flex items-center gap-3 text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    <span
                        class="flex h-3.5 w-[3px] shrink-0 flex-col overflow-hidden rounded-full"
                        aria-hidden="true"
                    >
                        <span class="bg-flag-white flex-1" />
                        <span class="bg-flag-red flex-1" />
                    </span>
                    {{ text('hero.kicker') }}
                </p>

                <h1
                    class="font-display text-bone mt-3 text-[clamp(2.5rem,7vw,5.5rem)] leading-[0.95] tracking-tight uppercase"
                >
                    {{ text('hero.title') }}
                </h1>

                <p
                    class="text-bone-muted mt-5 max-w-[56ch] text-lg leading-relaxed"
                >
                    {{ text('hero.lede') }}
                </p>
                <p
                    class="text-bone-muted/80 mt-4 text-[0.6875rem] tracking-[0.2em] uppercase"
                >
                    {{ text('hero.meta_line') }}
                </p>
            </div>
        </section>

        <!-- What the place is -->
        <section class="border-hairline bg-ink-raised border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('opening.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('opening.title') }}
                </h2>
                <p
                    class="text-bone mt-5 max-w-[62ch] text-base leading-relaxed sm:text-lg"
                >
                    {{ text('opening.body_1') }}
                </p>
                <p class="text-bone-muted mt-4 max-w-[62ch] leading-relaxed">
                    {{ text('opening.body_2') }}
                </p>
            </div>
        </section>

        <!-- Where it is, and the short version -->
        <section class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <div
                    class="grid gap-10 lg:grid-cols-[minmax(0,26rem)_minmax(0,1fr)] lg:gap-14"
                >
                    <div>
                        <p
                            class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                        >
                            {{ text('map.label') }}
                        </p>
                        <div
                            class="border-hairline bg-ink/60 mt-4 rounded-sm border p-5"
                        >
                            <CityLocatorMap
                                :lon="lon"
                                :lat="lat"
                                :label="text('hero.title')"
                                :alt="text('map.alt')"
                            />
                        </div>
                        <p
                            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
                        >
                            {{ text('map.caption') }}
                        </p>
                    </div>

                    <div>
                        <h2
                            class="font-display text-bone text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                        >
                            {{ text('map.title') }}
                        </h2>
                        <p
                            class="text-bone-muted mt-4 max-w-[58ch] leading-relaxed"
                        >
                            {{ text('map.note') }}
                        </p>

                        <p
                            class="text-bone-muted mt-8 text-[0.6875rem] tracking-[0.28em] uppercase"
                        >
                            {{ text('facts.label') }}
                        </p>
                        <dl class="border-hairline mt-3 grid border-t">
                            <div
                                v-for="fact in facts"
                                :key="fact.k"
                                class="border-hairline flex justify-between gap-4 border-b py-2.5 text-sm"
                            >
                                <dt class="text-bone-muted">{{ fact.k }}</dt>
                                <dd
                                    class="font-display text-bone m-0 text-right text-xs tracking-wide uppercase"
                                >
                                    {{ fact.v }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </section>

        <!-- History -->
        <section class="border-hairline bg-ink-raised border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('history.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('history.title') }}
                </h2>

                <ol class="border-hairline mt-6 grid border-t">
                    <li
                        v-for="moment in history"
                        :key="moment.year"
                        class="border-hairline grid gap-2 border-b py-3.5 sm:grid-cols-[110px_minmax(0,1fr)] sm:gap-5"
                    >
                        <span
                            class="font-display text-amber text-sm tracking-wide"
                        >
                            {{ moment.year }}
                        </span>
                        <span
                            class="text-bone max-w-[62ch] text-[0.9375rem] leading-relaxed"
                        >
                            {{ moment.note }}
                        </span>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Myths -->
        <section class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('myths.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('myths.title') }}
                </h2>

                <div class="mt-7 grid gap-5 lg:grid-cols-3">
                    <div
                        v-for="myth in myths"
                        :key="myth.title"
                        class="border-hairline bg-ink-raised rounded-sm border p-5"
                    >
                        <span
                            class="border-hairline text-bone-muted inline-block border px-2 py-0.5 text-[0.625rem] tracking-[0.2em] uppercase"
                        >
                            {{ myth.tag }}
                        </span>
                        <h3
                            class="font-display text-bone mt-2.5 text-base tracking-wide uppercase"
                        >
                            {{ myth.title }}
                        </h3>
                        <p
                            class="text-bone-muted mt-2 text-[0.9375rem] leading-relaxed"
                        >
                            {{ myth.note }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- The route -->
        <section class="border-hairline bg-ink-raised border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('route.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('route.title') }}
                </h2>
                <p class="text-bone-muted mt-4 max-w-[62ch] leading-relaxed">
                    {{ text('route.note') }}
                </p>

                <div class="mt-6">
                    <PlaceRoute
                        :prefix="city"
                        :stops="stops"
                        :stay-label="text('route.stay')"
                        :maps-label="text('route.maps')"
                    />
                </div>
            </div>
        </section>

        <!-- The neighbours, for a city that has any worth the trip -->
        <section v-if="neighbours.length" class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('neighbours.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('neighbours.title') }}
                </h2>
                <p class="text-bone-muted mt-4 max-w-[62ch] leading-relaxed">
                    {{ text('neighbours.note') }}
                </p>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <article
                        v-for="neighbour in neighbours"
                        :key="neighbour.photo"
                        class="border-hairline bg-ink-raised hover:border-amber-deep/60 overflow-hidden rounded-sm border transition-colors duration-300"
                    >
                        <picture>
                            <source
                                type="image/avif"
                                :srcset="`/images/${city}-${neighbour.photo}-sm.avif 960w, /images/${city}-${neighbour.photo}-lg.avif 1280w`"
                                sizes="(min-width: 1024px) 50vw, 92vw"
                            />
                            <img
                                :src="`/images/${city}-${neighbour.photo}-sm.jpg`"
                                :srcset="`/images/${city}-${neighbour.photo}-sm.jpg 960w, /images/${city}-${neighbour.photo}-lg.jpg 1280w`"
                                sizes="(min-width: 1024px) 50vw, 92vw"
                                :alt="neighbour.name"
                                class="h-52 w-full object-cover sm:h-60"
                                loading="lazy"
                                decoding="async"
                            />
                        </picture>
                        <div class="p-5 sm:p-6">
                            <h3
                                class="font-display text-bone text-xl tracking-wide uppercase"
                            >
                                {{ neighbour.name }}
                            </h3>
                            <p class="text-bone mt-2 max-w-[52ch]">
                                {{ neighbour.lede }}
                            </p>
                            <p
                                class="text-bone-muted mt-3 max-w-[54ch] text-[0.9375rem] leading-relaxed"
                            >
                                {{ neighbour.note }}
                            </p>

                            <p
                                class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2"
                            >
                                <span class="text-bone-muted text-[0.8125rem]">
                                    {{ neighbour.reach }}
                                </span>
                                <a
                                    :href="directions(neighbour.query)"
                                    target="_blank"
                                    rel="noopener"
                                    class="border-hairline hover:border-amber-deep hover:text-amber focus-visible:outline-amber inline-flex min-h-10 items-center gap-1.5 rounded-sm border px-3.5 py-2 text-xs tracking-[0.06em] transition focus-visible:outline-2 focus-visible:outline-offset-2"
                                >
                                    {{ text('route.maps') }}
                                </a>
                            </p>
                            <p
                                class="text-bone-muted/80 mt-4 text-[0.625rem] tracking-[0.12em] uppercase"
                            >
                                {{ neighbour.credit }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Gallery -->
        <section class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('gallery.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('gallery.title') }}
                </h2>
                <p class="text-bone-muted mt-4 max-w-[62ch] leading-relaxed">
                    {{ text('gallery.note') }}
                </p>

                <div class="mt-6">
                    <PlaceGallery :prefix="city" :shots="shots" />
                </div>
            </div>
        </section>

        <!-- Where the rest of the story is told, for a place that leans on one -->
        <section
            v-if="reading.length"
            class="border-hairline bg-ink-raised border-t"
        >
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('reading.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 text-[clamp(1.5rem,3vw,2.3rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('reading.title') }}
                </h2>

                <ul class="border-hairline mt-6 grid border-t">
                    <li v-for="item in reading" :key="item.href">
                        <Link
                            :href="item.href"
                            class="border-hairline group hover:bg-bone/[0.03] focus-visible:outline-amber block border-b px-1 py-4 transition focus-visible:outline-2 focus-visible:outline-offset-2"
                        >
                            <span
                                class="font-display text-bone group-hover:text-amber block text-base tracking-wide uppercase transition-colors"
                            >
                                {{ item.title }}
                            </span>
                            <span
                                class="text-bone-muted mt-1.5 block max-w-[62ch] text-[0.9375rem] leading-relaxed"
                            >
                                {{ item.note }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Sources and the way back -->
        <section class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('sources.label') }}
                </p>
                <p class="text-bone-muted mt-3 max-w-[70ch] leading-relaxed">
                    {{ text('sources.body') }}
                </p>

                <Link
                    href="/places"
                    class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber mt-3 inline-flex items-center gap-2.5 border-b pt-5 pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                >
                    &larr; {{ text('next') }}
                </Link>

                <div class="mt-10">
                    <SiteFooter />
                </div>
            </div>
        </section>
    </main>
</template>
