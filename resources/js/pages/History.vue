<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { articleFor } from '@/history-articles';
import { group, t } from '@/i18n';

/**
 * The history section: six eras in order, each holding several topics that
 * will become articles. Order and artwork live here, every word lives in
 * lang/<locale>/site.php.
 */
const eras = [
    {
        key: 'beginnings',
        photo: 'history',
        wash: 'radial-gradient(60% 60% at 20% 20%, rgba(226,203,142,.18) 0%, rgba(226,203,142,0) 62%), linear-gradient(190deg, #241f18 0%, #12100c 76%)',
    },
    {
        key: 'commonwealth',
        photo: null,
        wash: 'radial-gradient(60% 60% at 80% 22%, rgba(206,150,80,.14) 0%, rgba(206,150,80,0) 60%), linear-gradient(190deg, #221c15 0%, #12100c 78%)',
    },
    {
        key: 'partitions',
        photo: null,
        wash: 'radial-gradient(60% 60% at 24% 26%, rgba(132,140,152,.14) 0%, rgba(132,140,152,0) 58%), linear-gradient(190deg, #191c21 0%, #0d0f12 80%)',
    },
    {
        key: 'wars',
        href: '/history/second-world-war',
        photo: 'memory',
        wash: 'radial-gradient(60% 60% at 76% 22%, rgba(176,70,56,.16) 0%, rgba(176,70,56,0) 58%), linear-gradient(190deg, #1e1614 0%, #0d0f12 78%)',
    },
    {
        key: 'communism',
        photo: null,
        wash: 'radial-gradient(60% 60% at 26% 24%, rgba(150,170,180,.12) 0%, rgba(150,170,180,0) 58%), linear-gradient(190deg, #171b1e 0%, #0d0f12 80%)',
    },
    {
        key: 'after',
        photo: 'now',
        wash: 'radial-gradient(60% 60% at 78% 22%, rgba(220,20,60,.14) 0%, rgba(220,20,60,0) 60%), linear-gradient(190deg, #201820 0%, #0f0c10 78%)',
    },
] as const;

type Topic = { title: string; note: string };

const topicsFor = (key: string): Topic[] =>
    group<Topic[]>(`history.eras.${key}.topics`) ?? [];

/** Which topics already have a page lives in one list, with the reading order. */
const hrefFor = (era: string, index: number): string | undefined =>
    articleFor(era, index)?.href;
</script>

