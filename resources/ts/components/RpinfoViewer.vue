<script setup lang="ts">

import Callout from "./Callout.vue";

const category = defineModel<string>('category', {required: true});
const item = defineModel<string>('item', {required: true});
import {capitalOnEveryWord} from "../formatting";
</script>

<template>
    <h5>{{ capitalOnEveryWord(item) }} ({{ capitalOnEveryWord(category) }})</h5>
    <callout>This functionality doesn't exist yet. This is just a place holder.</callout>
</template>

<style scoped>

</style>

