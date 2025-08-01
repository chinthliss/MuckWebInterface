<!--
    Simple widget to highlight an area (and to stop being inconsistent about it in using it)
-->
<script setup lang="ts">

import {computed} from "vue";

const props = defineProps<{
    type?: 'warning' | 'danger' | 'info'
}>();

const classObject = computed(() => ({
    'text-warning-emphasis': props.type == 'warning' || !props.type,
    'border-warning': props.type == 'warning' || !props.type,
    'bg-warning-subtle': props.type == 'warning' || !props.type,

    'text-info-emphasis': props.type == 'info',
    'border-info': props.type == 'info',
    'bg-info-subtle': props.type == 'info',

    'text-danger-emphasis': props.type == 'danger',
    'border-danger': props.type == 'danger',
    'bg-danger-subtle': props.type == 'danger'

}))

</script>

<template>
    <div :class="classObject" class="border-start border-end border-5 p-2 rounded-4">
        <slot>
            No content has been provided for this callout.
        </slot>
    </div>
</template>

<style scoped>

</style>
