<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * Mieszko's realm and its neighbours around 970, with the two routes by which
 * Christianity reached this part of Europe. Drawn on an equirectangular
 * extent of 10.5-31.5 E and 47.8-55.6 N, so every dot sits on a real town:
 * x = (lon - 10.5) * 49.85, y = (55.6 - lat) * 80. Today's Poland is drawn
 * in for orientation only; the areas are soft because the frontiers were.
 */
const areas = [
    { key: 'realm', x: 345, y: 270, r: 115, lead: true },
    { key: 'empire', x: 30, y: 330, r: 150, lead: false },
    { key: 'veleti', x: 140, y: 160, r: 85, lead: false },
    { key: 'bohemia', x: 205, y: 450, r: 85, lead: false },
    { key: 'rus', x: 960, y: 370, r: 125, lead: false },
] as const;

/** Real seats: Gniezno, Poznan, Magdeburg, Prague, Kyiv. */
const towns = [
    { x: 354, y: 246 },
    { x: 321, y: 255 },
    { x: 56, y: 278 },
    { x: 195, y: 441 },
    { x: 998, y: 412 },
];

const labels = [
    { key: 'realm', x: 370, y: 236, anchor: 'start' },
    { key: 'empire', x: 70, y: 290, anchor: 'start' },
    { key: 'veleti', x: 140, y: 150, anchor: 'middle' },
    { key: 'bohemia', x: 180, y: 505, anchor: 'middle' },
    { key: 'rus', x: 980, y: 385, anchor: 'end' },
] as const;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="0 0 1047 624"
            class="w-full"
            role="img"
            :aria-label="t('baptism.map.alt')"
        >
            <defs>
                <marker
                    id="baptism-arrow"
                    viewBox="0 0 10 10"
                    refX="8"
                    refY="5"
                    markerWidth="7"
                    markerHeight="7"
                    orient="auto-start-reverse"
                >
                    <path d="M0,0 L10,5 L0,10 z" fill="context-stroke" />
                </marker>
            </defs>

            <circle
                v-for="area in areas"
                :key="area.key"
                :cx="area.x"
                :cy="area.y"
                :r="area.r"
                :class="area.lead ? 'fill-amber' : 'fill-bone-muted'"
                :opacity="area.lead ? 0.2 : 0.09"
            />

            <path
                :d="polandOutline"
                transform="translate(180.5 60.8) scale(0.5)"
                fill="none"
                stroke="currentColor"
                class="text-bone-muted"
                stroke-width="4"
                stroke-dasharray="10 8"
                stroke-linejoin="round"
                opacity="0.6"
            />

            <!-- Latin, from Prague to Poznan -->
            <path
                d="M205,425 Q300,380 318,275"
                fill="none"
                class="stroke-amber"
                stroke-width="4"
                marker-end="url(#baptism-arrow)"
            />
            <text
                x="290"
                y="395"
                class="fill-amber font-display"
                font-size="30"
                letter-spacing="1"
            >
                {{ t('baptism.map.labels.latin') }}
            </text>

            <!-- Byzantine, from the south to Kyiv -->
            <path
                d="M935,618 Q935,500 990,428"
                fill="none"
                class="stroke-bone-muted"
                stroke-width="4"
                stroke-dasharray="12 8"
                marker-end="url(#baptism-arrow)"
            />
            <text
                x="915"
                y="585"
                text-anchor="end"
                class="fill-bone-muted font-display"
                font-size="30"
                letter-spacing="1"
            >
                {{ t('baptism.map.labels.greek') }}
            </text>

            <circle
                v-for="(town, index) in towns"
                :key="index"
                :cx="town.x"
                :cy="town.y"
                r="7"
                :class="index < 2 ? 'fill-amber' : 'fill-bone'"
            />

            <text
                v-for="label in labels"
                :key="label.key"
                :x="label.x"
                :y="label.y"
                :text-anchor="label.anchor"
                :class="label.key === 'realm' ? 'fill-amber' : 'fill-bone'"
                class="font-display"
                font-size="40"
                letter-spacing="1"
            >
                {{ t(`baptism.map.labels.${label.key}`) }}
            </text>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t('baptism.map.caption') }}
        </figcaption>
    </figure>
</template>
