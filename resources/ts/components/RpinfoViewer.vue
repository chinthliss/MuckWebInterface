<script setup lang="ts">

import Callout from "./Callout.vue";

const category = defineModel('category', {type: String, required: true});
const item = defineModel('item', {type: String, required: true});
import {capitalOnEveryWord} from "../formatting";
</script>

<template>
    <h5>{{ capitalOnEveryWord(item) }} ({{ capitalOnEveryWord(category) }})</h5>
    <callout>This functionality doesn't exist yet. This is just a place holder.</callout>
</template>

<style scoped>

</style>

