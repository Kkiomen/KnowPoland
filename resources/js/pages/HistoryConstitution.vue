<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import ExhibitImage from '@/components/ExhibitImage.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { nextAfter } from '@/history-articles';
import { group, t } from '@/i18n';

const next = nextAfter('/history/constitution-of-3-may-1791');

/**
 * The Constitution of 3 May 1791, from the First Partition through the Great
 * Sejm to Targowica and the holiday it became, told as a filmstrip:
 * scrolling down drives the frames sideways. On a phone the strip becomes an ordinary vertical stack, because
 * horizontal scrolling on a small screen is a good way to lose the reader.
 */
type Frame = {
    key: string;
    photo: string;
    label: string;
    year: string;
    title: string;
    body: string;
    caption: string;
    credit: string;
};

const frames = computed<Frame[]>(
    () => group<Frame[]>('constitution.frames') ?? [],
);

/**
 * Two kinds of picture appear here and they cannot be shown the same way.
 * A place is a backdrop: it fills the frame behind the text. An object - a
 * portrait, an engraving, a printed map - is an exhibit, and cropping it to
 * fill a widescreen frame destroys the very thing the reader came to see, so
 * it gets shown whole, on its own plate, with the picture behind it
 * dimmed to a texture.
 */
const exhibits = new Set([
    'partition',
    'schools',
    'sejm',
    'patriots',
    'act',
    'peasants',
    'targowica',
]);

const isExhibit = (key: string): boolean => exhibits.has(key);

/**
 * The satire of 1772 is a wide sheet, so it gets the wide panel; squeezed
 * into the narrow one it shrinks to a postage stamp.
 */
const wide = new Set(['partition']);

const isWide = (key: string): boolean => wide.has(key);

/**
 * Backdrops wider than their frame are anchored on the subject: the throne
 * end of the hall in Wojniakowski's painting, the brick ruin in the garden.
 */
const focus: Record<string, string> = {
    day: 'object-[60%_center]',
    today: 'object-[40%_center]',
};

const strip = ref<HTMLElement | null>(null);
const track = ref<HTMLElement | null>(null);
const progress = ref(0);
const active = ref(0);

const horizontal = () =>
    typeof window !== 'undefined' &&
    window.matchMedia('(min-width: 1024px) and (min-height: 900px)').matches &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let frameRequested = false;

/** The strip is as tall as it is wide: one screen of scroll per frame. */
function measure(): void {
    if (!strip.value || !track.value) {
        return;
    }

    if (horizontal()) {
        strip.value.style.height = `${frames.value.length * 100}dvh`;
    } else {
        strip.value.style.height = 'auto';
        track.value.style.transform = '';
    }
}

function update(): void {
    frameRequested = false;

    if (!strip.value || !track.value) {
        return;
    }

    const span = strip.value.offsetHeight - window.innerHeight;
    const travelled = -strip.value.getBoundingClientRect().top;

    if (horizontal()) {
        const ratio = Math.min(1, Math.max(0, travelled / span));
        progress.value = ratio;
        active.value = Math.round(ratio * (frames.value.length - 1));
        track.value.style.transform = `translate3d(${-ratio * (frames.value.length - 1) * 100}vw, 0, 0)`;

        return;
    }

    const seen = [...track.value.children].filter(
        (child) => child.getBoundingClientRect().top < window.innerHeight * 0.5,
    ).length;

    active.value = Math.max(0, seen - 1);
    progress.value = active.value / Math.max(1, frames.value.length - 1);
}

function onScroll(): void {
    if (!frameRequested) {
        frameRequested = true;
        requestAnimationFrame(update);
    }
}

function goTo(index: number): void {
    if (!strip.value) {
        return;
    }

    if (!horizontal()) {
        track.value?.children[index]?.scrollIntoView({ behavior: 'smooth' });

        return;
    }

    const span = strip.value.offsetHeight - window.innerHeight;
    const top =
        window.scrollY +
        strip.value.getBoundingClientRect().top +
        (index / Math.max(1, frames.value.length - 1)) * span;

    window.scrollTo({ top, behavior: 'smooth' });
}

onMounted(() => {
    measure();
    update();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', measure);
    window.addEventListener('resize', onScroll);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', measure);
    window.removeEventListener('resize', onScroll);
});
</script>

