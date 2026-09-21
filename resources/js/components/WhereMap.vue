<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * The medieval sites a visitor can reach, grouped into the two clusters the
 * article is built around. Drawn in the same box as the Poland outline, so
 * every dot sits on a real place: x = (lon - 14.12) * 99.7,
 * y = (54.84 - lat) * 160. Lednica sits too close to Gniezno and Wieliczka
 * to Krakow to get dots of their own, so they share a label.
 */
const clusters = [
    { key: 'began', cx: 350, cy: 372, rx: 115, ry: 62, lx: 350, ly: 278 },
    { key: 'kingdom', cx: 578, cy: 742, rx: 125, ry: 62, lx: 578, ly: 858 },
] as const;

const sites = [
    { key: 'poznan', x: 280, y: 389, lx: 280, ly: 424, anchor: 'middle' },
    { key: 'gniezno', x: 347, y: 370, lx: 333, ly: 352, anchor: 'end' },
    { key: 'kruszwica', x: 420, y: 346, lx: 430, ly: 318, anchor: 'start' },
    { key: 'plock', x: 556, y: 366, lx: 570, ly: 360, anchor: 'start' },
    { key: 'tum', x: 512, y: 446, lx: 526, ly: 456, anchor: 'start' },
    { key: 'krakow', x: 580, y: 765, lx: 580, ly: 800, anchor: 'middle' },
    { key: 'bedzin', x: 500, y: 722, lx: 486, ly: 716, anchor: 'end' },
    { key: 'wislica', x: 653, y: 718, lx: 667, ly: 712, anchor: 'start' },
] as const;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="0 0 1000 935"
            class="w-full"
            role="img"
            :aria-label="t('where.map.alt')"
        >
            <path
                :d="polandOutline"
                fill="none"
                stroke="currentColor"
                class="text-bone-muted"
                stroke-width="3"
                stroke-dasharray="10 8"
                stroke-linejoin="round"
                opacity="0.6"
            />

            <ellipse
                v-for="cluster in clusters"
                :key="cluster.key"
                :cx="cluster.cx"
                :cy="cluster.cy"
                :rx="cluster.rx"
                :ry="cluster.ry"
                class="fill-amber"
                opacity="0.18"
            />

            <!-- The train between the two bases -->
            <path
                d="M296,410 Q420,600 566,748"
                fill="none"
                class="stroke-amber"
                stroke-width="4"
                stroke-dasharray="12 8"
                opacity="0.8"
            />
            <text
                x="452"
                y="590"
                text-anchor="start"
                class="fill-amber font-display"
                font-size="28"
                letter-spacing="1"
            >
                {{ t('where.map.labels.train') }}
            </text>

            <circle
                v-for="site in sites"
                :key="site.key"
                :cx="site.x"
                :cy="site.y"
                r="8"
                class="fill-bone"
            />

            <text
                v-for="site in sites"
                :key="`label-${site.key}`"
                :x="site.lx"
                :y="site.ly"
                :text-anchor="site.anchor"
                class="fill-bone font-display"
                font-size="28"
                letter-spacing="1"
            >
                {{ t(`where.map.labels.${site.key}`) }}
            </text>

            <text
                v-for="cluster in clusters"
                :key="`cluster-${cluster.key}`"
                :x="cluster.lx"
                :y="cluster.ly"
                text-anchor="middle"
                class="fill-amber font-display"
                font-size="30"
                letter-spacing="2"
            >
                {{ t(`where.map.labels.${cluster.key}`) }}
            </text>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t('where.map.caption') }}
        </figcaption>
    </figure>
</template>
