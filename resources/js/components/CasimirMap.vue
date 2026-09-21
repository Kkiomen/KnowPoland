<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The kingdom Casimir inherited in 1333 and what it had gained by 1370,
 * with the two lands left outside it: Silesia and the Teutonic Order's
 * Prussia. Drawn on an equirectangular extent of 14.0-26.3 E and
 * 48.9-55.0 N, so every dot sits on a real place:
 * x = (lon - 14) * 80, y = (55.0 - lat) * 128.4. Today's Poland is drawn in
 * for orientation only; the areas are soft because the frontiers moved.
 */
const areas = [
    // the kingdom of 1333: Greater Poland, the middle lands, Lesser Poland
    { key: 'greater', cx: 272, cy: 360, rx: 105, ry: 85, kind: 'core' },
    { key: 'middle', cx: 400, cy: 424, rx: 70, ry: 55, kind: 'core' },
    { key: 'lesser', cx: 520, cy: 565, rx: 125, ry: 70, kind: 'core' },
    // gained: Kuyavia back from the Order in 1343, Red Ruthenia from 1340
    { key: 'kuyavia', cx: 368, cy: 295, rx: 45, ry: 35, kind: 'gain' },
    { key: 'ruthenia', cx: 784, cy: 655, rx: 120, ry: 80, kind: 'gain' },
    // left outside
    { key: 'silesia', cx: 240, cy: 526, rx: 85, ry: 60, kind: 'out' },
    { key: 'order', cx: 464, cy: 128, rx: 200, ry: 75, kind: 'out' },
] as const;

/** Real places: Krakow, Poznan, Lviv, Gdansk, Wroclaw. */
const towns = [
    { key: 'krakow', x: 475, y: 634, lx: 491, ly: 662, anchor: 'start' },
    { key: 'poznan', x: 234, y: 333, lx: 218, ly: 322, anchor: 'end' },
    { key: 'lviv', x: 802, y: 662, lx: 802, ly: 700, anchor: 'middle' },
    { key: 'gdansk', x: 372, y: 83, lx: 356, ly: 78, anchor: 'end' },
    { key: 'wroclaw', x: 243, y: 499, lx: 227, ly: 488, anchor: 'end' },
] as const;

const labels = [
    { key: 'kingdom', x: 430, y: 470, anchor: 'middle', kind: 'core' },
    { key: 'gained', x: 784, y: 600, anchor: 'middle', kind: 'gain' },
    { key: 'silesia', x: 240, y: 585, anchor: 'middle', kind: 'out' },
    { key: 'order', x: 520, y: 150, anchor: 'start', kind: 'out' },
] as const;

const fill = (kind: string): string =>
    kind === 'out' ? 'fill-bone-muted' : 'fill-amber';

const opacity = (kind: string): number =>
    kind === 'core' ? 0.26 : kind === 'gain' ? 0.12 : 0.09;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="0 0 984 783"
            class="w-full"
            role="img"
            :aria-label="t('casimir.map.alt')"
        >
            <defs>
                <clipPath id="casimir-frame">
                    <rect x="0" y="0" width="984" height="783" />
                </clipPath>
            </defs>

            <g clip-path="url(#casimir-frame)">
                <ellipse
                    v-for="area in areas"
                    :key="area.key"
                    :cx="area.cx"
                    :cy="area.cy"
                    :rx="area.rx"
                    :ry="area.ry"
                    :class="fill(area.kind)"
                    :opacity="opacity(area.kind)"
                />

                <!-- What was gained is outlined, so it reads apart from the core -->
                <ellipse
                    v-for="area in areas.filter((a) => a.kind === 'gain')"
                    :key="`edge-${area.key}`"
                    :cx="area.cx"
                    :cy="area.cy"
                    :rx="area.rx"
                    :ry="area.ry"
                    fill="none"
                    class="stroke-amber"
                    stroke-width="3"
                    stroke-dasharray="10 7"
                    opacity="0.8"
                />

                <path
                    :d="polandOutline"
                    transform="translate(9.6 20.5) scale(0.8024)"
                    fill="none"
                    stroke="currentColor"
                    class="text-bone-muted"
                    stroke-width="4"
                    stroke-dasharray="10 8"
                    stroke-linejoin="round"
                    opacity="0.6"
                />
            </g>

            <circle
                v-for="town in towns"
                :key="town.key"
                :cx="town.x"
                :cy="town.y"
                r="7"
                class="fill-bone"
            />

            <text
                v-for="town in towns"
                :key="`label-${town.key}`"
                :x="town.lx"
                :y="town.ly"
                :text-anchor="town.anchor"
                class="fill-bone font-display"
                font-size="28"
                letter-spacing="1"
            >
                {{ t(`casimir.map.labels.${town.key}`) }}
            </text>

            <text
                v-for="label in labels"
                :key="`area-${label.key}`"
                :x="label.x"
                :y="label.y"
                :text-anchor="label.anchor"
                :class="label.kind === 'out' ? 'fill-bone-muted' : 'fill-amber'"
                class="font-display"
                font-size="30"
                letter-spacing="2"
            >
                {{ t(`casimir.map.labels.${label.key}`) }}
            </text>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t('casimir.map.caption') }}
        </figcaption>
    </figure>
</template>
