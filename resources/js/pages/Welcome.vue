<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import PolishEmblem from '@/components/PolishEmblem.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import SectionBackdrop from '@/components/SectionBackdrop.vue';
import { group, t } from '@/i18n';

/**
 * The home page is the table of contents for the site: one full screen per
 * area, in reading order. Order and artwork live here, every word lives in
 * lang/<locale>/site.php, so adding a language never touches this file.
 */
const sections = [
    {
        key: 'history',
        href: '/history',
        layout: 'poster',
        photo: 'history',
        photoPosition: '50% 50%',
        wash: 'radial-gradient(64% 54% at 26% 22%, rgba(226,203,142,.24) 0%, rgba(226,203,142,0) 62%), linear-gradient(190deg, #241f18 0%, #12100c 78%)',
    },
    {
        key: 'places',
        href: '/places',
        layout: 'split',
        photo: 'places',
        photoSide: 'left',
        wash: 'linear-gradient(190deg, #17201f 0%, #12100c 76%)',
    },
    {
        key: 'food',
        href: '/food',
        layout: 'poster',
        photo: 'food',
        photoPosition: '50% 50%',
        wash: 'radial-gradient(64% 54% at 30% 24%, rgba(226,150,80,.26) 0%, rgba(226,150,80,0) 62%), linear-gradient(190deg, #2a1e14 0%, #12100c 76%)',
    },
    {
        key: 'life',
        href: '/everyday-life',
        layout: 'poster',
        photo: 'life',
        photoPosition: '50% 50%',
        wash: 'radial-gradient(64% 54% at 70% 22%, rgba(150,190,150,.22) 0%, rgba(150,190,150,0) 62%), linear-gradient(190deg, #1b241c 0%, #12100c 78%)',
    },
    {
        key: 'memory',
        href: '/history/second-world-war',
        layout: 'poster',
        photo: 'memory',
        photoPosition: '50% 60%',
        wash: 'radial-gradient(60% 50% at 26% 28%, rgba(132,140,152,.16) 0%, rgba(132,140,152,0) 58%), linear-gradient(190deg, #171a1f 0%, #0b0c0e 80%)',
    },
    {
        key: 'now',
        href: '/poland-today',
        layout: 'split',
        photo: 'now',
        photoSide: 'right',
        wash: 'linear-gradient(190deg, #221a14 0%, #12100c 76%)',
    },
] as const;

type SectionItem = { title: string; note: string; href?: string };

const itemsFor = (key: string): SectionItem[] =>
    group<SectionItem[]>(`home.sections.${key}.items`) ?? [];

/** The donation address is shared by the server, so it is written once. */
const supportUrl = computed(
    () => (usePage().props.supportUrl as string | undefined) ?? '#',
);

const openingPlate =
    'radial-gradient(70% 52% at 80% 18%, rgba(220,20,60,.20) 0%, rgba(220,20,60,0) 60%), linear-gradient(196deg, #1c2029 0%, #0d1014 74%)';
const closingPlate =
    'radial-gradient(80% 60% at 50% 20%, rgba(240,162,60,.18) 0%, rgba(240,162,60,0) 62%), linear-gradient(196deg, #14181d 0%, #07080a 72%)';

/**
 * The three cards that close the page. A card with an address goes to its
 * page through the Inertia router, one still waiting for its page stays an
 * ordinary anchor, so the row keeps working while the site is filled in.
 */
const closingCards: { key: string; href?: string }[] = [
    { key: 'timeline', href: '/start-here' },
    { key: 'everyday', href: '/everyday-life' },
    { key: 'practical', href: '/polish-roots' },
];

const sectionIds = [
    'opening',
    ...sections.map((section) => section.key),
    'closing',
];
const sectionLabels = [
    'home.opening.label',
    ...sections.map((section) => `home.sections.${section.key}.label`),
    'home.closing.label',
];

const activeSection = ref<string>(sectionIds[0]);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.id;
                }
            });
        },
        { threshold: 0.5 },
    );

    sectionIds.forEach((id) => {
        const element = document.getElementById(id);

        if (element) {
            observer?.observe(element);
        }
    });
});

onBeforeUnmount(() => {
    observer?.disconnect();
    observer = null;
});
</script>

