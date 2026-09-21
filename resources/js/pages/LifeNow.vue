<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import UsefulLinks from '@/components/UsefulLinks.vue';
import { group, t } from '@/i18n';

/**
 * Living in Poland now.
 *
 * The rest of the site explains what happened to this country. This page is an
 * argument about what it is like to live in it, made in the first person,
 * because an argument needs somebody making it, and the honest parts of it
 * include the things that do not work.
 *
 * It is the only page with numbers that move, so the figures arrive as props
 * rather than as copy: the dated ones from config, the exchange rates straight
 * from the National Bank of Poland.
 */
type Figure = {
    key: string;
    value: string;
    checked: string;
    live?: boolean;
    year?: string;
};
type Price = { key: string; value: string; checked: string };
type Rate = { code: string; rate: string; date: string };
type Row = { name: string; note: string };

const props = defineProps<{
    figures: Figure[];
    prices: Price[];
    rates: Rate[];
}>();

const text = (path: string): string => t(`life_now.${path}`);

const services = computed<Row[]>(
    () => group<Row[]>('life_now.digital.items') ?? [],
);

/** Beetroot, crust, dill, honey, wine, cycled so a block keeps its colour. */
const CHIPS = ['#b32347', '#a8520f', '#457036', '#8a5a10', '#7c1d32'] as const;
const chip = (index: number): string => CHIPS[index % CHIPS.length];

/** Config gives the number, the translation file gives the words for it. */
const figureName = (key: string): string => text(`figures.items.${key}.name`);
const figureNote = (key: string): string => text(`figures.items.${key}.note`);
const priceName = (key: string): string => text(`prices.items.${key}.name`);
const priceNote = (key: string): string => text(`prices.items.${key}.note`);

const failings = computed<Row[]>(
    () => group<Row[]>('life_now.broken.items') ?? [],
);

/** 2026-09-20 reads as a date in every language this site speaks. */
const asDate = (iso: string): string => iso;