<template>
    <Head :title="t('constitution.meta.title')">
        <meta
            head-key="description"
            name="description"
            :content="t('constitution.meta.description')"
        />
    </Head>

    <SiteHeader />

    <main class="bg-ink">
        <!-- The title card -->
        <section class="relative overflow-hidden">
            <picture>
                <source
                    type="image/avif"
                    srcset="
                        /images/constitution-hero-sm.avif  960w,
                        /images/constitution-hero-lg.avif 1280w
                    "
                    sizes="100vw"
                />
                <img
                    src="/images/constitution-hero-sm.jpg"
                    srcset="
                        /images/constitution-hero-sm.jpg  960w,
                        /images/constitution-hero-lg.jpg 1280w
                    "
                    sizes="100vw"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover opacity-30"
                    loading="eager"
                    decoding="async"
                />
            </picture>
            <div
                class="from-ink via-ink/70 absolute inset-0 bg-linear-to-r to-transparent"
            />
            <div
                class="from-ink/40 to-ink absolute inset-0 bg-linear-to-b via-transparent"
            />

            <div
                class="relative mx-auto w-full max-w-[1200px] px-6 pt-32 pb-16 sm:pt-36"
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
                    {{ t('constitution.hero.kicker') }}
                </p>

                <h1
                    class="font-display text-bone mt-3 text-[clamp(2.5rem,7vw,5.5rem)] leading-[0.95] tracking-tight uppercase"
                >
                    {{ t('constitution.hero.title') }}
                    <span class="text-amber">{{
                        t('constitution.hero.title_accent')
                    }}</span>
                </h1>

                <p class="text-bone-muted mt-5 max-w-[52ch] text-lg">
                    {{ t('constitution.hero.lede') }}
                </p>
                <p
                    class="text-bone-muted/80 mt-4 text-[0.6875rem] tracking-[0.2em] uppercase"
                >
                    {{ t('constitution.hero.meta_line') }}
                </p>
            </div>
        </section>

        <!-- The filmstrip -->
        <div ref="strip" class="relative">
            <div class="film:sticky film:top-0 film:h-dvh film:overflow-hidden">
                <div
                    ref="track"
                    class="film:flex film:h-full film:will-change-transform"
                >
                    <article
                        v-for="(frame, index) in frames"
                        :key="frame.key"
                        class="border-hairline relative flex min-h-[86dvh] shrink-0 items-end overflow-hidden border-t film:h-full film:min-h-0 film:w-screen film:border-t-0 film:border-l"
                    >
                        <picture>
                            <source
                                type="image/avif"
                                :srcset="`/images/constitution-${frame.photo}-sm.avif 960w, /images/constitution-${frame.photo}-lg.avif 1280w`"
                                sizes="100vw"
                            />
                            <img
                                :src="`/images/constitution-${frame.photo}-sm.jpg`"
                                :srcset="`/images/constitution-${frame.photo}-sm.jpg 960w, /images/constitution-${frame.photo}-lg.jpg 1280w`"
                                sizes="100vw"
                                :alt="frame.caption"
                                class="absolute inset-0 h-full w-full object-cover"
                                :class="[
                                    focus[frame.key],
                                    isExhibit(frame.key)
                                        ? 'scale-105 opacity-45 blur-[3px]'
                                        : '',
                                ]"
                                :loading="index === 0 ? 'eager' : 'lazy'"
                                decoding="async"
                            />
                        </picture>
                        <div
                            class="from-ink via-ink/55 to-ink/55 absolute inset-0 bg-linear-to-r film:to-transparent"
                        />
                        <div
                            class="from-ink/95 absolute inset-0 bg-linear-to-t via-transparent to-transparent"
                        />
                        <!-- On a phone the text runs the full width, over the bright half of the photograph too -->
                        <div class="bg-ink/45 absolute inset-0 film:hidden" />
                        <div
                            class="absolute inset-0 opacity-40 [background:repeating-linear-gradient(0deg,rgba(0,0,0,.22)_0_1px,rgba(0,0,0,0)_1px_3px)]"
                        />

                        <div
                            class="relative mx-auto w-full max-w-[1200px] px-6 pt-36 pb-14 sm:pt-32 film:pt-24 film:pb-20"
                        >
                            <div
                                class="grid gap-8 film:items-end"
                                :class="
                                    isExhibit(frame.key)
                                        ? isWide(frame.key)
                                            ? 'film:grid-cols-[minmax(0,46ch)_minmax(0,34rem)]'
                                            : 'film:grid-cols-[minmax(0,46ch)_minmax(0,22rem)]'
                                        : ''
                                "
                            >
                                <div class="max-w-[46ch]">
                                    <p
                                        class="text-amber font-display flex flex-wrap items-baseline gap-x-4 text-[0.6875rem] tracking-[0.28em] uppercase"
                                    >
                                        {{ String(index + 1).padStart(2, '0') }}
                                        <span class="text-bone-muted">{{
                                            frame.label
                                        }}</span>
                                        <span
                                            class="text-bone-muted/80 tabular-nums"
                                            >{{ frame.year }}</span
                                        >
                                    </p>

                                    <h2
                                        class="font-display text-bone mt-3 text-[clamp(1.625rem,3vw,2.5rem)] leading-[1.06] tracking-tight uppercase"
                                    >
                                        {{ frame.title }}
                                    </h2>

                                    <p
                                        class="text-bone mt-4 text-base sm:text-lg"
                                    >
                                        {{ frame.body }}
                                    </p>

                                    <p
                                        class="text-bone-muted/80 mt-5 text-[0.625rem] tracking-[0.16em] uppercase"
                                    >
                                        {{ frame.caption }} · {{ frame.credit }}
                                    </p>
                                </div>

                                <div
                                    v-if="isExhibit(frame.key)"
                                    class="border-hairline bg-ink/70 w-full border p-4 backdrop-blur-[2px]"
                                    :class="
                                        isWide(frame.key)
                                            ? 'max-w-[34rem]'
                                            : 'max-w-[22rem]'
                                    "
                                >
                                    <ExhibitImage
                                        :src="`/images/constitution-${frame.photo}-sm.jpg`"
                                        :srcset="`/images/constitution-${frame.photo}-sm.jpg 960w, /images/constitution-${frame.photo}-lg.jpg 1280w`"
                                        :sizes="
                                            isWide(frame.key)
                                                ? '(min-width: 1024px) 34rem, 90vw'
                                                : '(min-width: 1024px) 22rem, 90vw'
                                        "
                                        :full="`/images/constitution-${frame.photo}-lg.jpg`"
                                        :alt="frame.caption"
                                        :caption="frame.caption"
                                        :credit="frame.credit"
                                    />
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Progress rail, on the wide layout only -->
                <div
                    class="from-ink/90 pointer-events-none absolute inset-x-0 bottom-0 hidden bg-linear-to-t to-transparent px-6 pt-10 pb-5 film:block"
                >
                    <div class="mx-auto w-full max-w-[1200px]">
                        <div class="bg-hairline relative h-px">
                            <div
                                class="bg-amber absolute inset-y-0 left-0"
                                :style="{ width: `${progress * 100}%` }"
                            />
                        </div>
                        <div
                            class="pointer-events-auto mt-3 flex justify-between gap-3"
                        >
                            <button
                                v-for="(frame, index) in frames"
                                :key="frame.key"
                                type="button"
                                class="focus-visible:outline-amber text-[0.625rem] tracking-[0.18em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                                :class="
                                    index === active
                                        ? 'text-amber'
                                        : 'text-bone-muted hover:text-bone'
                                "
                                :aria-current="
                                    index === active ? 'true' : undefined
                                "
                                @click="goTo(index)"
                            >
                                {{ frame.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sources and the way back -->
        <section class="border-hairline border-t">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-14 sm:py-16">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ t('constitution.sources.label') }}
                </p>
                <p class="text-bone-muted mt-3 max-w-[70ch]">
                    {{ t('constitution.sources.body') }}
                </p>

                <div
                    class="flex flex-wrap items-center justify-between gap-x-8 gap-y-4"
                >
                    <Link
                        href="/history"
                        class="border-amber-deep hover:border-amber hover:text-amber focus-visible:outline-amber inline-flex items-center gap-2.5 border-b pb-1.5 text-xs tracking-[0.2em] uppercase transition focus-visible:outline-2 focus-visible:outline-offset-4"
                    >
                        &larr; {{ t('constitution.next') }}
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
