<!--
Modal dialog that seeks to confirm something.
By default will have a 'yes' and 'no' button but they can be relabelled.
Will emit 'yes' or 'no' depending on choice
-->
<template>
    <div class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" @click="$emit('no')"
                            aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <slot></slot>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$emit('no')" data-bs-dismiss="modal">
                        {{ noLabel }}
                    </button>
                    <button type="button" class="btn btn-primary" @click="$emit('yes')">
                        {{ yesLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

defineProps({
    title: {type: String, required: false, default: 'Confirm?'},
    yesLabel: {type: String, required: false, default: 'Yes'},
    noLabel: {type: String, required: false, default: 'No'}
});

</script>

<style scoped>

</style>
