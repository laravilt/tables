<script setup lang="ts">
// Test stub: exposes the component name and props it was asked to render.
defineProps<{ component: string; props?: Record<string, any> }>()
</script>

<template>
    <div class="renderer-stub" :data-component="component" :data-props="JSON.stringify(props)" />
</template>
