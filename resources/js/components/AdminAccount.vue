<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <h1>Account {{ account.id }}</h1>
            </div>
        </div>

    </div>
</template>

<script setup>
/**
 * @typedef {object} Email
 * @property {string} email
 * @property {string} createdAt
 * @property {string} verifiedAt
 * @property {boolean} isPrimary
 */

import {ref} from 'vue';
import {carbonToString} from "../formatting";

const props = defineProps({
    account: {type: Object, required: true}
});

</script>

<style scoped>

</style>