const reasons = computed(() =>
    [
        'safety',
        'health',
        'tech',
        'together',
        'favour',
        'work',
        'school',
        'digital',
    ].map((key, index) => ({ key, accent: chip(index) })),
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
                        /images/tlo-now-1000.avif 1000w,
                        /images/tlo-now-1400.avif 1400w
                    "
                    sizes="100vw"
                />
                <img
                    src="/images/tlo-now-1000.jpg"
                    srcset="
                        /images/tlo-now-1000.jpg 1000w,
                        /images/tlo-now-1400.jpg 1400w
                    "
                    sizes="100vw"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover opacity-60"
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

        <!-- The author, speaking for himself -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <div class="border-l-[4px] border-[#b32347] pl-6 sm:pl-8">
                    <p
                        class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                    >
                        {{ text('author.label') }}
                    </p>
                    <h2
                        class="font-display mt-3 max-w-[22ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                    >
                        {{ text('author.title') }}
                    </h2>
                    <p
                        class="mt-5 max-w-[62ch] text-[1.0625rem] leading-relaxed text-[#3b352d]"
                    >
                        {{ text('author.body_1') }}
                    </p>
                    <p class="mt-4 max-w-[62ch] leading-relaxed text-[#6e6459]">
                        {{ text('author.body_2') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Safety, with the number that makes the point -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <div
                    class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:gap-14"
                >
                    <div>
                        <p
                            class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                        >
                            {{ text('safety.label') }}
                        </p>
                        <h2
                            class="font-display mt-3 max-w-[22ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                        >
                            {{ text('safety.title') }}
                        </h2>
                        <p
                            class="mt-5 max-w-[62ch] text-[1.0625rem] leading-relaxed text-[#3b352d]"
                        >
                            {{ text('safety.body_1') }}
                        </p>
                        <p
                            class="mt-4 max-w-[62ch] leading-relaxed text-[#6e6459]"
                        >
                            {{ text('safety.body_2') }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg p-6"
                        :style="{ backgroundColor: chip(0) }"
                    >
                        <p
                            class="font-display text-[3.2rem] leading-none text-white tabular-nums"
                        >
                            {{ text('safety.stat_value') }}
                        </p>
                        <p
                            class="mt-3 text-[0.6875rem] font-semibold tracking-[0.18em] text-white uppercase"
                        >
                            {{ text('safety.stat_unit') }}
                        </p>
                        <p
                            class="mt-3 text-[0.9375rem] leading-relaxed text-white"
                        >
                            {{ text('safety.stat_note') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Health, both halves of it -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('health.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('health.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('health.body_1') }}
                    </p>
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('health.body_2') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- The wallet that stayed at home -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                >
                    {{ text('tech.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[24ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('tech.title') }}
                </h2>
                <p
                    class="mt-5 max-w-[68ch] text-[1.0625rem] leading-relaxed text-[#3b352d]"
                >
                    {{ text('tech.body_1') }}
                </p>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('tech.body_2') }}
                </p>
            </div>
        </section>
        <!-- The state in a phone -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('digital.label') }}
                </p>
                <h2
                    class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('digital.title') }}
                </h2>
                <p
                    class="mt-5 max-w-[68ch] text-[1.0625rem] leading-relaxed text-[#3b352d]"
                >
                    {{ text('digital.body_1') }}
                </p>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('digital.body_2') }}
                </p>

                <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(service, index) in services"
                        :key="service.name"
                        class="rounded-lg border-l-[4px] bg-white p-4 shadow-[0_1px_2px_rgba(34,30,25,.06)]"
                        :style="{ borderLeftColor: chip(index) }"
                    >
                        <dt
                            class="font-display text-sm tracking-wide uppercase"
                            :style="{ color: chip(index) }"
                        >
                            {{ service.name }}
                        </dt>
                        <dd
                            class="m-0 mt-1.5 text-[0.9375rem] leading-relaxed text-[#4a443c]"
                        >
                            {{ service.note }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- The one that surprises people most -->
        <section class="bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('together.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#fdf9f1] uppercase"
                >
                    {{ text('together.title') }}
                </h2>

                <div
                    class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,20rem)] lg:gap-14"
                >
                    <div>
                        <p class="max-w-[64ch] leading-relaxed text-[#b0a795]">
                            {{ text('together.body_1') }}
                        </p>
                        <p
                            class="mt-4 max-w-[64ch] text-[1.0625rem] leading-relaxed text-[#e6dccb]"
                        >
                            {{ text('together.body_2') }}
                        </p>
                        <p
                            class="mt-4 max-w-[64ch] text-[1.0625rem] leading-relaxed text-[#e6dccb]"
                        >
                            {{ text('together.body_3') }}
                        </p>
                        <p
                            class="mt-4 max-w-[64ch] text-[1.0625rem] leading-relaxed text-[#e6dccb]"
                        >
                            {{ text('together.body_4') }}
                        </p>
                        <p
                            class="mt-4 max-w-[64ch] text-[1.0625rem] leading-relaxed text-[#e6dccb]"
                        >
                            {{ text('together.body_5') }}
                        </p>
                    </div>

                    <div
                        class="self-start rounded-lg border border-white/10 bg-white/[0.05] p-6"
                    >
                        <p
                            class="font-display text-[2.6rem] leading-none text-[#f5b24f] tabular-nums"
                        >
                            280 mln
                        </p>
                        <p
                            class="mt-3 text-[0.6875rem] font-semibold tracking-[0.18em] text-[#f5b24f] uppercase"
                        >
                            zł
                        </p>
                        <p
                            class="mt-3 text-[0.9375rem] leading-relaxed text-[#cfc6b6]"
                        >
                            Cancer Fighters &middot; 2026 &middot; Guinness
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- The bottle -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#8a5a10] uppercase"
                >
                    {{ text('favour.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[24ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('favour.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('favour.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('favour.body_2') }}
                    </p>
                </div>
                <h3
                    class="font-display mt-12 max-w-[28ch] text-[clamp(1.25rem,2.4vw,1.75rem)] leading-tight tracking-tight text-[#221e19]"
                >
                    {{ text('favour.money_title') }}
                </h3>
                <div class="mt-5 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('favour.body_3') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('favour.body_4') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Work, and school -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p
                            class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#7c1d32] uppercase"
                        >
                            {{ text('work.label') }}
                        </p>
                        <h2
                            class="font-display mt-3 text-[clamp(1.5rem,3vw,2.2rem)] leading-[1.1] tracking-tight text-[#221e19] uppercase"
                        >
                            {{ text('work.title') }}
                        </h2>
                        <p class="mt-4 leading-relaxed text-[#3b352d]">
                            {{ text('work.body_1') }}
                        </p>
                        <p class="mt-3 leading-relaxed text-[#6e6459]">
                            {{ text('work.body_2') }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                        >
                            {{ text('school.label') }}
                        </p>
                        <h2
                            class="font-display mt-3 text-[clamp(1.5rem,3vw,2.2rem)] leading-[1.1] tracking-tight text-[#221e19] uppercase"
                        >
                            {{ text('school.title') }}
                        </h2>
                        <p class="mt-4 leading-relaxed text-[#3b352d]">
                            {{ text('school.body_1') }}
                        </p>
                        <p class="mt-3 leading-relaxed text-[#6e6459]">
                            {{ text('school.body_2') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- The half a leaflet would leave out -->
        <section class="bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('broken.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#f4ede0] uppercase"
                >
                    {{ text('broken.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#cabfad]">
                    {{ text('broken.note') }}
                </p>

                <dl class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="failing in failings"
                        :key="failing.name"
                        class="border-t border-white/15 pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#f4ede0]"
                        >
                            {{ failing.name }}
                        </dt>
                        <dd
                            class="m-0 mt-2 text-[0.9375rem] leading-relaxed text-[#cabfad]"
                        >
                            {{ failing.note }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- The numbers, each with the day it was looked at -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                >
                    {{ text('figures.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('figures.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('figures.note') }}
                </p>

                <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(figure, index) in props.figures"
                        :key="figure.key"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index) }"
                    >
                        <dt
                            class="font-display text-[2rem] leading-none text-white tabular-nums"
                        >
                            {{ figure.value }}
                        </dt>
                        <dd
                            class="m-0 mt-2.5 text-[0.6875rem] font-semibold tracking-[0.18em] text-white uppercase"
                        >
                            {{ figureName(figure.key) }}
                        </dd>
                        <dd
                            class="m-0 mt-2 text-[0.875rem] leading-relaxed text-white"
                        >
                            {{ figureNote(figure.key) }}
                        </dd>
                        <dd
                            class="m-0 mt-3 text-[0.625rem] tracking-[0.16em] text-white/95 uppercase"
                        >
                            <template v-if="figure.live">
                                {{ text('figures.live_label') }} &middot;
                                {{ text('figures.reading_label') }}
                                {{ figure.year }}
                            </template>
                            <template v-else>
                                {{ text('figures.checked_label') }}
                                {{ asDate(figure.checked) }}
                            </template>
                        </dd>
                    </div>
                </dl>

                <div
                    class="mt-8 rounded-lg border border-[#e0d3bd] bg-white p-5"
                >
                    <div class="flex flex-wrap items-baseline gap-3">
                        <h3
                            class="font-display text-sm tracking-wide text-[#221e19] uppercase"
                        >
                            {{ text('figures.rates_label') }}
                        </h3>
                        <span
                            class="rounded-full bg-[#457036] px-2.5 py-1 text-[0.625rem] font-semibold tracking-[0.16em] text-white uppercase"
                        >
                            {{ text('figures.live_label') }}
                        </span>
                    </div>

                    <div
                        v-if="props.rates.length"
                        class="mt-4 flex flex-wrap gap-x-10 gap-y-4"
                    >
                        <p
                            v-for="rate in props.rates"
                            :key="rate.code"
                            class="m-0"
                        >
                            <span
                                class="font-display text-[1.7rem] leading-none text-[#221e19] tabular-nums"
                            >
                                {{ rate.rate }}
                            </span>
                            <span
                                class="ml-2 text-[0.75rem] tracking-[0.16em] text-[#6e6459] uppercase"
                            >
                                zł / 1 {{ rate.code }}
                            </span>
                        </p>
                    </div>
                    <p v-else class="mt-3 text-[0.9375rem] text-[#6e6459]">
                        {{ text('figures.rates_missing') }}
                    </p>

                    <p
                        v-if="props.rates.length"
                        class="mt-4 text-[0.8125rem] leading-relaxed text-[#6e6459]"
                    >
                        {{ text('figures.rates_note') }}
                        {{ props.rates[0].date }}
                    </p>
                </div>
            </div>
        </section>

        <!-- What a day costs, which is the question people actually ask -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('prices.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('prices.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('prices.note') }}
                </p>

                <dl
                    class="mt-8 grid gap-px overflow-hidden rounded-lg bg-[#e0d3bd]"
                >
                    <div
                        v-for="price in props.prices"
                        :key="price.key"
                        class="grid gap-x-6 gap-y-1 bg-white px-5 py-4 sm:grid-cols-[1fr_auto] sm:items-baseline"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ priceName(price.key) }}
                        </dt>
                        <dd
                            class="font-display order-first m-0 text-[1.375rem] leading-none whitespace-nowrap text-[#b32347] tabular-nums sm:order-none sm:col-start-2 sm:row-span-2"
                        >
                            {{ price.value }}
                        </dd>
                        <dd
                            class="m-0 max-w-[62ch] text-[0.875rem] leading-relaxed text-[#6e6459] sm:col-start-1"
                        >
                            {{ priceNote(price.key) }}
                        </dd>
                    </div>
                </dl>

                <p
                    class="mt-4 text-[0.6875rem] tracking-[0.16em] text-[#6e6459] uppercase"
                >
                    {{ text('figures.checked_label') }}
                    {{ asDate(props.prices[0]?.checked ?? '') }}
                </p>
            </div>
        </section>

        <!-- Every app named above, with its address -->
        <UsefulLinks tone="plain" />

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