<template>
    <Head :title="t('history.meta.title')">
        <meta
            head-key="description"
            name="description"
            :content="t('history.meta.description')"
        />
    </Head>

    <SiteHeader />

    <main class="bg-ink">
        <!-- The era index: the whole thousand years on one screen. -->
        <section class="relative overflow-hidden">
            <div
                class="absolute inset-0"
                style="
                    background:
                        radial-gradient(
                            70% 55% at 78% 18%,
                            rgba(220, 20, 60, 0.16) 0%,
                            rgba(220, 20, 60, 0) 60%
                        ),
                        linear-gradient(196deg, #1c2029 0%, #0d1014 74%);
                "
            />
            <div
                class="absolute inset-0 opacity-50 [background:repeating-linear-gradient(0deg,rgba(0,0,0,.22)_0_1px,rgba(0,0,0,0)_1px_3px)]"
            />

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 pt-32 pb-14 sm:pt-36"
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
                    {{ t('history.hero.kicker') }}
                </p>

                <h1
                    class="font-display text-bone mt-3 text-[clamp(2.5rem,7vw,5.5rem)] leading-[0.95] tracking-tight uppercase"
                >
                    {{ t('history.hero.title') }}
                    <span class="text-amber">{{
                        t('history.hero.title_accent')
                    }}</span>
                </h1>

                <p class="text-bone-muted mt-5 max-w-[56ch] text-lg">
                    {{ t('history.hero.lede') }}
                </p>

                <nav class="mt-9" :aria-label="t('history.hero.jump')">
                    <ul
                        class="border-hairline grid border-t sm:grid-cols-3 lg:grid-cols-6"
                    >
                        <li v-for="era in eras" :key="era.key">
                            <a
                                :href="`#${era.key}`"
                                class="border-hairline hover:bg-bone/[0.04] focus-visible:outline-amber block border-b py-3 transition sm:border-r sm:border-b-0 sm:pr-4 lg:last:border-r-0"
                            >
                                <span
                                    class="text-amber font-display block text-xs tracking-[0.2em]"
                                >
                                    {{ t(`history.eras.${era.key}.index`) }}
                                </span>
                                <span
                                    class="font-display text-bone mt-1 block text-sm tracking-wide uppercase"
                                >
                                    {{ t(`history.eras.${era.key}.label`) }}
                                </span>
                                <span
                                    class="text-bone-muted mt-0.5 block text-xs tabular-nums"
                                >
                                    {{ t(`history.eras.${era.key}.years`) }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </section>

        <!-- One band per era, with its topics underneath. -->
        <section
            v-for="era in eras"
            :id="era.key"
            :key="era.key"
            class="border-hairline relative scroll-mt-44 overflow-hidden border-t sm:scroll-mt-28"
        >
            <div class="absolute inset-0" :style="{ background: era.wash }" />
            <div
                class="absolute inset-0 opacity-50 [background:repeating-linear-gradient(0deg,rgba(0,0,0,.22)_0_1px,rgba(0,0,0,0)_1px_3px)]"
            />

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20"
            >
                <div class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-14">
                    <div>
                        <p
                            class="text-bone-muted flex flex-wrap items-baseline gap-x-4 text-[0.6875rem] tracking-[0.28em] uppercase tabular-nums"
                        >
                            <span class="text-amber font-display">{{
                                t(`history.eras.${era.key}.index`)
                            }}</span>
                            {{ t(`history.eras.${era.key}.years`) }}
                        </p>

                        <h2
                            class="font-display text-bone mt-3 text-[clamp(1.875rem,3.6vw,3rem)] leading-[1.02] tracking-tight uppercase"
                        >
                            {{ t(`history.eras.${era.key}.label`) }}
                        </h2>

                        <p
                            class="text-amber mt-3 max-w-[30ch] text-[clamp(1.0625rem,1.5vw,1.375rem)] leading-snug font-semibold"
                        >
                            {{ t(`history.eras.${era.key}.title`) }}
                        </p>

                        <p class="text-bone-muted mt-4 max-w-[48ch]">
                            {{ t(`history.eras.${era.key}.lede`) }}
                        </p>

                        <figure v-if="era.photo" class="mt-7 hidden lg:block">
                            <picture>
                                <source
                                    type="image/avif"
                                    :srcset="`/images/tlo-${era.photo}-1000.avif 1000w, /images/tlo-${era.photo}-1400.avif 1400w`"
                                    sizes="(min-width: 1024px) 40vw, 100vw"
                                />
                                <img
                                    :src="`/images/tlo-${era.photo}-1000.jpg`"
                                    :srcset="`/images/tlo-${era.photo}-1000.jpg 1000w, /images/tlo-${era.photo}-1400.jpg 1400w`"
                                    sizes="(min-width: 1024px) 40vw, 100vw"
                                    :alt="
                                        t(
                                            `home.sections.${era.photo}.photo_caption`,
                                        )
                                    "
                                    class="border-hairline aspect-[16/9] w-full border object-cover"
                                    loading="lazy"
                                    decoding="async"
                                />
                            </picture>
                            <figcaption
                                class="text-bone-muted/80 mt-2 text-[0.625rem] tracking-[0.14em] uppercase"
                            >
                                {{
                                    t(
                                        `home.sections.${era.photo}.photo_caption`,
                                    )
                                }}
                                ·
                                {{
                                    t(`home.sections.${era.photo}.photo_credit`)
                                }}
                            </figcaption>
                        </figure>
                    </div>

                    <div>
                        <p
                            class="text-bone-muted border-hairline border-b pb-2 text-[0.6875rem] tracking-[0.28em] uppercase"
                        >
                            {{ t('history.topics_label') }}
                        </p>

                        <component
                            :is="'href' in era ? Link : 'a'"
                            v-if="'href' in era"
                            :href="'href' in era ? era.href : '#'"
                            class="border-hairline hover:border-amber hover:bg-amber/10 focus-visible:outline-amber mb-4 flex items-center justify-between gap-4 border px-4 py-3 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                        >
                            {{ t('history.open_era') }}
                            <span aria-hidden="true">&rarr;</span>
                        </component>

                        <ul class="grid">
                            <li
                                v-for="(topic, index) in topicsFor(era.key)"
                                :key="topic.title"
                            >
                                <component
                                    :is="hrefFor(era.key, index) ? Link : 'a'"
                                    :href="hrefFor(era.key, index) ?? '#'"
                                    class="border-hairline group hover:bg-bone/[0.03] focus-visible:outline-amber block border-b px-1 py-4 transition focus-visible:outline-2 focus-visible:outline-offset-2"
                                >
                                    <span
                                        class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1"
                                    >
                                        <span
                                            class="font-display text-bone group-hover:text-amber text-base tracking-wide uppercase transition-colors"
                                        >
                                            {{ topic.title }}
                                        </span>
                                        <span
                                            class="text-[0.625rem] tracking-[0.2em] uppercase"
                                            :class="
                                                hrefFor(era.key, index)
                                                    ? 'text-amber'
                                                    : 'text-bone-muted/80'
                                            "
                                        >
                                            {{
                                                hrefFor(era.key, index)
                                                    ? t('history.ready')
                                                    : t('history.soon')
                                            }}
                                        </span>
                                    </span>
                                    <span
                                        class="text-bone-muted mt-1.5 block max-w-[52ch] text-[0.9375rem]"
                                    >
                                        {{ topic.note }}
                                    </span>
                                </component>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-hairline relative border-t">
            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 py-14 sm:py-16"
            >
                <Link
                    href="/"
                    class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber inline-flex items-center gap-2.5 border-b pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                >
                    &larr; {{ t('history.back') }}
                </Link>

                <div class="mt-10">
                    <SiteFooter />
                </div>
            </div>
        </section>
    </main>
</template>
