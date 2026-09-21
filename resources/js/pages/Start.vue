<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { group, t } from '@/i18n';

/**
 * The reading path for somebody who arrived knowing nothing.
 *
 * The home page has always offered this as a promise. It is a route through
 * pages that already exist rather than new writing, so the addresses live here
 * and only the words live in the translation file. The order is the argument:
 * where the country is, what happened to it, how it lives now, then the three
 * practical ones.
 */
type Step = { n: string; title: string; note: string; link: string };

const text = (path: string): string => t(`start.${path}`);

/** Same order as the copy, and the copy carries no addresses of its own. */
const ADDRESSES = [
    '/history/where-to-stand-in-it',
    '/history',
    '/poland-today',
    '/food',
    '/everyday-life',
    '/places',
] as const;

const steps = computed<Step[]>(() => group<Step[]>('start.steps') ?? []);
const addressFor = (index: number): string => ADDRESSES[index] ?? '/';
</script>

<template>
    <Head :title="text('meta.title')">
        <meta name="description" :content="text('meta.description')" />
    </Head>

    <SiteHeader />

    <main class="bg-[#fbf7f0]">
        <section class="bg-[#0b0a08]">
            <div
                class="mx-auto w-full max-w-[1200px] px-6 pt-32 pb-16 sm:pt-36"
            >
                <p
                    class="flex items-center gap-3 text-[0.6875rem] tracking-[0.28em] text-[#f5b24f] uppercase"
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
                    class="font-display mt-3 text-[clamp(2.5rem,7vw,5.5rem)] leading-[0.95] tracking-tight text-[#fdf9f1] uppercase"
                >
                    {{ text('hero.title') }}
                    <span class="block text-[#f5b24f]">{{
                        text('hero.title_accent')
                    }}</span>
                </h1>

                <p
                    class="mt-5 max-w-[56ch] text-lg leading-relaxed text-[#e6dccb]"
                >
                    {{ text('hero.lede') }}
                </p>
                <p
                    class="mt-4 text-[0.6875rem] tracking-[0.2em] text-[#bdb2a0] uppercase"
                >
                    {{ text('hero.meta_line') }}
                </p>
            </div>
        </section>

        <!-- The path itself. Numbered because the order carries the argument. -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p class="max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('note') }}
                </p>

                <ol class="mt-10 grid list-none gap-px bg-[#e0d3bd] p-0">
                    <li v-for="(step, index) in steps" :key="step.n">
                        <Link
                            :href="addressFor(index)"
                            class="group focus-visible:outline-amber block bg-[#fbf7f0] px-5 py-6 transition hover:bg-white focus-visible:outline-2 focus-visible:-outline-offset-2"
                        >
                            <div
                                class="grid gap-x-6 gap-y-2 sm:grid-cols-[4rem_1fr]"
                            >
                                <span
                                    class="font-display text-[1.75rem] leading-none text-[#b32347] tabular-nums"
                                >
                                    {{ step.n }}
                                </span>
                                <div>
                                    <h2
                                        class="font-display text-[1.25rem] tracking-wide text-[#221e19] uppercase transition-colors group-hover:text-[#b32347]"
                                    >
                                        {{ step.title }}
                                    </h2>
                                    <p
                                        class="mt-2 max-w-[68ch] leading-relaxed text-[#6e6459]"
                                    >
                                        {{ step.note }}
                                    </p>
                                    <span
                                        class="mt-3 inline-block border-b border-[#b32347] pb-1 text-[0.6875rem] tracking-[0.18em] text-[#b32347] uppercase"
                                    >
                                        {{ step.link }} &rarr;
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ol>
            </div>
        </section>

        <section class="bg-ink">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <h2
                    class="font-display text-bone max-w-[24ch] text-[clamp(1.5rem,3vw,2.25rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('after.title') }}
                </h2>
                <p class="text-bone-muted mt-4 max-w-[68ch] leading-relaxed">
                    {{ text('after.body') }}
                </p>

                <Link
                    href="/"
                    class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber text-bone mt-3 inline-flex items-center gap-2.5 border-b pt-5 pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
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
