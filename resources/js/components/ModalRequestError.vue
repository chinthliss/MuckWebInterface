<!--
Modal dialog that shows an error from a web request.
Error is intended to be whatever the interface for the web request returns, so is usually an object
-->
<template>
    <div class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">An error occurred</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div v-if="error">
                            Unfortunately whilst processing the request the server responded with an error:
                            <br/>{{ error?.response?.data?.message || error }}
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
defineProps({
    error: {required: false}
});
</script>

<style scoped>

</style>
