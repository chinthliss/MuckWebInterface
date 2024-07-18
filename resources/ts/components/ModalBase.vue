<!--
Base modal, intended to be used for more specific ones.
Emits a 'close' event.
-->

<script setup lang="ts">

import {ref} from "vue";

const self = ref<Element | null>(null);

const show = () => {
    if (self.value) bootstrap.Modal.getOrCreateInstance(self.value as Element).show();
}
defineExpose({show});

</script>

<template>
    <div ref="self" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <slot name="title"></slot>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" @click="$emit('close')"
                            aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <slot name="content"></slot>
                    </div>
                </div>
                <div class="modal-footer">
                    <slot name="footer"></slot>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
