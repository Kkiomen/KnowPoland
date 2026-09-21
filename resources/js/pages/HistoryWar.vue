<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ExhibitImage from '@/components/ExhibitImage.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { articleForWarTopic, nextAfter } from '@/history-articles';
import { group, t } from '@/i18n';

const next = nextAfter('/history/second-world-war');

/**
 * The Second World War, at length. This is the heaviest subject on the site,
 * so the page stays plain: a chronology, six chapters, a note about the words
 * people get wrong, and how to visit the places without turning them into an
 * attraction. Every word lives in lang/<locale>/site.php.
 */
const chapters = [
    {
        key: 'invasion',
        tone: 'linear-gradient(190deg, #1b1f26 0%, #0d0f12 78%)',
    },
    {
        key: 'occupation',
        tone: 'linear-gradient(190deg, #1c1a1a 0%, #0d0f12 78%)',
    },
    {
        key: 'holocaust',
        tone: 'linear-gradient(190deg, #16181c 0%, #0a0b0d 80%)',
        solemn: true,
    },
    {
        key: 'resistance',
        tone: 'linear-gradient(190deg, #1a201f 0%, #0d0f12 78%)',
    },
    {
        key: 'uprising',
        tone: 'linear-gradient(190deg, #211a17 0%, #0d0f12 78%)',
    },
    {
        key: 'aftermath',
        tone: 'linear-gradient(190deg, #1a1c22 0%, #0d0f12 78%)',
    },
] as const;

type Entry = { title: string; note: string; personal?: string };
type Moment = { year: string; title: string; note: string };

const topicsFor = (key: string): Entry[] =>
    group<Entry[]>(`war.chapters.${key}.topics`) ?? [];
const timeline = (): Moment[] => group<Moment[]>('war.timeline.items') ?? [];
const sites = (): Entry[] => group<Entry[]>('war.visiting.sites') ?? [];
const etiquette = (): Entry[] => group<Entry[]>('war.visiting.etiquette') ?? [];

/**
 * Some topics in these chapters have grown into articles of their own. The era
 * bands on /history are full, so those articles hang off the chapter topic
 * instead, through the warTopic field in the reading order. A topic that has
 * one becomes a link and gets the era page's own "ready" treatment, a topic
 * that does not stays plain text, so an unwritten topic is never a dead link.
 */
const hrefForTopic = (chapter: string, index: number): string | undefined =>
    articleForWarTopic(chapter, index)?.href;
</script>

