<script setup lang="ts">
import ExhibitImage from '@/components/ExhibitImage.vue';

/**
 * What to see in a city, as a route rather than a list: the stops stand in
 * walking order, each one says how long the next leg takes, and each one
 * opens in Google Maps so the reader can be routed there from where they
 * are. A list of highlights leaves the planning to the reader; this does not.
 */
export type Stop = {
    /** Image slug: public/images/<prefix>-<photo>-{sm,lg}.jpg */
    photo: string;
    name: string;
    /** What to search for in Google Maps. Plain ASCII travels better. */
    query: string;
    note: string;
    /** How long to spend here. */
    stay: string;
    /** How the reader gets here from the previous stop. */
    arrive: string;
    credit: string;
};

defineProps<{
    /** Image filename prefix, so one component serves every city. */
    prefix: string;
    stops: Stop[];
    /** Label before the time spent at a stop, already translated. */
    stayLabel: string;
    /** Label on the map link, already translated. */
    mapsLabel: string;
}>();

/**
 * The directions endpoint rather than the search one: it shows the place and
 * offers the way there in the same tap, which is what someone standing in a
 * strange city actually wants.
 */
const directions = (query: string): string =>
    `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(query)}`;
</script>

<template>
    <div class="grid">
        <template v-for="(stop, index) in stops" :key="stop.photo">
            <p
                v-if="index > 0"
                class="text-bone-muted flex items-center gap-2.5 py-3.5 pl-7 text-[0.8125rem]"
            >
                <span class="text-amber text-base" aria-hidden="true"
                    >&darr;</span
                >
                {{ stop.arrive }}
            </p>

            <article
                class="border-hairline bg-ink-raised hover:border-amber-deep/60 grid gap-5 rounded-sm border p-4 transition-colors duration-300 sm:grid-cols-[240px_minmax(0,1fr)] sm:items-start sm:p-5"
            >
                <div class="relative overflow-hidden rounded-sm">
                    <ExhibitImage
                        :src="`/images/${prefix}-${stop.photo}-sm.jpg`"
                        :srcset="`/images/${prefix}-${stop.photo}-sm.jpg 960w, /images/${prefix}-${stop.photo}-lg.jpg 1280w`"
                        sizes="(min-width: 640px) 220px, 92vw"
                        :full="`/images/${prefix}-${stop.photo}-lg.jpg`"
                        :alt="stop.name"
                        :caption="stop.name"
                        :credit="stop.credit"
                    />
                    <span
                        class="bg-amber text-ink font-display absolute top-0 left-0 rounded-br-sm px-2.5 py-1 text-xs tracking-wider"
                    >
                        {{ String(index + 1).padStart(2, '0') }}
                    </span>
                </div>

                <div>
                    <h3
                        class="font-display text-bone text-base tracking-wide uppercase"
                    >
                        {{ stop.name }}
                    </h3>
                    <p
                        class="text-bone-muted mt-2 max-w-[54ch] text-[0.9375rem] leading-relaxed"
                    >
                        {{ stop.note }}
                    </p>

                    <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span class="text-bone-muted text-[0.8125rem]">
                            {{ stayLabel }} {{ stop.stay }}
                        </span>
                        <a
                            :href="directions(stop.query)"
                            target="_blank"
                            rel="noopener"
                            class="border-hairline hover:border-amber-deep hover:text-amber focus-visible:outline-amber inline-flex min-h-10 items-center gap-1.5 rounded-sm border px-3.5 py-2 text-[0.75rem] tracking-[0.06em] transition focus-visible:outline-2 focus-visible:outline-offset-2"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                width="13"
                                height="13"
                                aria-hidden="true"
                            >
                                <path
                                    fill="currentColor"
                                    d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z"
                                />
                            </svg>
                            {{ mapsLabel }}
                        </a>
                    </p>
                </div>
            </article>
        </template>
    </div>
</template>
