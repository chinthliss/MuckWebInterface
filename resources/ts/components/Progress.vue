<script setup lang="ts">
const props = defineProps<{
    percentage: number
    alt: string
}>();
</script>

<template>
    <div class="progress" role="progressbar" aria-label="{{ props.alt }}"
         aria-valuemin="0" aria-valuemax="100" :aria-valuenow="props.percentage"
    >
        <div v-if="props.percentage > 0" class="progress-bar" :style="'width: ' + props.percentage + '%'">{{ Math.floor(props.percentage) }}%</div>
    </div>
</template>

<style scoped>

</style>
