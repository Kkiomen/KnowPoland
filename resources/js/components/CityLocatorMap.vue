<script setup lang="ts">
import { computed } from 'vue';

import { polandOutline } from '@/poland-outline';

/**
 * Where a city sits in the country. No published map answers "where is this
 * place" with the place picked out, so this is the case the project rules
 * allow a drawn schematic for - and the pin is still placed from the city's
 * real coordinates rather than by eye, on the projection the shared outline
 * documents: x = (lon - 14.12) * 99.7, y = (54.84 - lat) * 160.
 */
const props = defineProps<{
    /** Degrees east, positive. */
    lon: number;
    /** Degrees north, positive. */
    lat: number;
    /** The city's name, drawn beside the pin. */
    label: string;
    /** Accessible description of the whole map. */
    alt: string;
}>();

const pin = computed(() => ({
    x: (props.lon - 14.12) * 99.7,
    y: (54.84 - props.lat) * 160,
}));

/** Keep the name inside the box when the city sits near the eastern edge. */
const labelAnchor = computed(() => (pin.value.x > 700 ? 'end' : 'start'));
const labelX = computed(() =>
    labelAnchor.value === 'end' ? pin.value.x - 46 : pin.value.x + 46,
);
</script>

<template>
    <svg
        viewBox="0 0 1000 935"
        role="img"
        :aria-label="alt"
        class="h-auto w-full"
    >
        <path
            :d="polandOutline"
            class="fill-bone/[0.06] stroke-bone/45"
            stroke-width="4"
        />
        <circle
            :cx="pin.x"
            :cy="pin.y"
            r="34"
            fill="none"
            class="stroke-flag-red"
            stroke-width="3"
            opacity="0.5"
        />
        <circle :cx="pin.x" :cy="pin.y" r="13" class="fill-flag-red" />
        <text
            :x="labelX"
            :y="pin.y + 12"
            :text-anchor="labelAnchor"
            class="font-display fill-bone"
            font-size="34"
            letter-spacing="1.4"
        >
            {{ label }}
        </text>
    </svg>
</template>
