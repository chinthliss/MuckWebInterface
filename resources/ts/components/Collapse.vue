<script lang="ts" setup>

import {ref} from "vue";

defineProps<{
    title?: string,
}>();

const expanded = ref<boolean>(false);

const emit = defineEmits(['shown', 'hidden', 'mounted'])

const show = () => {
    if (expanded.value) return;
    expanded.value = true;
    emit('shown');
}

const hide = () => {
    if (!expanded.value) return;
    expanded.value = false;
    emit('hidden');
}

const toggle = () => {
    if (expanded.value) hide(); else show();
}

defineExpose({show, hide});

</script>

<template>
    <div>
        <div id="collapse-toggle" :class="{
                'rounded-top': expanded,
                'rounded': !expanded,
                'border-bottom-0': expanded
             }" aria-controls="content"
             class="d-flex align-items-center border-primary-subtle border p-1 "
             @click="toggle"
        >
            <div :aria-expanded="expanded" aria-hidden="true" class="btn btn-primary me-2">
                <i v-if="expanded" class="fa fa-chevron-up"></i>
                <i v-else class="fa fa-chevron-down"></i>
            </div>
            <div id="content-title">{{ title || 'Collapse' }}</div>
        </div>
        <div v-if="expanded" id="content"
             aria-labelledby="content-title"
             class="border-primary-subtle border rounded-bottom bg-body"
        >
            <slot>
                No content provided for collapse
            </slot>
        </div>
    </div>
</template>

<style scoped>
#collapse-toggle {
    cursor: pointer;
}
</style>
