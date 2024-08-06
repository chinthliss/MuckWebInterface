<script setup lang="ts">
const props = defineProps<{
    percentage: number
    alt: string
}>();
</script>

<template>
    <div class="progress" role="progressbar" aria-label="{{ alt }}"
         aria-valuemin="0" aria-valuemax="100" :aria-valuenow="percentage"
    >
        <div v-if="percentage > 0" class="progress-bar" :style="'width: ' + percentage + '%'">{{ percentage }}%</div>
    </div>
</template>

<style scoped>

</style>