<template>
    <Head :title="t('war.meta.title')">
        <meta
            head-key="description"
            name="description"
            :content="t('war.meta.description')"
        />
    </Head>

    <SiteHeader />

    <main class="bg-ink">
        <section class="relative overflow-hidden">
            <div
                class="absolute inset-0"
                style="
                    background:
                        radial-gradient(
                            70% 55% at 76% 18%,
                            rgba(176, 70, 56, 0.18) 0%,
                            rgba(176, 70, 56, 0) 60%
                        ),
                        linear-gradient(196deg, #1b1a1f 0%, #0a0b0d 74%);
                "
            />
            <picture>
                <source
                    type="image/avif"
                    srcset="
                        /images/tlo-memory-1000.avif 1000w,
                        /images/tlo-memory-1400.avif 1400w
                    "
                    sizes="100vw"
                />
                <img
                    src="/images/tlo-memory-1000.jpg"
                    srcset="
                        /images/tlo-memory-1000.jpg 1000w,
                        /images/tlo-memory-1400.jpg 1400w
                    "
                    sizes="100vw"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover opacity-20"
                    loading="eager"
                    decoding="async"
                />
            </picture>
            <div
                class="from-ink/70 absolute inset-0 bg-linear-to-r to-transparent"
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
                    {{ t('war.hero.kicker') }}
                </p>

                <h1
                    class="font-display text-bone mt-3 text-[clamp(2.5rem,7vw,5.5rem)] leading-[0.95] tracking-tight uppercase"
                >
                    {{ t('war.hero.title') }}
                    <span class="text-flag-red">{{
                        t('war.hero.title_accent')
                    }}</span>
                </h1>

                <p class="text-bone-muted mt-5 max-w-[58ch] text-lg">
                    {{ t('war.hero.lede') }}
                </p>

                <p
                    class="border-hairline text-bone-muted/80 mt-6 max-w-[58ch] border-l-2 pl-4 text-sm"
                >
                    {{ t('war.hero.note') }}
                </p>
            </div>
        </section>

        <!-- The chronology, so the rest of the page has a spine. -->
        <section
            class="border-hairline relative border-t bg-[#0b0c0f] py-14 sm:py-16"
        >
            <div class="mx-auto w-full max-w-[1200px] px-6">
                <h2
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ t('war.timeline.label') }}
                </h2>

                <ol class="border-hairline mt-5 grid border-t sm:grid-cols-2">
                    <li
                        v-for="moment in timeline()"
                        :key="moment.year"
                        class="border-hairline border-b py-4 sm:pr-8 sm:odd:border-r sm:even:pl-8"
                    >
                        <p
                            class="font-display text-flag-red text-sm tracking-[0.12em] tabular-nums"
                        >
                            {{ moment.year }}
                        </p>
                        <p
                            class="font-display text-bone mt-1 text-base tracking-wide uppercase"
                        >
                            {{ moment.title }}
                        </p>
                        <p class="text-bone-muted mt-1 max-w-[52ch] text-sm">
                            {{ moment.note }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Six chapters, each with its topics. -->
        <section
            v-for="chapter in chapters"
            :id="chapter.key"
            :key="chapter.key"
            class="border-hairline relative scroll-mt-44 overflow-hidden border-t sm:scroll-mt-28"
        >
            <div
                class="absolute inset-0"
                :style="{ background: chapter.tone }"
            />
            <div
                class="absolute inset-0 opacity-50 [background:repeating-linear-gradient(0deg,rgba(0,0,0,.22)_0_1px,rgba(0,0,0,0)_1px_3px)]"
            />

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20"
            >
                <div class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-14">
                    <div>
                        <p
                            class="text-amber font-display text-[0.6875rem] tracking-[0.28em] uppercase"
                        >
                            {{ t(`war.chapters.${chapter.key}.index`) }}
                            <span class="text-bone-muted ml-3">{{
                                t(`war.chapters.${chapter.key}.label`)
                            }}</span>
                        </p>

                        <h2
                            class="font-display text-bone mt-3 text-[clamp(1.75rem,3.2vw,2.75rem)] leading-[1.04] tracking-tight uppercase"
                        >
                            {{ t(`war.chapters.${chapter.key}.title`) }}
                        </h2>

                        <p class="text-bone-muted mt-4 max-w-[48ch]">
                            {{ t(`war.chapters.${chapter.key}.lede`) }}
                        </p>

                        <!-- One piece of evidence per chapter: a photograph taken at the time, or a document. -->
                        <figure
                            class="border-hairline bg-ink/40 mt-7 border p-3 sm:p-4"
                        >
                            <ExhibitImage
                                :src="`/images/war-${chapter.key}-sm.jpg`"
                                :srcset="`/images/war-${chapter.key}-sm.jpg 960w, /images/war-${chapter.key}-lg.jpg 1280w`"
                                sizes="(min-width: 1024px) 34rem, 92vw"
                                :full="`/images/war-${chapter.key}-lg.jpg`"
                                :alt="t(`war.chapters.${chapter.key}.alt`)"
                                :caption="
                                    t(`war.chapters.${chapter.key}.caption`)
                                "
                                :credit="
                                    t(`war.chapters.${chapter.key}.credit`)
                                "
                            />
                            <figcaption
                                class="text-bone-muted/75 mt-3 text-[0.625rem] leading-[1.7] tracking-[0.14em] uppercase"
                            >
                                {{ t(`war.chapters.${chapter.key}.caption`) }}
                                &middot;
                                {{ t(`war.chapters.${chapter.key}.credit`) }}
                            </figcaption>
                        </figure>
                    </div>

                    <!-- content-start: the picture makes the left column taller, and the notes must not drift apart to match it -->
                    <ul class="border-hairline grid content-start border-t">
                        <li
                            v-for="(topic, index) in topicsFor(chapter.key)"
                            :key="topic.title"
                        >
                            <component
                                :is="
                                    hrefForTopic(chapter.key, index)
                                        ? Link
                                        : 'div'
                                "
                                :href="hrefForTopic(chapter.key, index)"
                                class="border-hairline group block border-b px-1 py-4"
                                :class="
                                    hrefForTopic(chapter.key, index)
                                        ? 'hover:bg-bone/[0.03] focus-visible:outline-amber transition focus-visible:outline-2 focus-visible:outline-offset-2'
                                        : ''
                                "
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
                                        v-if="hrefForTopic(chapter.key, index)"
                                        class="text-amber text-[0.625rem] tracking-[0.2em] uppercase"
                                    >
                                        {{ t('history.ready') }}
                                    </span>
                                </span>
                                <span
                                    class="text-bone-muted mt-1.5 block max-w-[54ch] text-[0.9375rem]"
                                >
                                    {{ topic.note }}
                                </span>
                            </component>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- The wording correction, given its own weight. -->
        <section class="border-hairline border-t bg-[#140f10]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-14 sm:py-16">
                <div
                    class="border-flag-red/60 bg-ink/60 border-l-4 py-5 pr-5 pl-6"
                >
                    <p
                        class="text-flag-red text-[0.6875rem] tracking-[0.28em] uppercase"
                    >
                        {{ t('war.language.label') }}
                    </p>
                    <h2
                        class="font-display text-bone mt-2 max-w-[30ch] text-[clamp(1.375rem,2.4vw,2rem)] leading-tight uppercase"
                    >
                        {{ t('war.language.title') }}
                    </h2>
                    <p class="text-bone-muted mt-3 max-w-[70ch]">
                        {{ t('war.language.body') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Visiting: the sites, and how to behave at them. -->
        <section class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-amber font-display text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ t('war.visiting.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 max-w-[22ch] text-[clamp(1.75rem,3.2vw,2.75rem)] leading-[1.04] tracking-tight uppercase"
                >
                    {{ t('war.visiting.title') }}
                </h2>
                <p class="text-bone-muted mt-4 max-w-[56ch]">
                    {{ t('war.visiting.lede') }}
                </p>

                <div
                    class="mt-9 grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-14"
                >
                    <ul class="border-hairline grid border-t">
                        <li
                            v-for="site in sites()"
                            :key="site.title"
                            class="border-hairline border-b py-4"
                        >
                            <p
                                class="font-display text-bone text-base tracking-wide uppercase"
                            >
                                {{ site.title }}
                            </p>
                            <p
                                class="text-bone-muted mt-1 max-w-[52ch] text-sm"
                            >
                                {{ site.note }}
                            </p>

                            <!-- The one place on the site where the author speaks in the first person. -->
                            <div
                                v-if="site.personal"
                                class="border-amber-deep mt-3 border-l-2 pl-4"
                            >
                                <p
                                    class="text-amber text-[0.625rem] tracking-[0.2em] uppercase"
                                >
                                    {{ t('war.visiting.author_label') }}
                                </p>
                                <p
                                    class="text-bone mt-1.5 max-w-[52ch] text-sm"
                                >
                                    {{ site.personal }}
                                </p>
                            </div>
                        </li>
                    </ul>

                    <div class="grid gap-3">
                        <figure
                            class="border-hairline bg-ink/40 border p-3 sm:p-4"
                        >
                            <ExhibitImage
                                src="/images/war-visiting-sm.jpg"
                                srcset="/images/war-visiting-sm.jpg 960w, /images/war-visiting-lg.jpg 1280w"
                                sizes="(min-width: 1024px) 30rem, 92vw"
                                full="/images/war-visiting-lg.jpg"
                                :alt="t('war.visiting.alt')"
                                :caption="t('war.visiting.caption')"
                                :credit="t('war.visiting.credit')"
                            />
                            <figcaption
                                class="text-bone-muted/75 mt-3 text-[0.625rem] leading-[1.7] tracking-[0.14em] uppercase"
                            >
                                {{ t('war.visiting.caption') }} &middot;
                                {{ t('war.visiting.credit') }}
                            </figcaption>
                        </figure>

                        <ul class="grid gap-3">
                            <li
                                v-for="rule in etiquette()"
                                :key="rule.title"
                                class="border-hairline bg-bone/[0.03] border p-4"
                            >
                                <p
                                    class="font-display text-bone text-sm tracking-wide uppercase"
                                >
                                    {{ rule.title }}
                                </p>
                                <p class="text-bone-muted mt-1 text-sm">
                                    {{ rule.note }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sources and the way back -->
        <section class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-14 sm:py-16">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ t('war.sources.label') }}
                </p>
                <p class="text-bone-muted mt-3 mb-10 max-w-[70ch]">
                    {{ t('war.sources.body') }}
                </p>

                <div
                    class="flex flex-wrap items-center justify-between gap-x-8 gap-y-4"
                >
                    <Link
                        href="/history"
                        class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber inline-flex items-center gap-2.5 border-b pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                    >
                        &larr; {{ t('war.back') }}
                    </Link>

                    <Link
                        v-if="next"
                        :href="next.href"
                        class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber group inline-flex max-w-full flex-col items-start gap-1 border-b pb-1.5 text-left transition focus-visible:outline-2 focus-visible:outline-offset-4 sm:items-end sm:text-right"
                    >
                        <span
                            class="text-bone-muted text-[0.625rem] tracking-[0.2em] uppercase"
                        >
                            {{ t('history.next_topic') }}
                        </span>
                        <span
                            class="font-display text-bone group-hover:text-amber text-sm tracking-wide uppercase transition-colors"
                        >
                            {{ t(next.titleKey) }} &rarr;
                        </span>
                    </Link>
                </div>

                <div class="mt-10">
                    <SiteFooter />
                </div>
            </div>
        </section>
    </main>
</template>
