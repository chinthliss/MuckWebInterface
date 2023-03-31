<template>
    <div class="container">

        <h1>Notifications</h1>

        <div v-if="loadingNotifications" class="text-center">
            <span class="spinner-border text-primary me-2" role="status" aria-hidden="true"></span>
            <div>Loading..</div>
        </div>

        <div v-if="!initialLoading">

            <div class="d-flex justify-content-center mb-2">
                <button class="btn btn-warning" @click="deleteAllNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>Delete All Notifications
                </button>
            </div>

            <DataTable class="table table-dark table-hover table-striped table-bordered"
                       :options="notificationTableConfiguration" :data="notifications">
                <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th scope="col">Character</th>
                    <th scope="col">Date</th>
                    <th scope="col">Message</th>
                </tr>
                </thead>
            </DataTable>

            <div class="d-flex justify-content-center mt-2">
                <button class="btn btn-warning" @click="deleteAllNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>Delete All Notifications
                </button>
            </div>

        </div>
    </div>

</template>

<script setup>

/**
 * @typedef {object} Notification
 * @property {int} id
 * @property {string} created_at
 * @property {string} read_at
 * @property {string} character
 * @property {string} message
 */

import {ref, onMounted} from 'vue';
import DataTable from 'datatables.net-vue3';
import ModalConfirmation from './ModalConfirmation.vue';
// import {carbonToString} from "../formatting";

const props = defineProps({
    apiUrl: {type: String, required: true}
});


const loadingNotifications = ref(true);
const initialLoading = ref(true);

/** @type {Ref<Notification[]>} */
const notifications = ref([]);

const notificationTableConfiguration = {
    columns: [
        {data: 'id', sortable: false},
        {data: 'id', sortable: false},
        {data: 'character'},
        {data: 'created_at'},
        {data: 'message'}
    ],
    order: [[3, 'asc']],
    language: {
        "emptyTable": "You have no notifications."
    },
    paging: false,
    info: false,
    searching: true
};

const refreshNotifications = () => {
    loadingNotifications.value = true;
    axios.get(props.apiUrl)
        .then(response => {
            notifications.value = response.data;
        })
        .finally(() => {
            loadingNotifications.value = false;
            initialLoading.value = false;
        });

};

onMounted(() => {
    refreshNotifications();
});

</script>

<style scoped>

</style>
