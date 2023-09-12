<!--
Modal dialog that intercepts axios communication errors and shows itself if one occurs
-->
<script setup lang="ts">

import {ref, onMounted} from "vue";
import type {Ref} from "vue";
import type {Modal} from "bootstrap";

const lastError = ref("");
const self: Ref<Element | null> = ref(null);

onMounted(() => {
    console.log("Registering Axios interceptor for response errors.");
    const modal: Modal = bootstrap.Modal.getOrCreateInstance(self.value!);
    axios.interceptors.response.use(function (response) {
        return response;
    }, function (error) {
        if (error.response.status === 422) {
            // We don't intercept validation errors
            throw error;
        } else {
            console.log("Server error: ", error);
            lastError.value = error?.response?.data?.message || error || "";
            modal.value.show();
            return Promise.reject(error);
        }
    });
});

</script>

<template>
    <div ref="self" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Server Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div v-if="lastError">
                            Whilst processing the request, the server responded with the following error:
                            <br/>{{ lastError }}
                        </div>
                        <div v-else>
                            An error occurred with the request to the server.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
