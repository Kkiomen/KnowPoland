<script setup lang="ts">
/**
 * The coat of arms of Poland, as defined by the 1980 act on the emblem,
 * colours and anthem. State symbols are excluded from copyright under art. 4
 * of the Polish copyright act; the vector rendition comes from Wikimedia
 * Commons (File:Herb Polski.svg).
 *
 * The emblem must be reproduced faithfully: never recolour it, stretch it,
 * rotate it, crop it or fade it into a background. Size it and leave it alone.
 */
defineProps<{
    /** Accessible name. Omit only when a caption already names the emblem. */
    label?: string;
}>();
</script>

<template>
    <picture>
        <source type="image/avif" srcset="/images/godlo-polski.svg" />
        <img
            src="/images/godlo-polski.svg"
            :alt="label ?? ''"
            :aria-hidden="label ? undefined : 'true'"
            decoding="async"
            width="3158"
            height="3716"
        />
    </picture>
</template>
