<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The campaign of 1410: the Polish army crosses the Vistula at Czerwinsk, the
 * Lithuanians come in from the east, and the joined army marches north into
 * the Order's Prussia. Drawn on an equirectangular extent of 14.0-26.3 E and
 * 51.6-56.9 N, so every dot sits on a real place:
 * x = (lon - 14) * 80, y = (56.9 - lat) * 128.4. Today's Poland is drawn in
 * for orientation only; the areas are soft because the frontiers moved.
 */
const areas = [
    { key: 'order', cx: 450, cy: 365, rx: 190, ry: 85, lead: true },
    { key: 'crown', cx: 400, cy: 600, rx: 150, ry: 120, lead: false },
    { key: 'lithuania', cx: 880, cy: 300, rx: 170, ry: 150, lead: false },
    { key: 'samogitia', cx: 664, cy: 170, rx: 80, ry: 55, lead: false },
] as const;

/** Real places: Malbork, Grunwald, Czerwinsk, Torun, Vilnius. */
const towns = [
    { key: 'malbork', x: 402, y: 367, lx: 388, ly: 358, anchor: 'end' },
    { key: 'grunwald', x: 490, y: 438, lx: 506, ly: 448, anchor: 'start' },
    { key: 'czerwinsk', x: 505, y: 578, lx: 489, ly: 586, anchor: 'end' },
    { key: 'torun', x: 368, y: 500, lx: 352, ly: 510, anchor: 'end' },
    { key: 'vilnius', x: 902, y: 284, lx: 902, ly: 262, anchor: 'middle' },
] as const;

const labels = [
    { key: 'order', x: 560, y: 336, anchor: 'start' },
    { key: 'crown', x: 250, y: 650, anchor: 'middle' },
    { key: 'lithuania', x: 900, y: 440, anchor: 'middle' },
    { key: 'samogitia', x: 664, y: 180, anchor: 'middle' },
] as const;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="0 0 984 680"
            class="w-full"
            role="img"
            :aria-label="t('grunwald.map.alt')"
        >
            <defs>
                <marker
                    id="grunwald-arrow"
                    viewBox="0 0 10 10"
                    refX="8"
                    refY="5"
                    markerWidth="6"
                    markerHeight="6"
                    orient="auto-start-reverse"
                >
                    <path d="M0,0 L10,5 L0,10 z" fill="context-stroke" />
                </marker>
                <clipPath id="grunwald-frame">
                    <rect x="0" y="0" width="984" height="680" />
                </clipPath>
            </defs>

            <g clip-path="url(#grunwald-frame)">
                <ellipse
                    v-for="area in areas"
                    :key="area.key"
                    :cx="area.cx"
                    :cy="area.cy"
                    :rx="area.rx"
                    :ry="area.ry"
                    :class="area.lead ? 'fill-amber' : 'fill-bone-muted'"
                    :opacity="area.lead ? 0.2 : 0.09"
                />

                <path
                    :d="polandOutline"
                    transform="translate(9.6 264.5) scale(0.8024)"
                    fill="none"
                    stroke="currentColor"
                    class="text-bone-muted"
                    stroke-width="4"
                    stroke-dasharray="10 8"
                    stroke-linejoin="round"
                    opacity="0.6"
                />
            </g>

            <!-- Samogitia is what stood between the Order and its Livonian branch -->
            <path
                d="M700,120 L730,40"
                fill="none"
                class="stroke-bone-muted"
                stroke-width="3"
                stroke-dasharray="8 6"
                marker-end="url(#grunwald-arrow)"
            />
            <text
                x="744"
                y="46"
                class="fill-bone-muted font-display"
                font-size="26"
                letter-spacing="1"
            >
                {{ t('grunwald.map.labels.livonia') }}
            </text>

            <!-- The Polish army, from the south to the bridge -->
            <path
                d="M470,676 Q482,630 500,594"
                fill="none"
                class="stroke-amber"
                stroke-width="5"
                marker-end="url(#grunwald-arrow)"
            />

            <!-- The Lithuanians, from Vilnius to the bridge -->
            <path
                d="M894,296 Q740,540 520,580"
                fill="none"
                class="stroke-bone-muted"
                stroke-width="4"
                stroke-dasharray="12 8"
                marker-end="url(#grunwald-arrow)"
            />

            <!-- Together, north to the battle -->
            <path
                d="M504,566 L493,458"
                fill="none"
                class="stroke-amber"
                stroke-width="6"
                marker-end="url(#grunwald-arrow)"
            />

            <!-- And on to Malbork, which they could not take -->
            <path
                d="M478,430 Q440,405 414,378"
                fill="none"
                class="stroke-amber"
                stroke-width="3"
                stroke-dasharray="8 6"
                marker-end="url(#grunwald-arrow)"
            />

            <circle
                cx="490"
                cy="438"
                r="16"
                fill="none"
                class="stroke-amber"
                stroke-width="3"
            />

            <circle
                v-for="town in towns"
                :key="town.key"
                :cx="town.x"
                :cy="town.y"
                r="7"
                :class="town.key === 'grunwald' ? 'fill-amber' : 'fill-bone'"
            />

            <text
                v-for="town in towns"
                :key="`label-${town.key}`"
                :x="town.lx"
                :y="town.ly"
                :text-anchor="town.anchor"
                :class="town.key === 'grunwald' ? 'fill-amber' : 'fill-bone'"
                class="font-display"
                font-size="28"
                letter-spacing="1"
            >
                {{ t(`grunwald.map.labels.${town.key}`) }}
            </text>

            <text
                v-for="label in labels"
                :key="`area-${label.key}`"
                :x="label.x"
                :y="label.y"
                :text-anchor="label.anchor"
                class="fill-bone-muted font-display"
                font-size="30"
                letter-spacing="2"
            >
                {{ t(`grunwald.map.labels.${label.key}`) }}
            </text>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t('grunwald.map.caption') }}
        </figcaption>
    </figure>
</template>
