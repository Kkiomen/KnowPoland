<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { group, t } from '@/i18n';

/**
 * How this site is made.
 *
 * The home page makes three claims about this site: that it is free, that it
 * is sourced, and that corrections are visible. The first needs no page, the
 * other two had nothing behind them, which is the kind of gap a reader is
 * entitled to catch. This page is the evidence, and the corrections log is
 * deliberately the longest part of it.
 */
type Pair = { k: string; v: string };
type Correction = { date: string; where: string; was: string; now: string };

const text = (path: string): string => t(`method.${path}`);

const rules = computed<Pair[]>(
    () => group<Pair[]>('method.sources.items') ?? [],
);
const corrections = computed<Correction[]>(
    () => group<Correction[]>('method.corrections.items') ?? [],
);
const column = (name: string): string => text(`corrections.columns.${name}`);
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
        <!-- The title card, type only: this page is about words, not places -->
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

        <!-- What the reader is and is not being offered -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                >
                    {{ text('promise.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('promise.title') }}
                </h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2 md:gap-10">
                    <p class="text-[1.0625rem] leading-relaxed text-[#3b352d]">
                        {{ text('promise.body_1') }}
                    </p>
                    <p class="leading-relaxed text-[#6e6459]">
                        {{ text('promise.body_2') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- The method, which is what the word sourced is supposed to mean -->
        <section id="sources" class="scroll-mt-24 bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('sources.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#f4ede0] uppercase"
                >
                    {{ text('sources.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#cabfad]">
                    {{ text('sources.note') }}
                </p>

                <dl class="mt-8 grid gap-6 md:grid-cols-2 lg:gap-x-10">
                    <div
                        v-for="rule in rules"
                        :key="rule.k"
                        class="border-t border-white/15 pt-4"
                    >
                        <dt
                            class="font-display text-[1.0625rem] tracking-wide text-[#f4ede0]"
                        >
                            {{ rule.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 max-w-[62ch] text-[0.9375rem] leading-relaxed text-[#cabfad]"
                        >
                            {{ rule.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- The log. Was and is sit side by side on purpose. -->
        <section id="corrections" class="scroll-mt-24 bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#a8520f] uppercase"
                >
                    {{ text('corrections.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('corrections.title') }}
                </h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-[#6e6459]">
                    {{ text('corrections.note') }}
                </p>

                <ol
                    class="mt-8 grid list-none gap-px overflow-hidden rounded-lg bg-[#e0d3bd] p-0"
                >
                    <li
                        v-for="correction in corrections"
                        :key="correction.date + correction.where"
                        class="bg-white px-5 py-5"
                    >
                        <p
                            class="flex flex-wrap items-baseline gap-x-4 gap-y-1"
                        >
                            <span
                                class="font-display text-[0.9375rem] text-[#b32347] tabular-nums"
                            >
                                {{ correction.date }}
                            </span>
                            <span
                                class="text-[0.6875rem] tracking-[0.18em] text-[#6e6459] uppercase"
                            >
                                {{ column('where') }}: {{ correction.where }}
                            </span>
                        </p>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2 sm:gap-8">
                            <p>
                                <span
                                    class="block text-[0.625rem] tracking-[0.18em] text-[#6e6459] uppercase"
                                >
                                    {{ column('was') }}
                                </span>
                                <span
                                    class="mt-1 block text-[0.9375rem] leading-relaxed text-[#6e6459] line-through decoration-[#d9cbb3]"
                                >
                                    {{ correction.was }}
                                </span>
                            </p>
                            <p>
                                <span
                                    class="block text-[0.625rem] tracking-[0.18em] text-[#457036] uppercase"
                                >
                                    {{ column('now') }}
                                </span>
                                <span
                                    class="mt-1 block text-[0.9375rem] leading-relaxed text-[#3b352d]"
                                >
                                    {{ correction.now }}
                                </span>
                            </p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <!-- How to add to the list above -->
        <section id="contact" class="bg-ink scroll-mt-24">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-bone-muted text-[0.6875rem] tracking-[0.28em] uppercase"
                >
                    {{ text('contact.label') }}
                </p>
                <h2
                    class="font-display text-bone mt-3 max-w-[24ch] text-[clamp(1.5rem,3vw,2.25rem)] leading-tight tracking-tight uppercase"
                >
                    {{ text('contact.title') }}
                </h2>
                <p class="text-bone-muted mt-4 max-w-[68ch] leading-relaxed">
                    {{ text('contact.body') }}
                </p>
                <p
                    class="text-bone-muted/80 mt-3 max-w-[68ch] text-[0.9375rem]"
                >
                    {{ text('contact.note') }}
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
