<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { group, t } from '@/i18n';

/**
 * The page a reader gets when the address does not exist, or when something
 * on the server broke.
 *
 * Most people arrive on this site from a search engine, and a search engine
 * keeps addresses long after they change, so this page is as much a part of
 * the site as any chapter: it says what happened in the reader's language and
 * hands them the four doors worth walking through instead.
 */
const props = withDefaults(defineProps<{ status?: number }>(), { status: 404 });

type Door = { k: string; v: string };

const text = (path: string): string => t(`notfound.${path}`);

/** 404 is a wrong address, anything else is a fault on my side. */
const mood = computed(() => (props.status === 404 ? 'lost' : 'broken'));

const doors = computed<Door[]>(() => group<Door[]>('notfound.doors') ?? []);

/** The copy lives in the translation file, the addresses live here. */
const HREFS = ['/start-here', '/history', '/poland-today', '/places'];

/** Beetroot, crust, dill, honey, wine, cycled so a block keeps its colour. */
const CHIPS = ['#b32347', '#a8520f', '#457036', '#8a5a10', '#7c1d32'] as const;
const chip = (index: number): string => CHIPS[index % CHIPS.length];
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

    <main class="bg-ink min-h-dvh">
        <section
            class="mx-auto w-full max-w-[1200px] px-6 pt-32 pb-16 sm:pt-40"
        >
            <p
                class="text-amber text-[0.6875rem] tracking-[0.28em] uppercase"
                aria-hidden="true"
            >
                {{ props.status }}
            </p>
            <h1
                class="font-display text-bone mt-3 max-w-[20ch] hyphens-auto text-[clamp(1.75rem,5.5vw,3.5rem)] leading-[1.05] tracking-tight break-words uppercase"
            >
                {{ text(`${mood}.title`) }}
            </h1>
            <p
                class="text-bone-muted mt-6 max-w-[62ch] text-[1.0625rem] leading-relaxed"
            >
                {{ text(`${mood}.body`) }}
            </p>

            <h2
                class="text-bone-muted mt-14 text-[0.6875rem] tracking-[0.28em] uppercase"
            >
                {{ text('doors_label') }}
            </h2>

            <ul class="mt-6 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                <li
                    v-for="(door, index) in doors"
                    :key="door.k"
                    class="border-t pt-4"
                    :style="{ borderTopColor: chip(index) }"
                >
                    <Link
                        :href="HREFS[index] ?? '/'"
                        class="font-display text-bone hover:text-amber focus-visible:outline-amber inline-flex min-h-[44px] items-center text-[1.0625rem] tracking-wide transition-colors focus-visible:outline-2 focus-visible:outline-offset-4"
                    >
                        {{ door.k }}
                    </Link>
                    <p
                        class="text-bone-muted max-w-[52ch] text-[0.9375rem] leading-relaxed"
                    >
                        {{ door.v }}
                    </p>
                </li>
            </ul>

            <div class="mt-14">
                <SiteFooter />
            </div>
        </section>
    </main>
</template>
