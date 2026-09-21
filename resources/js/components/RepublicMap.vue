<script setup lang="ts">
import { t } from '@/i18n';
import { polandOutline } from '@/poland-outline';

/**
 * Poland as its borders stood in 1922, after the wars and the plebiscites,
 * with the three old partitions shaded inside it so the reader can see what
 * the new state was stitched together from. A sibling of PartitionMap: same
 * projection, same colours for the three powers, and every label from the
 * `map.labels` branch of the page's translation group.
 *
 * Drawn on an equirectangular extent where x = (lon - 14) * 46 and
 * y = (58 - lat) * 73.8, the same as the other maps, so today's Poland can be
 * laid over it. The borders are simplified: towns sit on their real
 * coordinates, frontiers only roughly follow the lines they ran along.
 */
type Power = 'russia' | 'prussia' | 'austria';

type Label = {
    key: string;
    lon: number;
    lat: number;
    anchor?: 'start' | 'middle' | 'end';
    size?: number;
};

type Town = {
    key: string;
    lon: number;
    lat: number;
    dx: number;
    dy: number;
    anchor: 'start' | 'middle' | 'end';
};

const props = defineProps<{
    /** Translation group holding `map.alt`, `map.caption` and `map.labels`. */
    group: string;
}>();

const x = (lon: number): number => (lon - 14) * 46;
const y = (lat: number): number => (58 - lat) * 73.8;

const path = (points: [number, number][]): string =>
    points
        .map(
            ([lon, lat], index) =>
                `${index === 0 ? 'M' : 'L'}${x(lon).toFixed(1)},${y(lat).toFixed(1)}`,
        )
        .join(' ') + ' Z';

/** The Second Republic after the Silesian division of 1922. */
const republic: [number, number][] = [
    [17.95, 54.83],
    [18.35, 54.82],
    [18.55, 54.6],
    [18.5, 54.45],
    [18.4, 54.32],
    [18.55, 54.15],
    [18.8, 54.02],
    [18.85, 53.6],
    [19.2, 53.45],
    [19.7, 53.3],
    [20.2, 53.2],
    [20.7, 53.2],
    [21.6, 53.3],
    [22.5, 53.5],
    [22.8, 54.1],
    [22.8, 54.4],
    [23.4, 54.25],
    [23.95, 54.0],
    [24.5, 54.2],
    [24.85, 54.55],
    [25.0, 54.95],
    [25.6, 55.3],
    [26.2, 55.65],
    [26.7, 55.75],
    [27.3, 55.85],
    [28.2, 55.6],
    [27.8, 55.1],
    [27.3, 54.6],
    [27.15, 54.0],
    [26.95, 53.4],
    [27.15, 52.8],
    [27.3, 52.2],
    [27.4, 51.6],
    [27.2, 51.0],
    [27.3, 50.6],
    [26.9, 50.2],
    [26.3, 49.8],
    [26.15, 49.2],
    [26.35, 48.55],
    [25.9, 48.55],
    [25.35, 48.45],
    [25.05, 48.2],
    [24.6, 47.95],
    [24.1, 48.25],
    [23.5, 48.75],
    [22.6, 49.1],
    [21.5, 49.4],
    [20.9, 49.3],
    [20.0, 49.2],
    [19.4, 49.5],
    [19.0, 49.45],
    [18.6, 49.72],
    [18.3, 49.95],
    [18.2, 50.05],
    [18.45, 50.3],
    [18.7, 50.45],
    [19.0, 50.55],
    [19.15, 50.55],
    [19.0, 50.75],
    [18.7, 50.95],
    [18.55, 51.2],
    [18.2, 51.35],
    [17.5, 51.35],
    [17.3, 51.6],
    [16.8, 51.65],
    [16.3, 51.8],
    [16.0, 52.1],
    [15.85, 52.3],
    [16.0, 52.6],
    [16.3, 52.8],
    [16.6, 52.95],
    [17.2, 53.05],
    [17.3, 53.25],
    [17.45, 53.5],
    [17.55, 53.9],
    [17.7, 54.1],
    [17.75, 54.4],
    [17.85, 54.6],
];

/** The Free City of Danzig, on the coast between Poland and East Prussia. */
const danzig: [number, number][] = [
    [18.55, 54.6],
    [18.95, 54.45],
    [19.3, 54.35],
    [19.1, 54.1],
    [18.8, 54.02],
    [18.55, 54.15],
    [18.4, 54.32],
    [18.5, 54.45],
];

/**
 * The three partitions as they stood before 1914, the same outlines the
 * partition pages draw, clipped to the borders of 1922 when rendered.
 */
