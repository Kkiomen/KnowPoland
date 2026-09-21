<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { group, t } from '@/i18n';
import { places } from '@/places';
import { polandOutline } from '@/poland-outline';

/**
 * The index of cities. One is written and the rest say so, because a list
 * that pretends everything is ready wastes the reader's time, and a list of
 * names with nothing behind them tells them nothing either: each entry
 * carries the one thing that makes that city worth the trip.
 */
type City = { name: string; region: string; note: string };

const cities = computed<Record<string, City>>(
    () => group<Record<string, City>>('places.cities') ?? {},
);

const rows = computed(() =>
    places
        .map((place) => ({ ...place, city: cities.value[place.key] }))
        .filter((row) => row.city !== undefined),
);

/** Roughly where each one sits, for the little map beside the list. */
const COORDS: Record<string, { lon: number; lat: number }> = {
    gdansk: { lon: 18.6466, lat: 54.352 },
    warszawa: { lon: 21.0122, lat: 52.2297 },
    krakow: { lon: 19.945, lat: 50.0647 },
    wroclaw: { lon: 17.0385, lat: 51.1079 },
    lodz: { lon: 19.4559, lat: 51.7592 },
    poznan: { lon: 16.9252, lat: 52.4064 },
    szczecin: { lon: 14.5528, lat: 53.4285 },
    bydgoszcz: { lon: 18.0084, lat: 53.1235 },
    lublin: { lon: 22.5684, lat: 51.2465 },
    katowice: { lon: 19.0238, lat: 50.2649 },
    zakopane: { lon: 19.9496, lat: 49.2992 },
    torun: { lon: 18.5985, lat: 53.0138 },
    malbork: { lon: 19.0279, lat: 54.0397 },
    wieliczka: { lon: 20.0546, lat: 49.9847 },
    bialowieza: { lon: 23.8419, lat: 52.7003 },
    mazury: { lon: 21.5675, lat: 53.8022 },
};

/** Same projection the locator map uses, so the dots and the outline agree. */
const dot = (key: string) => {
    const point = COORDS[key];

    return point
        ? { x: (point.lon - 14.12) * 99.7, y: (54.84 - point.lat) * 160 }
        : null;
};

const dots = computed(() =>
    rows.value
        .map((row) => ({
            key: row.key,
            ready: row.href !== undefined,
            at: dot(row.key),
        }))
        .filter((entry) => entry.at !== null),
);
</script>

<template>
    <Head :title="t('places.meta.title')">
        <meta
            head-key="description"
            name="description"
            :content="t('places.meta.description')"
        />
    </Head>

    <SiteHeader />

    <main class="bg-ink">
        <section class="border-hairline border-b">
            <div
                class="mx-auto w-full max-w-[1200px] px-6 pt-32 pb-14 sm:pt-36 sm:pb-16"
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
                    {{ t('places.hero.kicker') }}
                </p>

                <h1
                    class="font-display text-bone mt-3 text-[clamp(2.2rem,6vw,4.4rem)] leading-[0.98] tracking-tight uppercase"
                >
                    {{ t('places.hero.title') }}
                    <span class="text-amber">{{
                        t('places.hero.title_accent')
                    }}</span>
                </h1>

                <p
                    class="text-bone-muted mt-5 max-w-[62ch] text-lg leading-relaxed"
                >
                    {{ t('places.hero.lede') }}
                </p>
                <p
                    class="text-bone-muted/80 mt-4 text-[0.6875rem] tracking-[0.2em] uppercase"
                >
                    {{ t('places.hero.meta_line') }}
                </p>
            </div>
        </section>

        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-14 sm:py-16">
                <div
                    class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:gap-16"
                >
                    <ul class="border-hairline grid border-t">
                        <li v-for="row in rows" :key="row.key">
                            <component
                                :is="row.href ? Link : 'div'"
                                :href="row.href"
                                class="border-hairline group block border-b px-1 py-5 transition"
                                :class="
                                    row.href
                                        ? 'hover:bg-bone/[0.03] focus-visible:outline-amber focus-visible:outline-2 focus-visible:outline-offset-2'
                                        : ''
                                "
                            >
                                <span
                                    class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1"
                                >
                                    <span
                                        class="font-display text-bone text-lg tracking-wide uppercase transition-colors"
                                        :class="
                                            row.href
                                                ? 'group-hover:text-amber'
                                                : ''
                                        "
                                    >
                                        {{ row.city.name }}
                                    </span>
                                    <span
                                        class="text-[0.625rem] tracking-[0.2em] uppercase"
                                        :class="
                                            row.href
                                                ? 'text-amber'
                                                : 'text-bone-muted/80'
                                        "
                                    >
                                        {{
                                            row.href
                                                ? t('places.ready')
                                                : t('places.soon')
                                        }}
                                    </span>
                                </span>
                                <span
                                    class="text-bone-muted/80 mt-1 block text-[0.6875rem] tracking-[0.16em] uppercase"
                                >
                                    {{ row.city.region }}
                                </span>
                                <span
                                    class="text-bone-muted mt-2 block max-w-[58ch] text-[0.9375rem] leading-relaxed"
                                >
                                    {{ row.city.note }}
                                </span>
                            </component>
                        </li>
                    </ul>

                    <div class="lg:sticky lg:top-24 lg:self-start">
                        <div
                            class="border-hairline bg-ink-raised rounded-sm border p-5"
                        >
                            <svg
                                viewBox="0 0 1000 935"
                                role="img"
                                :aria-label="t('places.map_alt')"
                                class="h-auto w-full"
                            >
                                <path
                                    :d="polandOutline"
                                    class="fill-bone/[0.06] stroke-bone/40"
                                    stroke-width="4"
                                />
                                <circle
                                    v-for="entry in dots"
                                    :key="entry.key"
                                    :cx="entry.at!.x"
                                    :cy="entry.at!.y"
                                    :r="entry.ready ? 16 : 10"
                                    :class="
                                        entry.ready
                                            ? 'fill-flag-red'
                                            : 'fill-bone/35'
                                    "
                                />
                            </svg>
                            <p
                                class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
                            >
                                {{ t('places.map_caption') }}
                            </p>
                        </div>
                    </div>
                </div>

                <Link
                    href="/"
                    class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber mt-10 inline-flex items-center gap-2.5 border-b pt-5 pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                >
                    &larr; {{ t('places.back') }}
                </Link>

                <div class="mt-10">
                    <SiteFooter />
                </div>
            </div>
        </section>
    </main>
</template>
