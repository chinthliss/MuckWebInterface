<!--
Modal dialog that shows a dismissible message
-->

<script setup lang="ts">

import {ref} from "vue";
import ModalBase from "./ModalBase.vue";

defineProps<{
    title?: string
}>();

const self = ref<ModalBase | null>(null);

const show = () => {
    if (self.value) self.value.show();
}
defineExpose({show});

</script>

<template>
    <modal-base ref="self">
        <template v-slot:title>
            <h5 class="modal-title">{{ title ?? 'Message' }}</h5>
        </template>
        <template v-slot:content>
            <slot></slot>
        </template>
        <template v-slot:footer>
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        </template>
    </modal-base>
</template>

<style scoped>

</style>