const partitions: { power: Power; points: [number, number][] }[] = [
    {
        power: 'russia',
        points: [
            [18.55, 52.8],
            [18.75, 52.9],
            [19.05, 53.12],
            [19.45, 53.2],
            [20.15, 53.2],
            [20.7, 53.2],
            [21.6, 53.3],
            [22.5, 53.5],
            [22.8, 54.1],
            [22.8, 54.4],
            [22.9, 54.85],
            [22.85, 55.05],
            [23.5, 55.0],
            [25.0, 56.0],
            [28.5, 56.0],
            [29.0, 52.0],
            [28.5, 49.0],
            [26.25, 48.6],
            [26.1, 49.3],
            [25.9, 49.8],
            [25.2, 50.15],
            [24.4, 50.3],
            [24.0, 50.6],
            [23.4, 50.45],
            [22.7, 50.45],
            [22.2, 50.55],
            [21.83, 50.68],
            [21.2, 50.35],
            [20.5, 50.2],
            [20.1, 50.18],
            [19.85, 50.28],
            [19.4, 50.3],
            [19.2, 50.3],
            [19.15, 50.55],
            [19.0, 50.75],
            [18.7, 50.95],
            [18.55, 51.2],
            [18.2, 51.35],
            [18.1, 51.7],
            [17.9, 51.95],
            [17.75, 52.15],
            [17.9, 52.35],
            [18.3, 52.65],
        ],
    },
    {
        power: 'prussia',
        points: [
            [15.5, 52.0],
            [15.5, 55.0],
            [19.3, 54.35],
            [19.6, 53.9],
            [19.7, 53.45],
            [19.45, 53.2],
            [19.05, 53.12],
            [18.75, 52.9],
            [18.55, 52.8],
            [18.3, 52.65],
            [17.9, 52.35],
            [17.75, 52.15],
            [17.9, 51.95],
            [18.1, 51.7],
            [18.2, 51.35],
            [17.5, 51.2],
            [16.0, 51.5],
        ],
    },
    {
        power: 'austria',
        points: [
            [19.2, 50.3],
            [19.4, 50.3],
            [19.85, 50.28],
            [20.1, 50.18],
            [20.5, 50.2],
            [21.2, 50.35],
            [21.83, 50.68],
            [22.2, 50.55],
            [22.7, 50.45],
            [23.4, 50.45],
            [24.0, 50.6],
            [24.4, 50.3],
            [25.2, 50.15],
            [25.9, 49.8],
            [26.1, 49.3],
            [26.25, 48.6],
            [25.9, 48.4],
            [25.3, 48.2],
            [24.9, 47.95],
            [24.4, 48.0],
            [23.5, 48.6],
            [22.6, 49.1],
            [21.5, 49.4],
            [20.9, 49.3],
            [20.0, 49.2],
            [19.4, 49.5],
            [19.1, 49.6],
            [18.9, 49.9],
            [19.1, 50.05],
        ],
    },
];

const fills: Record<Power, string> = {
    russia: 'fill-flag-red',
    prussia: 'fill-bone',
    austria: 'fill-amber',
};

const labelColours: Record<Power, string> = {
    russia: 'fill-[#ff8a9c]',
    prussia: 'fill-bone',
    austria: 'fill-amber',
};

/** Where each partition's name sits inside the new state. */
const partitionLabels: { power: Power; lon: number; lat: number }[] = [
    { power: 'russia', lon: 23.6, lat: 52.55 },
    { power: 'prussia', lon: 17.2, lat: 53.75 },
    { power: 'austria', lon: 23.6, lat: 49.35 },
];

/** The neighbours of 1922, named outside the border. */
const areaLabels: Label[] = [
    { key: 'sea', lon: 17.6, lat: 56.3 },
    { key: 'germany', lon: 14.9, lat: 51.2, anchor: 'start' },
    { key: 'east_prussia', lon: 21.4, lat: 54.75, size: 20 },
    { key: 'lithuania', lon: 23.6, lat: 55.55 },
    { key: 'soviet', lon: 30.6, lat: 53.2 },
    { key: 'czechoslovakia', lon: 19.4, lat: 48.95 },
    { key: 'romania', lon: 27.2, lat: 47.75 },
    { key: 'silesia', lon: 17.6, lat: 50.0, anchor: 'end', size: 18 },
];

