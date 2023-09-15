<!--
Modal dialog that seeks to confirm something.
By default will have a 'yes' and 'no' button but they can be relabelled.
Will emit 'yes' or 'no' depending on choice
-->

<script setup lang="ts">

import {ref} from "vue";
import ModalBase from "./ModalBase.vue";

defineProps<{
    title?: string,
    yesLabel?: string,
    noLabel?: string
}>();

const self = ref<InstanceType<typeof ModalBase> | null>(null);

const show = () => {
    if (self.value) self.value.show();
}
defineExpose({show});


</script>

<template>
    <modal-base ref="self">
        <template v-slot:title>
            <h5 class="modal-title">{{ title ?? 'Confirm?' }}</h5>
        </template>
        <template v-slot:content>
            <slot></slot>
        </template>
        <template v-slot:footer>
            <button type="button" class="btn btn-secondary" @click="$emit('no')" data-bs-dismiss="modal">
                {{ noLabel ?? 'No' }}
            </button>
            <button type="button" class="btn btn-primary" @click="$emit('yes')" data-bs-dismiss="modal">
                {{ yesLabel ?? 'Yes' }}
            </button>
        </template>
    </modal-base>
</template>

<style scoped>

</style>
