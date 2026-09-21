<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { group, t } from '@/i18n';

/**
 * Tracing a Polish family from abroad.
 *
 * The reader here is not a visitor with a suitcase, they are somebody whose
 * great-grandfather left and who now holds a misspelt surname, a half-heard
 * village name and a year. The page answers the four questions that stop
 * such a search dead - why the town is in another country now, why the name
 * looks wrong, where the paper actually is, and what language it is in - and
 * then says plainly what citizenship by descent is and is not.
 */
type Pair = { k: string; v: string };
/** An archive or index, with its address written as plain text in the copy. */
type Place = { k: string; v: string; web?: string };
/** One line of the record vocabulary table, in the three clerical languages. */
type Word = { en: string; la: string; de: string; ru: string };

const text = (path: string): string => t(`roots.${path}`);
const rows = (path: string): Pair[] => group<Pair[]>(`roots.${path}`) ?? [];

/** Beetroot, crust, dill, honey, wine, cycled so a block keeps its colour. */
const CHIPS = ['#b32347', '#a8520f', '#457036', '#8a5a10', '#7c1d32'] as const;
const chip = (index: number): string => CHIPS[index % CHIPS.length];

const carry = computed(() => rows('start.items'));
const changes = computed(() => rows('name.items'));
const partitions = computed(() => rows('language.items'));
const manners = computed(() => rows('ground.items'));
const papers = computed(() => group<Place[]>('roots.records.items') ?? []);
const words = computed(() => group<Word[]>('roots.words.items') ?? []);
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
                        /images/roots-hero-sm.avif  960w,
                        /images/roots-hero-lg.avif 1280w
                    "
                    sizes="100vw"
                />
                <img
                    src="/images/roots-hero-sm.jpg"
                    srcset="
                        /images/roots-hero-sm.jpg  960w,
                        /images/roots-hero-lg.jpg 1280w
                    "
                    sizes="100vw"
                    :alt="text('hero.photo_alt')"
                    class="absolute inset-0 h-full w-full object-cover opacity-45"
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
                <p class="mt-6 max-w-[56ch] text-xs text-[#9c9184]">
                    {{ text('hero.photo_caption') }}
                    <span class="block">{{ text('hero.photo_credit') }}</span>
                </p>
            </div>
        </section>

        <!-- What you need in your hand before any of this works -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                >
                    {{ text('start.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('start.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('start.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('start.body_2') }}
                    </p>
                </div>

                <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(item, index) in carry"
                        :key="item.k"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index) }"
                    >
                        <dt
                            class="font-display text-[1.25rem] leading-none text-white"
                        >
                            {{ item.k }}
                        </dt>
                        <dd
                            class="m-0 mt-3 text-[0.9375rem] leading-relaxed text-white"
                        >
                            {{ item.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Why the village is in another country now -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('borders.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('borders.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('borders.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('borders.body_2') }}
                    </p>
                </div>

                <Link
                    href="/history/the-borders-moved"
                    class="mt-4 inline-flex min-h-[44px] items-center text-[0.6875rem] tracking-[0.18em] text-[#b32347] uppercase transition hover:text-[#7c1d32] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#b32347]"
                >
                    <span class="border-b border-[#b32347] pb-1">
                        {{ text('borders.link') }} &rarr;
                    </span>
                </Link>
            </div>
        </section>

        <!-- The surname, and the myth that comes attached to it -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#7c1d32] uppercase"
                >
                    {{ text('name.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('name.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('name.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('name.body_2') }}
                    </p>
                </div>

                <!-- The myth gets its own plate, because the reader arrives holding it -->
                <div class="mt-8 border-l-2 border-[#b32347] pl-5 sm:pl-6">
                    <h3
                        class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                    >
                        {{ text('name.myth_title') }}
                    </h3>
                    <p
                        class="mt-2 max-w-[70ch] text-[1.0625rem] leading-relaxed text-[#3b352d]"
                    >
                        {{ text('name.myth_body_1') }}
                    </p>
                    <p class="mt-3 max-w-[70ch] leading-relaxed text-[#6e6459]">
                        {{ text('name.myth_body_2') }}
                    </p>
                </div>

                <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(item, index) in changes"
                        :key="item.k"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index + 1) }"
                    >
                        <dt
                            class="font-display text-[1.25rem] leading-none text-white"
                        >
                            {{ item.k }}
                        </dt>
                        <dd
                            class="m-0 mt-3 text-[0.9375rem] leading-relaxed text-white"
                        >
                            {{ item.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Where the paper is -->
        <section class="bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('records.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#f4ede0] uppercase"
                >
                    {{ text('records.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#cabfad]">
                    {{ text('records.note') }}
                </p>

                <dl class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="place in papers"
                        :key="place.k"
                        class="border-t border-white/15 pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#f4ede0]"
                        >
                            {{ place.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 text-[0.9375rem] leading-relaxed text-[#cabfad]"
                        >
                            {{ place.v }}
                        </dd>
                        <dd v-if="place.web" class="m-0">
                            <a
                                :href="`https://${place.web}`"
                                rel="noopener"
                                class="inline-flex min-h-[44px] items-center text-[0.8125rem] break-all text-[#f5b24f] transition hover:text-[#fdf9f1] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f5b24f]"
                            >
                                {{ place.web }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- What language the record is in, and the words in it -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('language.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('language.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('language.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('language.body_2') }}
                    </p>
                </div>

                <dl class="mt-8 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="part in partitions"
                        :key="part.k"
                        class="border-t border-[#d9cbb3] pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ part.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459]"
                        >
                            {{ part.v }}
                        </dd>
                    </div>
                </dl>

                <h3
                    class="mt-12 text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('words.label') }}
                </h3>
                <p class="mt-3 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('words.note') }}
                </p>

                <!--
                    Four columns on a desk, one stacked block per word on a
                    phone, where each language keeps its own small label so the
                    row never has to be read against a header far above it.
                -->
                <div
                    class="mt-6 grid gap-px overflow-hidden rounded-lg bg-[#e0d3bd]"
                >
                    <div
                        class="hidden bg-[#221e19] px-5 py-3 text-[0.6875rem] tracking-[0.2em] text-[#cabfad] uppercase lg:grid lg:grid-cols-4 lg:gap-x-8"
                    >
                        <span>{{ text('words.col_en') }}</span>
                        <span>{{ text('words.col_la') }}</span>
                        <span>{{ text('words.col_de') }}</span>
                        <span>{{ text('words.col_ru') }}</span>
                    </div>
                    <div
                        v-for="word in words"
                        :key="word.en"
                        class="grid gap-x-8 gap-y-2 bg-white px-5 py-4 lg:grid-cols-4"
                    >
                        <p
                            class="font-display m-0 text-[1.0625rem] leading-snug text-[#221e19]"
                        >
                            {{ word.en }}
                        </p>
                        <p class="m-0 text-[0.9375rem] text-[#6e6459]">
                            <span
                                class="mr-2 text-[0.6875rem] tracking-[0.18em] text-[#a8520f] uppercase lg:hidden"
                                >{{ text('words.col_la') }}</span
                            >{{ word.la }}
                        </p>
                        <p class="m-0 text-[0.9375rem] text-[#6e6459]">
                            <span
                                class="mr-2 text-[0.6875rem] tracking-[0.18em] text-[#a8520f] uppercase lg:hidden"
                                >{{ text('words.col_de') }}</span
                            >{{ word.de }}
                        </p>
                        <p class="m-0 text-[0.9375rem] text-[#6e6459]">
                            <span
                                class="mr-2 text-[0.6875rem] tracking-[0.18em] text-[#a8520f] uppercase lg:hidden"
                                >{{ text('words.col_ru') }}</span
                            >{{ word.ru }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Citizenship, which is where hope outruns the law -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                >
                    {{ text('citizenship.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('citizenship.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('citizenship.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('citizenship.body_2') }}
                    </p>
                </div>

                <dl class="mt-8 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="item in rows('citizenship.items')"
                        :key="item.k"
                        class="border-t border-[#d9cbb3] pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ item.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459]"
                        >
                            {{ item.v }}
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
                    {{ text('citizenship.disclaimer') }}
                </p>
            </div>
        </section>

        <!-- Standing in the place itself -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#7c1d32] uppercase"
                >
                    {{ text('ground.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('ground.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('ground.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('ground.body_2') }}
                    </p>
                </div>

                <dl class="mt-8 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="item in manners"
                        :key="item.k"
                        class="border-l-2 border-[#7c1d32] pl-5"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#221e19]"
                        >
                            {{ item.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#6e6459]"
                        >
                            {{ item.v }}
                        </dd>
                    </div>
                </dl>

                <p
                    class="mt-8 max-w-[70ch] text-[1.0625rem] leading-relaxed text-[#3b352d]"
                >
                    {{ text('ground.close') }}
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