const towns: Town[] = [
    { key: 'warsaw', lon: 21.01, lat: 52.23, dx: 14, dy: 8, anchor: 'start' },
    { key: 'krakow', lon: 19.94, lat: 50.06, dx: 0, dy: 34, anchor: 'middle' },
    { key: 'poznan', lon: 16.93, lat: 52.41, dx: 0, dy: 34, anchor: 'middle' },
    { key: 'lwow', lon: 24.03, lat: 49.84, dx: 14, dy: 8, anchor: 'start' },
    {
        key: 'vilnius',
        lon: 25.28,
        lat: 54.69,
        dx: 14,
        dy: 8,
        anchor: 'start',
    },
    { key: 'gdynia', lon: 18.53, lat: 54.52, dx: -14, dy: 0, anchor: 'end' },
    { key: 'danzig', lon: 18.65, lat: 54.35, dx: 14, dy: 26, anchor: 'start' },
    {
        key: 'katowice',
        lon: 19.02,
        lat: 50.26,
        dx: -14,
        dy: -12,
        anchor: 'end',
    },
];

const republicPath = path(republic);
const danzigPath = path(danzig);
const partitionPaths = partitions.map((partition) => ({
    ...partition,
    d: path(partition.points),
}));

const clipId = `${props.group}-republic-clip`;
</script>

<template>
    <figure class="w-full">
        <svg
            viewBox="30 0 870 800"
            class="w-full"
            role="img"
            :aria-label="t(`${group}.map.alt`)"
        >
            <defs>
                <clipPath :id="clipId">
                    <path :d="republicPath" />
                </clipPath>
                <clipPath :id="`${group}-republic-frame`">
                    <rect x="30" y="0" width="870" height="800" />
                </clipPath>
            </defs>

            <g :clip-path="`url(#${group}-republic-frame)`">
                <path
                    :d="republicPath"
                    class="fill-bone-muted"
                    fill-opacity="0.14"
                />

                <g :clip-path="`url(#${clipId})`">
                    <path
                        v-for="partition in partitionPaths"
                        :key="partition.power"
                        :d="partition.d"
                        :class="fills[partition.power]"
                        fill-opacity="0.42"
                        stroke="currentColor"
                        class="text-ink"
                        stroke-width="2"
                        stroke-linejoin="round"
                    />
                </g>

                <path
                    :d="danzigPath"
                    class="fill-bone-muted stroke-bone-muted"
                    fill-opacity="0.3"
                    stroke-width="2"
                    stroke-linejoin="round"
                />

                <path
                    :d="republicPath"
                    fill="none"
                    class="stroke-amber"
                    stroke-width="4"
                    stroke-linejoin="round"
                />

                <path
                    :d="polandOutline"
                    transform="translate(5.52 233.2) scale(0.4614)"
                    fill="none"
                    stroke="currentColor"
                    class="text-bone-muted"
                    stroke-width="7"
                    stroke-dasharray="18 14"
                    stroke-linejoin="round"
                    opacity="0.65"
                />
            </g>

            <text
                v-for="label in areaLabels"
                :key="`area-${label.key}`"
                :x="x(label.lon)"
                :y="y(label.lat)"
                :text-anchor="label.anchor ?? 'middle'"
                class="fill-bone-muted font-display stroke-ink"
                :font-size="label.size ?? 24"
                letter-spacing="2"
                paint-order="stroke"
                stroke-width="5"
                stroke-linejoin="round"
            >
                {{ t(`${group}.map.labels.${label.key}`) }}
            </text>

            <text
                v-for="label in partitionLabels"
                :key="`partition-${label.power}`"
                :x="x(label.lon)"
                :y="y(label.lat)"
                text-anchor="middle"
                :class="labelColours[label.power]"
                class="font-display stroke-ink"
                font-size="22"
                letter-spacing="1.5"
                paint-order="stroke"
                stroke-width="6"
                stroke-linejoin="round"
            >
                {{ t(`${group}.map.labels.${label.power}`) }}
            </text>

            <g v-for="town in towns" :key="town.key">
                <circle
                    :cx="x(town.lon)"
                    :cy="y(town.lat)"
                    r="7"
                    class="fill-bone"
                />
                <text
                    :x="x(town.lon) + town.dx"
                    :y="y(town.lat) + town.dy"
                    :text-anchor="town.anchor"
                    class="fill-bone font-display stroke-ink"
                    font-size="22"
                    letter-spacing="1"
                    paint-order="stroke"
                    stroke-width="5"
                    stroke-linejoin="round"
                >
                    {{ t(`${group}.map.labels.${town.key}`) }}
                </text>
            </g>
        </svg>

        <figcaption
            class="text-bone-muted/80 mt-3 text-[0.625rem] tracking-[0.16em] uppercase"
        >
            {{ t(`${group}.map.caption`) }}
        </figcaption>
    </figure>
</template>
