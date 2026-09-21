<script setup lang="ts">
import CityPage from '@/components/CityPage.vue';

/**
 * Masuria is a region rather than a town, so the pin stands on Mikolajki,
 * roughly the middle of the Great Lakes, and the map caption says so.
 */
</script>

<template>
    <CityPage city="mazury" :lon="21.5675" :lat="53.8022" />
</template>
