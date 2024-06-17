<script setup lang="ts">

import {ref, Ref} from "vue";

const props = defineProps<{
    viewOnly: boolean,
    propName: string,
    propValue: string,
    label: string
}>();

const input: Ref<string> = ref('');

</script>

<template>
    <div>
        <label :for="propName" class="form-label">{{label}}</label>
        <input :id="propName" type="text" class="form-control" :disabled="viewOnly"
               placeholder="#" v-model="input"
        >
    </div>
</template>

<style scoped>

</style>
