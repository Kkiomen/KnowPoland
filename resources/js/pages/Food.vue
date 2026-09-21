<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ExhibitImage from '@/components/ExhibitImage.vue';
import RecipeDialog from '@/components/RecipeDialog.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { group, t } from '@/i18n';

/**
 * The page about the Polish table.
 *
 * The rest of the site is dark because it is mostly about a century that was.
 * Food on black reads as evidence, so here the ground is warm paper and the
 * dark is kept for the three places where it means something: the opening
 * frame, the wall of plates, and Christmas Eve, which is eaten after dark.
 *
 * Three devices carry the page. The dishes are photographs with the words laid
 * on them, so the food is the page rather than an illustration of it. The
 * things a reader has to hold on to - six rules, five meals - are flat blocks
 * of colour, which are quick to scan. The two places worth knowing get a
 * coloured panel with the photograph pushed out over its edge.
 */
type Rule = { k: string; v: string };
type Recipe = {
    time: string;
    serves: string;
    ingredients: string[];
    steps: string[];
};
type Dish = {
    photo: string;
    name: string;
    kind: string;
    said: string;
    w: number;
    h: number;
    note: string;
    credit: string;
    recipe: Recipe;
};
type Meal = { time: string; name: string; note: string };
type Place = {
    photo: string;
    name: string;
    price: string;
    note: string;
    credit: string;
};
type Row = { name: string; note: string };
type Course = { title: string; note: string };
type Myth = { tag: string; title: string; note: string };
type Figure = { value: string; unit: string; note: string };

const text = (path: string): string => t(`food.${path}`);

const rules = computed<Rule[]>(() => group<Rule[]>('food.table.items') ?? []);
const dishes = computed<Dish[]>(() => group<Dish[]>('food.dishes.items') ?? []);
const meals = computed<Meal[]>(() => group<Meal[]>('food.day.items') ?? []);
const places = computed<Place[]>(
    () => group<Place[]>('food.where.items') ?? [],
);
const otherPlaces = computed<Row[]>(
    () => group<Row[]>('food.where.notes') ?? [],
);
const courses = computed<Course[]>(
    () => group<Course[]>('food.wigilia.items') ?? [],
);
const drinks = computed<Row[]>(() => group<Row[]>('food.drinks.items') ?? []);
const figures = computed<Figure[]>(
    () => group<Figure[]>('food.numbers.items') ?? [],
);
const easterTable = computed<Course[]>(
    () => group<Course[]>('food.easter.items') ?? [],
);
const gatherings = computed<Row[]>(() => group<Row[]>('food.year.items') ?? []);
const myths = computed<Myth[]>(() => group<Myth[]>('food.myths.items') ?? []);

/**
 * Colours taken off the food itself - beetroot, crust, dill, honey, wine -
 * cycled in a fixed order, so a block keeps its colour between visits.
 */
const CHIPS = ['#b32347', '#a8520f', '#457036', '#8a5a10', '#7c1d32'] as const;
const chip = (index: number): string => CHIPS[index % CHIPS.length];

/**
 * Which dish is open in the recipe panel. One panel serves all of them, so the
 * page holds the index rather than every tile holding a dialog of its own.
 */