<template>
    <Head :title="t('home.meta.title')">
        <meta
            head-key="description"
            name="description"
            :content="t('home.meta.description')"
        />
    </Head>

    <a
        href="#opening"
        class="focus:bg-bone focus:text-ink sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-full focus:px-5 focus:py-2"
    >
        {{ t('nav.skip_to_content') }}
    </a>

    <SiteHeader with-section-nav />

    <nav
        class="fixed top-1/2 right-3 z-40 hidden -translate-y-1/2 flex-col gap-3.5 md:flex"
        :aria-label="t('a11y.chapter_nav')"
    >
        <a
            v-for="(id, index) in sectionIds"
            :key="id"
            :href="`#${id}`"
            :aria-label="t('a11y.go_to', { chapter: t(sectionLabels[index]) })"
            :aria-current="activeSection === id ? 'true' : undefined"
            class="border-bone-muted focus-visible:outline-amber size-2.5 rounded-full border transition focus-visible:outline-2 focus-visible:outline-offset-4"
            :class="
                activeSection === id
                    ? 'border-amber bg-amber scale-[1.35]'
                    : 'hover:border-bone'
            "
        />
    </nav>

    <main>
        <section
            id="opening"
            class="relative flex min-h-dvh snap-start items-center overflow-hidden"
        >
            <SectionBackdrop
                :wash="openingPlate"
                photo="tatry"
                :photo-opacity="62"
                :photo-blur="false"
                photo-mask="bottom"
                outline="right"
                eager
            />

            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 z-10"
                aria-hidden="true"
            >
                <div class="bg-flag-white h-2" />
                <div class="bg-flag-red h-2" />
            </div>

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 pt-28 pb-24"
            >
                <div class="flex items-center gap-4">
                    <PolishEmblem
                        class="h-16 w-auto sm:h-20"
                        :label="t('home.opening.emblem_alt')"
                    />
                    <div>
                        <p
                            class="text-amber text-[0.625rem] tracking-[0.18em] uppercase sm:text-xs sm:tracking-[0.34em]"
                        >
                            {{ t('home.opening.kicker') }}
                        </p>
                        <p
                            class="text-bone/70 mt-1.5 text-[0.6875rem] tracking-[0.24em] uppercase"
                        >
                            {{ t('home.opening.author_label') }}
                        </p>
                    </div>
                </div>
                <h1
                    class="font-display mt-6 text-[clamp(2.25rem,7.2vw,6.25rem)] leading-[1.06] tracking-tight uppercase"
                >
                    <span class="text-flag-white">{{
                        t('home.opening.title_line_1')
                    }}</span
                    ><br />
                    <span class="text-outline">{{
                        t('home.opening.title_line_2')
                    }}</span
                    ><br />
                    <span class="text-flag-white">{{
                        t('home.opening.title_line_3')
                    }}</span>
                </h1>

                <p
                    class="text-bone-muted mt-7 max-w-[48ch] text-base sm:text-lg"
                >
                    {{ t('home.opening.lede') }}
                </p>
                <a
                    href="#history"
                    class="border-hairline hover:border-amber hover:bg-amber/10 focus-visible:outline-amber mt-8 inline-flex items-center gap-3.5 rounded-full border px-6 py-3.5 text-xs tracking-[0.22em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                >
                    {{ t('home.opening.start') }}
                    <span
                        class="border-amber size-2 rotate-45 border-r-2 border-b-2"
                    />
                </a>

                <p
                    class="text-bone/85 mt-8 flex max-w-[46ch] gap-4 text-[0.9375rem] leading-relaxed"
                >
                    <span
                        class="flex w-1 shrink-0 flex-col overflow-hidden rounded-full"
                        aria-hidden="true"
                    >
                        <span class="bg-flag-white flex-1" />
                        <span class="bg-flag-red flex-1" />
                    </span>
                    {{ t('home.opening.author_note') }}
                </p>
            </div>
        </section>

        <section
            v-for="section in sections"
            :id="section.key"
            :key="section.key"
            class="relative flex min-h-dvh snap-start items-center overflow-hidden"
        >
            <!-- Split screen: the photograph carries the section, edge to edge. -->
            <template v-if="section.layout === 'split'">
                <div
                    class="grid w-full lg:min-h-dvh lg:grid-cols-2"
                    :class="
                        section.photoSide === 'right'
                            ? 'lg:[direction:rtl]'
                            : ''
                    "
                >
                    <div
                        class="relative min-h-[46vh] overflow-hidden lg:min-h-dvh"
                    >
                        <picture>
                            <source
                                type="image/avif"
                                :srcset="`/images/tlo-${section.photo}-1000.avif 1000w, /images/tlo-${section.photo}-1400.avif 1400w`"
                                sizes="(min-width: 1024px) 50vw, 100vw"
                            />
                            <img
                                :src="`/images/tlo-${section.photo}-1400.jpg`"
                                :srcset="`/images/tlo-${section.photo}-1000.jpg 1000w, /images/tlo-${section.photo}-1400.jpg 1400w`"
                                sizes="(min-width: 1024px) 50vw, 100vw"
                                :alt="
                                    t(
                                        `home.sections.${section.key}.photo_caption`,
                                    )
                                "
                                class="absolute inset-0 h-full w-full object-cover"
                                loading="lazy"
                                decoding="async"
                            />
                        </picture>
                        <div
                            class="from-ink/70 absolute inset-0 bg-linear-to-b to-transparent lg:bg-linear-to-r lg:via-transparent lg:to-transparent"
                        />
                    </div>

                    <div
                        class="border-hairline flex flex-col justify-center px-6 pt-24 pb-16 [direction:ltr] sm:px-10 lg:border-l lg:pt-28 lg:pb-24 lg:pl-14"
                    >
                        <p
                            class="text-bone-muted flex items-center gap-3 text-[0.6875rem] tracking-[0.28em] tabular-nums"
                        >
                            <span
                                class="flex h-3.5 w-[3px] shrink-0 flex-col overflow-hidden rounded-full"
                                aria-hidden="true"
                            >
                                <span class="bg-flag-white flex-1" />
                                <span class="bg-flag-red flex-1" />
                            </span>
                            {{ t(`home.sections.${section.key}.index`) }}
                        </p>

                        <h2
                            class="font-display text-bone mt-3 text-[clamp(2rem,3.6vw,3.25rem)] leading-[1] tracking-tight uppercase"
                        >
                            {{ t(`home.sections.${section.key}.eyebrow`) }}
                        </h2>

                        <p
                            class="text-amber mt-3 max-w-[26ch] text-[clamp(1.0625rem,1.5vw,1.375rem)] leading-snug font-semibold"
                        >
                            {{ t(`home.sections.${section.key}.title`) }}
                            {{ t(`home.sections.${section.key}.title_accent`) }}
                        </p>

                        <p class="text-bone-muted mt-5 max-w-[46ch]">
                            {{ t(`home.sections.${section.key}.lede`) }}
                        </p>

                        <ul class="mt-8 grid max-w-[52ch]">
                            <li
                                v-for="item in itemsFor(section.key)"
                                :key="item.title"
                                class="border-hairline border-t"
                            >
                                <component
                                    :is="item.href ? Link : 'div'"
                                    v-bind="
                                        item.href ? { href: item.href } : {}
                                    "
                                    class="group/item focus-visible:outline-amber grid gap-1 py-3.5 transition-colors sm:grid-cols-[9rem_1fr] sm:gap-6"
                                    :class="
                                        item.href
                                            ? 'hover:bg-bone/[0.03] focus-visible:outline-2 focus-visible:outline-offset-2'
                                            : ''
                                    "
                                >
                                    <span
                                        class="font-display text-bone group-hover/item:text-amber text-sm tracking-wide uppercase transition-colors"
                                    >
                                        {{ item.title }}
                                        <span
                                            v-if="item.href"
                                            aria-hidden="true"
                                            class="text-amber-deep group-hover/item:text-amber"
                                            >&rarr;</span
                                        >
                                    </span>
                                    <span
                                        class="text-bone-muted text-[0.9375rem]"
                                    >
                                        {{ item.note }}
                                    </span>
                                </component>
                            </li>
                        </ul>

                        <div class="mt-8 flex flex-wrap items-center gap-6">
                            <component
                                :is="'href' in section ? Link : 'a'"
                                :href="'href' in section ? section.href : '#'"
                                class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber inline-flex items-center gap-2.5 border-b pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                            >
                                {{ t(`home.sections.${section.key}.link`) }}
                                &rarr;
                            </component>
                            <p
                                class="text-bone-muted/80 text-[0.625rem] tracking-[0.14em] uppercase"
                            >
                                {{
                                    t(
                                        `home.sections.${section.key}.photo_caption`,
                                    )
                                }}
                                &middot;
                                {{
                                    t(
                                        `home.sections.${section.key}.photo_credit`,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Poster: a portrait plate, a fact card, and the type beside it. -->
            <template v-else>
                <SectionBackdrop :wash="section.wash" />

                <div
                    class="relative mx-auto w-full max-w-[1200px] px-6 pt-24 pb-16 sm:pt-28"
                >
                    <div
                        class="grid items-center gap-10 lg:grid-cols-[1.08fr_0.92fr] lg:gap-16"
                    >
                        <div>
                            <p
                                class="text-bone-muted flex items-center gap-3 text-[0.6875rem] tracking-[0.28em] tabular-nums"
                            >
                                <span
                                    class="flex h-3.5 w-[3px] shrink-0 flex-col overflow-hidden rounded-full"
                                    aria-hidden="true"
                                >
                                    <span class="bg-flag-white flex-1" />
                                    <span class="bg-flag-red flex-1" />
                                </span>
                                {{ t(`home.sections.${section.key}.index`) }}
                            </p>

                            <h2
                                class="font-display text-bone mt-3 text-[clamp(2.25rem,4.8vw,4rem)] leading-[0.98] tracking-tight uppercase"
                            >
                                {{ t(`home.sections.${section.key}.eyebrow`) }}
                            </h2>

                            <p
                                class="text-amber mt-3 max-w-[28ch] text-[clamp(1.125rem,1.7vw,1.5rem)] leading-snug font-semibold"
                            >
                                {{ t(`home.sections.${section.key}.title`) }}
                                {{
                                    t(
                                        `home.sections.${section.key}.title_accent`,
                                    )
                                }}
                            </p>

                            <p
                                class="text-bone-muted mt-5 max-w-[46ch] text-lg"
                            >
                                {{ t(`home.sections.${section.key}.lede`) }}
                            </p>

                            <ul class="mt-6 grid max-w-[50ch]">
                                <li
                                    v-for="item in itemsFor(section.key)"
                                    :key="item.title"
                                    class="border-hairline border-t"
                                >
                                    <component
                                        :is="item.href ? Link : 'div'"
                                        v-bind="
                                            item.href ? { href: item.href } : {}
                                        "
                                        class="group/item focus-visible:outline-amber grid gap-1 py-2.5 transition-colors sm:grid-cols-[8rem_1fr] sm:gap-6 sm:py-3"
                                        :class="
                                            item.href
                                                ? 'hover:bg-bone/[0.03] focus-visible:outline-2 focus-visible:outline-offset-2'
                                                : ''
                                        "
                                    >
                                        <span
                                            class="font-display text-bone group-hover/item:text-amber text-sm tracking-wide uppercase transition-colors"
                                        >
                                            {{ item.title }}
                                            <span
                                                v-if="item.href"
                                                aria-hidden="true"
                                                class="text-amber-deep group-hover/item:text-amber"
                                                >&rarr;</span
                                            >
                                        </span>
                                        <span
                                            class="text-bone-muted text-[0.875rem] sm:text-[0.9375rem]"
                                        >
                                            {{ item.note }}
                                        </span>
                                    </component>
                                </li>
                            </ul>

                            <component
                                :is="'href' in section ? Link : 'a'"
                                :href="'href' in section ? section.href : '#'"
                                class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber mt-6 inline-flex items-center gap-2.5 border-b pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                            >
                                {{ t(`home.sections.${section.key}.link`) }}
                                &rarr;
                            </component>
                        </div>

                        <figure class="group relative">
                            <div
                                class="border-hairline bg-ink-raised overflow-hidden border shadow-[0_28px_70px_-24px_rgba(0,0,0,.95)]"
                            >
                                <picture>
                                    <source
                                        type="image/avif"
                                        :srcset="`/images/tlo-${section.photo}-1000.avif 1000w, /images/tlo-${section.photo}-1400.avif 1400w`"
                                        sizes="(min-width: 1024px) 45vw, 100vw"
                                    />
                                    <img
                                        :src="`/images/tlo-${section.photo}-1400.jpg`"
                                        :srcset="`/images/tlo-${section.photo}-1000.jpg 1000w, /images/tlo-${section.photo}-1400.jpg 1400w`"
                                        sizes="(min-width: 1024px) 45vw, 100vw"
                                        :alt="
                                            t(
                                                `home.sections.${section.key}.photo_caption`,
                                            )
                                        "
                                        class="aspect-[16/11] w-full object-cover transition duration-700 ease-out group-hover:scale-[1.04] lg:aspect-[5/6]"
                                        :style="{
                                            objectPosition:
                                                section.photoPosition,
                                        }"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                </picture>
                            </div>

                            <figcaption
                                class="text-bone-muted/80 mt-2.5 text-[0.625rem] tracking-[0.14em] uppercase lg:absolute lg:top-6 lg:-right-4 lg:mt-0 lg:[writing-mode:vertical-rl]"
                            >
                                {{
                                    t(
                                        `home.sections.${section.key}.photo_caption`,
                                    )
                                }}
                                &middot;
                                {{
                                    t(
                                        `home.sections.${section.key}.photo_credit`,
                                    )
                                }}
                            </figcaption>

                            <div
                                class="border-hairline bg-ink/95 mt-3 border p-4 lg:absolute lg:-bottom-8 lg:-left-8 lg:mt-0 lg:w-[62%] lg:p-5 lg:backdrop-blur-sm"
                            >
                                <p
                                    class="font-display text-flag-red text-[clamp(1.75rem,3vw,2.75rem)] leading-none"
                                >
                                    {{
                                        t(
                                            `home.sections.${section.key}.fact_value`,
                                        )
                                    }}
                                </p>
                                <p class="text-bone-muted mt-2 text-[0.875rem]">
                                    {{
                                        t(
                                            `home.sections.${section.key}.fact_label`,
                                        )
                                    }}
                                </p>
                            </div>
                        </figure>
                    </div>
                </div>
            </template>
        </section>

        <section
            id="closing"
            class="relative flex min-h-dvh snap-start items-center overflow-hidden"
        >
            <SectionBackdrop
                :wash="closingPlate"
                photo="tatry"
                :photo-opacity="30"
                :photo-blur="false"
                photo-mask="bottom"
            />

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 pt-28 pb-16"
            >
                <div class="flex items-center gap-3.5">
                    <span
                        class="flex h-6 w-[3px] shrink-0 flex-col overflow-hidden rounded-full"
                        aria-hidden="true"
                    >
                        <span class="bg-flag-white flex-1" />
                        <span class="bg-flag-red flex-1" />
                    </span>
                    <p class="text-amber text-xs tracking-[0.34em] uppercase">
                        {{ t('home.closing.kicker') }}
                    </p>
                </div>
                <h2
                    class="font-display mt-4 text-[clamp(2rem,6vw,4.625rem)] leading-[0.92] tracking-tight uppercase"
                >
                    {{ t('home.closing.title_line_1') }}<br />
                    {{ t('home.closing.title_line_2') }}
                </h2>

                <p class="text-bone/80 mt-6 max-w-[58ch] text-lg">
                    {{ t('home.closing.promise') }}
                </p>

                <div class="mt-10 grid gap-5.5 md:grid-cols-3">
                    <component
                        :is="card.href ? Link : 'a'"
                        v-for="card in closingCards"
                        :key="card.key"
                        :href="card.href ?? '#'"
                        class="border-hairline hover:border-amber hover:bg-bone/[0.03] group/card block border-t p-4 pl-0 transition"
                    >
                        <h3
                            class="font-display group-hover/card:text-amber text-lg uppercase transition-colors"
                        >
                            {{ t(`home.closing.cards.${card.key}.title`) }}
                        </h3>
                        <p class="text-bone-muted mt-2 text-sm">
                            {{ t(`home.closing.cards.${card.key}.body`) }}
                        </p>
                    </component>
                </div>

                <div
                    class="border-hairline bg-bone/[0.03] mt-10 flex flex-wrap items-center gap-x-8 gap-y-5 border p-5 sm:p-6"
                >
                    <svg
                        class="text-amber h-10 w-10 shrink-0"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.4"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M4 9h12v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V9Z" />
                        <path d="M16 10h1.6a2.4 2.4 0 0 1 0 4.8H16" />
                        <path
                            d="M7 3.5c-.6.8-.6 1.7 0 2.5M10.5 3.5c-.6.8-.6 1.7 0 2.5M14 3.5c-.6.8-.6 1.7 0 2.5"
                        />
                    </svg>

                    <div class="min-w-[16rem] flex-1">
                        <p
                            class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                        >
                            {{ t('support.label') }}
                        </p>
                        <p
                            class="font-display text-bone mt-1 text-xl uppercase"
                        >
                            {{ t('support.title') }}
                        </p>
                        <p class="text-bone-muted mt-2 max-w-[62ch] text-sm">
                            {{ t('support.body') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <a
                            :href="supportUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="border-amber text-amber hover:bg-amber hover:text-ink focus-visible:outline-amber inline-flex items-center gap-2.5 rounded-full border px-6 py-3 text-xs tracking-[0.2em] whitespace-nowrap uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                        >
                            {{ t('support.action') }}
                        </a>
                        <p
                            class="text-bone-muted/80 text-center text-[0.6875rem]"
                        >
                            {{ t('support.note') }}
                        </p>
                    </div>
                </div>

                <div class="mt-12">
                    <SiteFooter />
                </div>
            </div>
        </section>
    </main>
</template>
