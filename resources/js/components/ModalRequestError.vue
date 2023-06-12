<!--
Modal dialog that intercepts axios communication errors and shows itself if one occurs
-->
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

<script setup>

import {ref, onMounted} from "vue";

const lastError = ref("");
const self = ref(null);
const modal = ref(null);

onMounted(() => {
    modal.value = bootstrap.Modal.getOrCreateInstance(self.value);
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
            modal.value.show();
            return Promise.reject(error);
        }
    });
});

</script>

<style scoped>

</style>