const openDish = ref<number | null>(null);
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
                        /images/food-hero-sm.avif  960w,
                        /images/food-hero-lg.avif 1280w
                    "
                    sizes="100vw"
                />
                <img
                    src="/images/food-hero-sm.jpg"
                    srcset="
                        /images/food-hero-sm.jpg  960w,
                        /images/food-hero-lg.jpg 1280w
                    "
                    sizes="100vw"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover opacity-70"
                    loading="eager"
                    decoding="async"
                />
            </picture>
            <div
                class="absolute inset-0 bg-[linear-gradient(100deg,rgba(11,10,8,.95)_0%,rgba(11,10,8,.76)_48%,rgba(11,10,8,.12)_100%)]"
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

        <!-- Six things that will save you, as blocks of colour -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#8a5a10] uppercase"
                >
                    {{ text('table.label') }}
                </p>
                <h2
                    class="font-display mt-2 text-xl tracking-wide text-[#221e19] uppercase"
                >
                    {{ text('table.title') }}
                </h2>
                <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(rule, index) in rules"
                        :key="rule.k"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index) }"
                    >
                        <dt
                            class="font-display text-sm tracking-[0.06em] text-white uppercase"
                        >
                            {{ rule.k }}
                        </dt>
                        <dd
                            class="m-0 mt-2 text-[0.9375rem] leading-relaxed text-white"
                        >
                            {{ rule.v }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- The wall of plates: the words lie on the food -->
        <section class="bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('dishes.label') }}
                </p>
                <h2
                    class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#fdf9f1] uppercase"
                >
                    {{ text('dishes.title') }}
                </h2>
                <p class="mt-4 max-w-[62ch] leading-relaxed text-[#b0a795]">
                    {{ text('dishes.note') }}
                </p>
                <p
                    class="mt-2 text-[0.6875rem] font-semibold tracking-[0.2em] text-[#f5b24f] uppercase"
                >
                    {{ text('recipe_ui.hint') }}
                </p>

                <ul
                    class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <li v-for="(course, index) in dishes" :key="course.photo">
                        <button
                            type="button"
                            class="group/card focus-visible:outline-amber relative block w-full overflow-hidden rounded-lg text-left focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="openDish = index"
                        >
                            <picture>
                                <source
                                    type="image/avif"
                                    :srcset="`/images/food-${course.photo}-sm.avif 960w, /images/food-${course.photo}-lg.avif 1280w`"
                                    sizes="(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 640px) 48vw, 92vw"
                                />
                                <img
                                    :src="`/images/food-${course.photo}-sm.jpg`"
                                    :srcset="`/images/food-${course.photo}-sm.jpg 960w, /images/food-${course.photo}-lg.jpg 1280w`"
                                    sizes="(min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 640px) 48vw, 92vw"
                                    :alt="course.name"
                                    class="block aspect-[3/4] h-auto w-full object-cover transition-transform duration-500 group-hover/card:scale-[1.06]"
                                    loading="lazy"
                                    decoding="async"
                                />
                            </picture>
                            <span
                                class="absolute top-3 right-3 rounded-full px-2.5 py-1 text-[0.625rem] font-semibold tracking-[0.16em] text-white uppercase shadow-[0_2px_8px_rgba(0,0,0,.4)]"
                                :style="{ backgroundColor: chip(index) }"
                            >
                                {{ t('food.recipe_ui.open') }}
                            </span>
                            <span
                                class="pointer-events-none absolute inset-x-0 bottom-0 block bg-[linear-gradient(0deg,rgba(0,0,0,.93)_0%,rgba(0,0,0,.78)_42%,rgba(0,0,0,.28)_76%,rgba(0,0,0,0)_100%)] p-4 pt-16"
                            >
                                <span
                                    class="block text-[0.625rem] font-semibold tracking-[0.2em] text-[#ffd08a] uppercase"
                                >
                                    {{ String(index + 1).padStart(2, '0') }} ·
                                    {{ course.kind }}
                                </span>
                                <span
                                    class="font-display mt-1.5 block text-[1.15rem] leading-tight tracking-wide text-white uppercase"
                                >
                                    {{ course.name }}
                                </span>
                                <span
                                    class="mt-1 block text-[0.6875rem] tracking-[0.14em] text-white/55"
                                >
                                    {{ course.said }}
                                </span>
                                <span
                                    class="mt-2 block text-[0.8125rem] leading-snug text-white"
                                >
                                    {{ course.note }}
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
            </div>
        </section>

        <!-- The shape of a day, as blocks of colour -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                >
                    {{ text('day.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[24ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('day.title') }}
                </h2>
                <p class="mt-4 max-w-[62ch] leading-relaxed text-[#6e6459]">
                    {{ text('day.note') }}
                </p>

                <ol class="mt-8 grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <li
                        v-for="(meal, index) in meals"
                        :key="meal.time"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index) }"
                    >
                        <p
                            class="font-display text-[1.7rem] leading-none text-white tabular-nums"
                        >
                            {{ meal.time }}
                        </p>
                        <h3
                            class="font-display mt-3 text-sm tracking-wide text-white uppercase"
                        >
                            {{ meal.name }}
                        </h3>
                        <p
                            class="mt-1.5 text-[0.875rem] leading-relaxed text-white"
                        >
                            {{ meal.note }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- What the country actually eats and drinks, in figures -->
        <section class="bg-[#221e19]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                >
                    {{ text('numbers.label') }}
                </p>
                <h2
                    class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#fdf9f1] uppercase"
                >
                    {{ text('numbers.title') }}
                </h2>

                <dl class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(figure, index) in figures"
                        :key="figure.unit"
                        class="rounded-lg border-t-[3px] bg-white/[0.05] p-5"
                        :style="{ borderTopColor: chip(index) }"
                    >
                        <dt
                            class="font-display text-[2.1rem] leading-none text-[#fdf9f1] tabular-nums"
                        >
                            {{ figure.value }}
                        </dt>
                        <dd
                            class="m-0 mt-2 text-[0.6875rem] font-semibold tracking-[0.18em] text-[#f5b24f] uppercase"
                        >
                            {{ figure.unit }}
                        </dd>
                        <dd
                            class="m-0 mt-2.5 text-[0.9375rem] leading-relaxed text-[#cfc6b6]"
                        >
                            {{ figure.note }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-6 max-w-[80ch] text-[0.8125rem] text-[#8e8676]">
                    {{ text('numbers.note') }}
                </p>
            </div>
        </section>

        <!-- Two places worth knowing: coloured panel, photograph over its edge -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                >
                    {{ text('where.label') }}
                </p>
                <h2
                    class="font-display mt-3 max-w-[26ch] text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('where.title') }}
                </h2>
                <p class="mt-4 max-w-[62ch] leading-relaxed text-[#6e6459]">
                    {{ text('where.note') }}
                </p>

                <div class="mt-10 grid gap-12 lg:gap-14">
                    <article
                        v-for="(place, index) in places"
                        :key="place.photo"
                        class="grid items-center gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:gap-0"
                    >
                        <div
                            class="order-2 rounded-lg p-6 sm:p-8 lg:order-1 lg:pr-28"
                            :style="{
                                backgroundColor: chip(index === 0 ? 4 : 1),
                            }"
                        >
                            <h3
                                class="font-display text-xl tracking-wide text-white uppercase"
                            >
                                {{ place.name }}
                            </h3>
                            <p
                                class="mt-2 inline-block rounded-full bg-black/25 px-3 py-1 text-[0.6875rem] font-semibold tracking-[0.12em] text-white uppercase"
                            >
                                {{ place.price }}
                            </p>
                            <p
                                class="mt-4 max-w-[58ch] leading-relaxed text-white"
                            >
                                {{ place.note }}
                            </p>
                        </div>

                        <div class="order-1 lg:order-2 lg:-ml-20">
                            <div
                                class="overflow-hidden rounded-lg shadow-[0_14px_34px_rgba(34,30,25,.32)]"
                            >
                                <ExhibitImage
                                    :src="`/images/food-${place.photo}-sm.jpg`"
                                    :srcset="`/images/food-${place.photo}-sm.jpg 960w, /images/food-${place.photo}-lg.jpg 1280w`"
                                    sizes="(min-width: 1024px) 22rem, 92vw"
                                    :full="`/images/food-${place.photo}-lg.jpg`"
                                    :alt="place.name"
                                    :caption="place.name"
                                    :credit="place.credit"
                                    :image-class="'block aspect-[4/3] h-auto w-full object-cover'"
                                />
                            </div>
                        </div>
                    </article>
                </div>

                <dl class="mt-12 grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="row in otherPlaces"
                        :key="row.name"
                        class="rounded-lg border border-[#e0d3bd] bg-[#fbf7f0] p-4"
                    >
                        <dt
                            class="font-display text-sm tracking-wide text-[#221e19] uppercase"
                        >
                            {{ row.name }}
                        </dt>
                        <dd
                            class="m-0 mt-1.5 text-[0.9375rem] leading-relaxed text-[#4a443c]"
                        >
                            {{ row.note }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Christmas Eve, the one meal eaten after dark -->
        <section class="bg-[#0d0c0a]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <div
                    class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,24rem)] lg:gap-14"
                >
                    <div>
                        <p
                            class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#f5b24f] uppercase"
                        >
                            {{ text('wigilia.label') }}
                        </p>
                        <h2
                            class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#fdf9f1] uppercase"
                        >
                            {{ text('wigilia.title') }}
                        </h2>
                        <p
                            class="mt-5 max-w-[62ch] text-lg leading-relaxed text-[#e6dccb]"
                        >
                            {{ text('wigilia.body_1') }}
                        </p>
                        <p
                            class="mt-4 max-w-[62ch] leading-relaxed text-[#b0a795]"
                        >
                            {{ text('wigilia.body_2') }}
                        </p>

                        <dl class="mt-8 grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="course in courses"
                                :key="course.title"
                                class="rounded-lg border border-white/10 bg-white/[0.04] p-4"
                            >
                                <dt
                                    class="font-display text-sm tracking-wide text-[#f5b24f] uppercase"
                                >
                                    {{ course.title }}
                                </dt>
                                <dd
                                    class="m-0 mt-1.5 text-[0.9375rem] leading-relaxed text-[#cfc6b6]"
                                >
                                    {{ course.note }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="grid content-start gap-5">
                        <figure class="m-0">
                            <div
                                class="overflow-hidden rounded-lg border border-white/10"
                            >
                                <ExhibitImage
                                    src="/images/food-stol-sm.jpg"
                                    srcset="/images/food-stol-sm.jpg 960w, /images/food-stol-lg.jpg 1280w"
                                    sizes="(min-width: 1024px) 24rem, 92vw"
                                    full="/images/food-stol-lg.jpg"
                                    :alt="text('wigilia.photo_caption')"
                                    :caption="text('wigilia.photo_caption')"
                                    :credit="text('wigilia.photo_credit')"
                                    :image-class="'block h-auto w-full object-cover'"
                                />
                            </div>
                            <figcaption
                                class="mt-2 text-[0.8125rem] leading-snug text-[#b0a795]"
                            >
                                {{ text('wigilia.photo_caption') }}
                            </figcaption>
                        </figure>

                        <figure class="m-0">
                            <div
                                class="overflow-hidden rounded-lg border border-white/10"
                            >
                                <ExhibitImage
                                    src="/images/food-oplatek-sm.jpg"
                                    srcset="/images/food-oplatek-sm.jpg 960w, /images/food-oplatek-lg.jpg 1280w"
                                    sizes="(min-width: 1024px) 24rem, 92vw"
                                    full="/images/food-oplatek-lg.jpg"
                                    :alt="text('wigilia.wafer_caption')"
                                    :caption="text('wigilia.wafer_caption')"
                                    :credit="text('wigilia.wafer_credit')"
                                    :image-class="'block h-auto w-full object-cover'"
                                />
                            </div>
                            <figcaption
                                class="mt-2 text-[0.8125rem] leading-snug text-[#b0a795]"
                            >
                                {{ text('wigilia.wafer_caption') }}
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <!-- Easter: the other meal the whole family turns up for -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <div
                    class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,24rem)] lg:gap-14"
                >
                    <div>
                        <p
                            class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                        >
                            {{ text('easter.label') }}
                        </p>
                        <h2
                            class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                        >
                            {{ text('easter.title') }}
                        </h2>
                        <p
                            class="mt-5 max-w-[62ch] text-[1.0625rem] leading-relaxed text-[#3b352d]"
                        >
                            {{ text('easter.body_1') }}
                        </p>
                        <p
                            class="mt-4 max-w-[62ch] leading-relaxed text-[#6e6459]"
                        >
                            {{ text('easter.body_2') }}
                        </p>

                        <dl class="mt-8 grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="(dish, index) in easterTable"
                                :key="dish.title"
                                class="rounded-lg border-l-[4px] bg-white p-4 shadow-[0_1px_2px_rgba(34,30,25,.06)]"
                                :style="{ borderLeftColor: chip(index) }"
                            >
                                <dt
                                    class="font-display text-sm tracking-wide uppercase"
                                    :style="{ color: chip(index) }"
                                >
                                    {{ dish.title }}
                                </dt>
                                <dd
                                    class="m-0 mt-1.5 text-[0.9375rem] leading-relaxed text-[#4a443c]"
                                >
                                    {{ dish.note }}
                                </dd>
                            </div>
                        </dl>

                        <p
                            class="mt-6 rounded-lg p-5 leading-relaxed text-white"
                            :style="{ backgroundColor: chip(2) }"
                        >
                            {{ text('easter.monday') }}
                        </p>
                    </div>

                    <div class="grid content-start gap-5">
                        <figure class="m-0">
                            <div class="overflow-hidden rounded-lg">
                                <ExhibitImage
                                    src="/images/food-wielkanoc-sm.jpg"
                                    srcset="/images/food-wielkanoc-sm.jpg 960w, /images/food-wielkanoc-lg.jpg 1280w"
                                    sizes="(min-width: 1024px) 24rem, 92vw"
                                    full="/images/food-wielkanoc-lg.jpg"
                                    :alt="text('easter.photo_caption')"
                                    :caption="text('easter.photo_caption')"
                                    :credit="text('easter.photo_credit')"
                                    :image-class="'block h-auto w-full object-cover'"
                                />
                            </div>
                            <figcaption
                                class="mt-2 text-[0.8125rem] leading-snug text-[#6e6459]"
                            >
                                {{ text('easter.photo_caption') }}
                            </figcaption>
                        </figure>

                        <figure class="m-0">
                            <div class="overflow-hidden rounded-lg">
                                <ExhibitImage
                                    src="/images/food-swieconka-sm.jpg"
                                    srcset="/images/food-swieconka-sm.jpg 960w, /images/food-swieconka-lg.jpg 1280w"
                                    sizes="(min-width: 1024px) 24rem, 92vw"
                                    full="/images/food-swieconka-lg.jpg"
                                    :alt="text('easter.basket_caption')"
                                    :caption="text('easter.basket_caption')"
                                    :credit="text('easter.basket_credit')"
                                    :image-class="'block h-auto w-full object-cover'"
                                />
                            </div>
                            <figcaption
                                class="mt-2 text-[0.8125rem] leading-snug text-[#6e6459]"
                            >
                                {{ text('easter.basket_caption') }}
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <!-- Every other occasion that puts a family round one table -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#8a5a10] uppercase"
                >
                    {{ text('year.label') }}
                </p>
                <h2
                    class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('year.title') }}
                </h2>

                <ol class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="(moment, index) in gatherings"
                        :key="moment.name"
                        class="rounded-lg p-5"
                        :style="{ backgroundColor: chip(index) }"
                    >
                        <h3
                            class="font-display text-sm tracking-wide text-white uppercase"
                        >
                            {{ moment.name }}
                        </h3>
                        <p
                            class="mt-2 text-[0.9375rem] leading-relaxed text-white"
                        >
                            {{ moment.note }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- What is in the glass -->
        <section>
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#457036] uppercase"
                >
                    {{ text('drinks.label') }}
                </p>
                <h2
                    class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('drinks.title') }}
                </h2>

                <dl class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(drink, index) in drinks"
                        :key="drink.name"
                        class="rounded-lg border-l-[4px] bg-white p-4 shadow-[0_1px_2px_rgba(34,30,25,.06)]"
                        :style="{ borderLeftColor: chip(index) }"
                    >
                        <dt
                            class="font-display text-sm tracking-wide uppercase"
                            :style="{ color: chip(index) }"
                        >
                            {{ drink.name }}
                        </dt>
                        <dd
                            class="m-0 mt-1.5 text-[0.9375rem] leading-relaxed text-[#4a443c]"
                        >
                            {{ drink.note }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- What is true -->
        <section class="bg-[#f3eadb]">
            <div class="mx-auto w-full max-w-[1200px] px-6 py-16 sm:py-20">
                <p
                    class="text-[0.6875rem] font-semibold tracking-[0.28em] text-[#b32347] uppercase"
                >
                    {{ text('myths.label') }}
                </p>
                <h2
                    class="font-display mt-3 text-[clamp(1.75rem,4vw,3rem)] leading-[1.05] tracking-tight text-[#221e19] uppercase"
                >
                    {{ text('myths.title') }}
                </h2>

                <div class="mt-7 grid gap-5 lg:grid-cols-3">
                    <div
                        v-for="(myth, index) in myths"
                        :key="myth.title"
                        class="rounded-lg bg-white p-5 shadow-[0_2px_10px_rgba(34,30,25,.09)]"
                    >
                        <span
                            class="inline-block rounded-full px-2.5 py-1 text-[0.625rem] font-semibold tracking-[0.18em] text-white uppercase"
                            :style="{ backgroundColor: chip(index) }"
                        >
                            {{ myth.tag }}
                        </span>
                        <h3
                            class="font-display mt-2.5 text-base tracking-wide text-[#221e19] uppercase"
                        >
                            {{ myth.title }}
                        </h3>
                        <p
                            class="mt-2 text-[0.9375rem] leading-relaxed text-[#4a443c]"
                        >
                            {{ myth.note }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sources and the way back, on the site's own ground again -->
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

        <RecipeDialog
            :dish="openDish === null ? null : (dishes[openDish] ?? null)"
            :accent="chip(openDish ?? 0)"
            @close="openDish = null"
        />
    </main>
</template>
