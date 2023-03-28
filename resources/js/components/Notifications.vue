<template>
    <div class="container">

        <h1>Notifications</h1>

        <div v-if="initialLoad">Loading...</div>
    </div>

</template>

<script setup>
import {ref} from 'vue';
import DataTable from 'datatables.net-vue3';
import ModalConfirmation from './ModalConfirmation.vue';
import {carbonToString} from "../formatting";

const initialLoad = ref(true);

</script>

<style scoped>

</style>
