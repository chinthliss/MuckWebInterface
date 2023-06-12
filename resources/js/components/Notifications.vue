<template>
    <div class="container">

        <h1>Notifications</h1>

        <div v-if="loadingNotifications" class="text-center">
            <span class="spinner-border text-primary me-2" role="status" aria-hidden="true"></span>
            <div>Loading..</div>
        </div>

        <div v-if="!initialLoading">

            <div class="d-flex justify-content-center mb-2">
                <button class="btn btn-warning" @click="verifyIntentToDeleteAllNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>Delete All Notifications
                </button>
            </div>

            <DataTable class="table table-dark table-hover table-striped table-bordered"
                       :options="notificationTableConfiguration" :data="notifications">
                <thead>
                <tr>
                    <th></th>
                    <th scope="col">Character</th>
                    <th scope="col">Date</th>
                    <th scope="col">Message</th>
                </tr>
                </thead>
            </DataTable>

            <div class="d-flex justify-content-center mt-2">
                <button class="btn btn-warning" @click="verifyIntentToDeleteAllNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>Delete All Notifications
                </button>
            </div>

        </div>

        <ModalConfirmation id="confirm-delete-all-notifications" @yes="deleteAllNotifications"
                           yes-label="Delete All" no-label="Cancel">
            Are you sure you wish to delete all your notifications?
        </ModalConfirmation>

    </div>

</template>

<script setup>

import {ref, onMounted} from 'vue';
import DataTable from 'datatables.net-vue3';
import ModalConfirmation from './ModalConfirmation.vue';
import {carbonToString} from "../formatting";

const props = defineProps({
    apiUrl: {type: String, required: true}
});


const loadingNotifications = ref(true);
const initialLoading = ref(true);

/** @type {Ref<AccountNotification[]>} */
const notifications = ref([]);

let confirmationModal = null;

const renderControlsColumn = (data, type, row) => {
    let controls = ''
    if (row.read_at)
        controls += `<i class="fas fa-envelope-open text-muted"></i>`;
    else
        controls += `<i class="fas fa-envelope text-primary"></i>`;
    controls += `<button class="btn btn-secondary ms-2 notification-delete-button" data-id="${row.id}"><i class="fas fa-trash btn-icon-left"></i>Delete</button>`;
    return controls;
};

const linkButtonsInTable = () => {
    $('.notification-delete-button').click(deleteNotification);
};

const notificationTableConfiguration = {
    columns: [
        {render: renderControlsColumn, sortable: false, className: 'text-nowrap'},
        {data: 'character'},
        {data: 'created_at', render: carbonToString},
        {data: 'message'}
    ],
    order: [[2, 'asc']],
    language: {
        "emptyTable": "You have no notifications."
    },
    paging: false,
    info: false,
    searching: true,
    drawCallback: linkButtonsInTable
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

const deleteNotification = function () {
    const id = $(this).data('id');

    axios.delete(props.apiUrl + '/' + id)
        .then(response => {
            //Find the actual entry with this ID to delete locally
            for (let i = 0; i < notifications.value.length; i++) {
                if (notifications.value[i].id === id) notifications.value.splice(i, 1);
            }
        })
        .catch(error => {
            console.log("Failed to delete: ", error);
        });
}

const verifyIntentToDeleteAllNotifications = () => {
    confirmationModal.show();
};

const deleteAllNotifications = () => {
    confirmationModal.hide();
    // Find highest seen ID so that delete all doesn't delete one we haven't seen yet
    let highestId = 0;
    for (let i = 0; i < notifications.value.length; i++) {
        highestId = Math.max(notifications.value[i].id, highestId);
    }

    axios.delete(props.apiUrl, {data: {'highestId': highestId}})
        .then(response => {
            notifications.value = [];
        })
        .catch(error => {
            console.log("Failed to delete all: ", error);
        });
};


onMounted(() => {
    confirmationModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirm-delete-all-notifications'));
    refreshNotifications();
});

</script>

<style scoped>

</style>
