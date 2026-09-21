<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import UsefulLinks from '@/components/UsefulLinks.vue';
import { group, t } from '@/i18n';

/**
 * Everyday life and language.
 *
 * The site explains what this country is. This page explains how to be in it:
 * the greeting nobody tells a visitor about, the two levels of address, enough
 * pronunciation to read a map, and the customs that trip people up. It is the
 * one page a reader is expected to come back to with a phone in their hand,
 * so the reference blocks are tables rather than prose.
 */
/**
 * A warning tile can carry a `link` label, and the one tile that does sends the
 * reader to the article behind it. The label lives in the translation file and
 * the address lives here, so no markup ever goes into the copy.
 */
type Pair = { k: string; v: string; link?: string };
type Phrase = { pl: string; say: string; en: string };

const text = (path: string): string => t(`everyday.${path}`);
const rows = (path: string): Pair[] => group<Pair[]>(`everyday.${path}`) ?? [];

/** Beetroot, crust, dill, honey, wine, cycled so a block keeps its colour. */
const CHIPS = ['#b32347', '#a8520f', '#457036', '#8a5a10', '#7c1d32'] as const;
const chip = (index: number): string => CHIPS[index % CHIPS.length];

const address = computed(() => rows('forms.items'));
const letters = computed(() => rows('speak.letters'));
const names = computed(() => rows('speak.names'));
const manners = computed(() => rows('visit.items'));
const dates = computed(() => rows('calendar.items'));
const travel = computed(() => rows('travel.items'));
const practical = computed(() => rows('practical.items'));
const warnings = computed(() => rows('careful.items'));
const phrases = computed<Phrase[]>(
    () => group<Phrase[]>('everyday.phrases.items') ?? [],
);
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

    <main class="bg-[#fbf7f0]">
        <!-- The title card -->
        <section class="relative overflow-hidden bg-[#0b0a08]">
            <picture>
                <source
                    type="image/avif"
                    srcset="
                        /images/tlo-life-1000.avif 1000w,
                        /images/tlo-life-1400.avif 1400w
                    "
                    sizes="100vw"
                />
                <img
                    src="/images/tlo-life-1000.jpg"
                    srcset="
                        /images/tlo-life-1000.jpg 1000w,
                        /images/tlo-life-1400.jpg 1400w
                    "
                    sizes="100vw"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover opacity-55"
                    loading="eager"
                    decoding="async"
                />
            </picture>
            <div
                class="absolute inset-0 bg-[linear-gradient(100deg,rgba(11,10,8,.95)_0%,rgba(11,10,8,.78)_48%,rgba(11,10,8,.18)_100%)]"
            />

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 pt-32 pb-16 sm:pt-36"
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

        <!-- The greeting, which is the single most useful thing on this page -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                >
                    {{ text('greet.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('greet.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('greet.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('greet.body_2') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Two levels of address, which is where a foreigner slips first -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('forms.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('forms.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('forms.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('forms.body_2') }}
                    </p>
                </div>

                <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(form, index) in address"
                        :key="form.k"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index) }"
                    >
                        <dt
                            class="font-display text-[1.25rem] leading-none text-white"
                        >
                            {{ form.k }}
                        </dt>
                        <dd
                            class="m-0 mt-3 text-[0.9375rem] leading-relaxed text-white"
                        >
                            {{ form.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Will you be understood -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                >
                    {{ text('english.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('english.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('english.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('english.body_2') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Enough pronunciation to read a map -->
        <section class="bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('speak.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#f4ede0] uppercase"
                >
                    {{ text('speak.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#cabfad]">
                    {{ text('speak.note') }}
                </p>

                <dl class="mt-8 grid gap-x-10 gap-y-5 sm:grid-cols-2">
                    <div
                        v-for="letter in letters"
                        :key="letter.k"
                        class="flex flex-wrap items-baseline gap-x-4 border-t border-white/15 pt-3"
                    >
                        <dt
                            class="font-display min-w-[5rem] text-[1.5rem] leading-none text-[#f5b24f]"
                        >
                            {{ letter.k }}
                        </dt>
                        <dd
                            class="m-0 flex-1 text-[0.9375rem] leading-relaxed text-[#cabfad]"
                        >
                            {{ letter.v }}
                        </dd>
                    </div>
                </dl>

                <h3
                    class="mt-12 text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('speak.names_label') }}
                </h3>
                <dl
                    class="mt-4 grid gap-x-10 gap-y-3 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="name in names"
                        :key="name.k"
                        class="flex flex-wrap items-baseline justify-between gap-x-4 border-b border-white/10 pb-2"
                    >
                        <dt
                            class="font-display text-[1.0625rem] text-[#f4ede0]"
                        >
                            {{ name.k }}
                        </dt>
                        <dd class="m-0 text-[0.9375rem] text-[#cabfad]">
                            {{ name.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- The phrases, laid out so a phone can be held up to a waiter -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('phrases.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('phrases.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('phrases.note') }}
                </p>

                <dl
                    class="mt-8 grid gap-px overflow-hidden rounded-lg bg-[#e0d3bd]"
                >
                    <div
                        v-for="phrase in phrases"
                        :key="phrase.pl"
                        class="grid gap-x-8 gap-y-1 bg-white px-5 py-4 sm:grid-cols-[minmax(0,15rem)_minmax(0,1fr)]"
                    >
                        <dt
                            class="font-display text-[1.125rem] leading-snug text-[#221e19]"
                        >
                            {{ phrase.pl }}
                        </dt>
                        <dd
                            class="m-0 text-[0.9375rem] leading-relaxed text-[#b32347] sm:col-start-1"
                        >
                            {{ phrase.say }}
                        </dd>
                        <dd
                            class="m-0 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459] sm:col-start-2 sm:row-start-1 sm:row-end-3"
                        >
                            {{ phrase.en }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Being invited in -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#7c1d32] uppercase"
                >
                    {{ text('visit.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('visit.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('visit.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('visit.body_2') }}
                    </p>
                </div>

                <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(manner, index) in manners"
                        :key="manner.k"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index + 1) }"
                    >
                        <dt
                            class="font-display text-[1.25rem] leading-none text-white"
                        >
                            {{ manner.k }}
                        </dt>
                        <dd
                            class="m-0 mt-3 text-[0.9375rem] leading-relaxed text-white"
                        >
                            {{ manner.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- The dates that wreck a plan -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('calendar.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('calendar.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('calendar.note') }}
                </p>

                <dl class="mt-8 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="date in dates"
                        :key="date.k"
                        class="border-t border-[#d9cbb3] pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ date.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459]"
                        >
                            {{ date.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Crossing the country, which the calendar section makes people ask -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                >
                    {{ text('travel.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('travel.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('travel.note') }}
                </p>

                <dl class="mt-8 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="way in travel"
                        :key="way.k"
                        class="border-t border-[#e0d3bd] pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ way.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459]"
                        >
                            {{ way.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- The things everybody asks anyway -->
        <section class="bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('practical.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#f4ede0] uppercase"
                >
                    {{ text('practical.title') }}
                </h2>

                <dl class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="item in practical"
                        :key="item.k"
                        class="border-t border-white/15 pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#f4ede0]"
                        >
                            {{ item.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 text-[0.9375rem] leading-relaxed text-[#cabfad]"
                        >
                            {{ item.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Every app named above, with its address -->
        <UsefulLinks />

        <!-- Four sentences that change the conversation -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                >
                    {{ text('careful.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('careful.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('careful.note') }}
                </p>

                <dl class="mt-8 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="warning in warnings"
                        :key="warning.k"
                        class="border-l-2 border-[#b32347] pl-5"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ warning.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459]"
                        >
                            {{ warning.v }}
                        </dd>
                        <dd v-if="warning.link" class="m-0">
                            <Link
                                href="/history/volhynia-1943"
                                class="mt-1 inline-flex min-h-[44px] items-center text-[0.6875rem] tracking-[0.18em] text-[#b32347] uppercase transition hover:text-[#7c1d32] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#b32347]"
                            >
                                <span class="border-b border-[#b32347] pb-1">
                                    {{ warning.link }} &rarr;
                                </span>
                            </Link>
                        </dd>
                    </div>
                </dl>

                <p
                    class="mt-8 flex max-w-[70ch] gap-4 text-[1.0625rem] leading-relaxed text-[#3b352d]"
                >
                    <span
                        class="flex w-1 shrink-0 flex-col overflow-hidden rounded-full"
                        aria-hidden="true"
                    >
                        <span class="bg-flag-white flex-1" />
                        <span class="bg-flag-red flex-1" />
                    </span>
                    {{ text('careful.close') }}
                </p>
            </div>
        </section>

        <!-- Sources and the way back -->
        <section class="bg-ink">
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
