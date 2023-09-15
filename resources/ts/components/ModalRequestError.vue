<!--
Modal dialog that intercepts axios communication errors and shows itself if one occurs
-->
<script setup lang="ts">

import {ref, onMounted} from "vue";
import type {Ref} from "vue";
import ModalBase from "./ModalBase.vue";

const lastError = ref("");
const self = ref<Element | null>(null);

const show = () => {
    if (self.value) self.value.show();
}
defineExpose({show});

onMounted(() => {
    console.log("Registering Axios interceptor for response errors.");
    axios.interceptors.response.use(function (response) {
        return response;
    }, function (error) {
        if (error.response.status === 422) {
            // We don't intercept validation errors
            throw error;
        } else {
            console.log("Server error: ", error);
            lastError.value = error?.response?.data?.message || error || "";
            show();
            return Promise.reject(error);
        }
    });
});

</script>

<template>
    <modal-base ref="self">
        <template v-slot:title>
            <h5 class="modal-title">Server Error</h5>
        </template>
        <template v-slot:content>
            <div v-if="lastError">
                Whilst processing the request, the server responded with the following error:
                <br/>{{ lastError }}
            </div>
            <div v-else>
                An error occurred with the request to the server.
            </div>
        </template>
        <template v-slot:footer>
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        </template>
    </modal-base>
</template>

<style scoped>

</style>
