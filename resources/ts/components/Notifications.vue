<script setup lang="ts">
import {ref, onMounted, Ref} from 'vue';
import ModalConfirmation from './ModalConfirmation.vue';
import {carbonToString} from "../formatting";
import Spinner from "./Spinner.vue";
import {AccountNotification} from "../defs";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";


const props = defineProps<{
    apiUrl: string
}>();

const loadingNotifications: Ref<boolean> = ref(true);
const initialLoading: Ref<boolean> = ref(true);

const notifications: Ref<AccountNotification[]> = ref([]);

const confirmationModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);

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

const deleteNotification = (notification: AccountNotification) => {

    axios.delete(props.apiUrl + '/' + notification.id)
        .then(_response => {
            //Find the actual entry with this ID to delete locally
            for (let i = 0; i < notifications.value.length; i++) {
                if (notifications.value[i].id === notification.id) notifications.value.splice(i, 1);
            }
        })
        .catch(error => {
            console.log("Failed to delete: ", error);
        });
}

const verifyIntentToDeleteAllNotifications = () => {
    if (confirmationModal.value) confirmationModal.value.show();
};

const deleteAllNotifications = () => {
    // Find highest seen ID so that delete all doesn't delete one we haven't seen yet
    let highestId = 0;
    for (let i = 0; i < notifications.value.length; i++) {
        highestId = Math.max(notifications.value[i].id, highestId);
    }

    axios.delete(props.apiUrl, {data: {'highestId': highestId}})
        .then(_response => {
            notifications.value = [];
        })
        .catch(error => {
            console.log("Failed to delete all: ", error);
        });
};


onMounted(() => {
    refreshNotifications();
});

</script>

<template>
    <div class="container">

        <h1>Notifications</h1>

        <Spinner v-if="loadingNotifications"/>

        <div v-if="!initialLoading">

            <div class="d-flex justify-content-center mb-2">
                <button class="btn btn-warning" @click="verifyIntentToDeleteAllNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>
                    Delete All Notifications
                </button>
            </div>

            <DataTable :value="notifications" stripedRows>
                <template #empty>
                    <div class="text-center">You have no notifications.</div>
                </template>
                <Column>
                    <template #body="{ data }">
                        <div class="d-flex align-items-center">
                            <i v-if="(data as AccountNotification).read_at" class="fas fa-envelope-open text-muted"></i>
                            <i v-else class="fas fa-envelope text-primary"></i>
                            <button class="btn btn-secondary ms-2"
                                    @click="deleteNotification(data as AccountNotification)"
                            ><i class="fas fa-trash btn-icon-left"></i>Delete
                            </button>
                        </div>
                    </template>
                </Column>
                <Column header="Character" field="character" sortable></Column>
                <Column header="Date" field="created_at" sortable>
                    <template #body="{ data }">
                        {{ carbonToString((data as AccountNotification).created_at) }}
                    </template>
                </Column>
                <Column header="Message" field="message"></Column>
            </DataTable>

            <div class="d-flex justify-content-center mt-2">
                <button class="btn btn-warning" @click="verifyIntentToDeleteAllNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>
                    Delete All Notifications
                </button>
            </div>

        </div>

        <ModalConfirmation ref="confirmationModal" @yes="deleteAllNotifications"
                           yes-label="Delete All" no-label="Cancel"
        >
            Are you sure you wish to delete all your notifications?
        </ModalConfirmation>

    </div>

</template>

<style scoped>

</style>
