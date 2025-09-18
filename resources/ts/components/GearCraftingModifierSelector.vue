<script lang="ts" setup>

import type {Modifier} from "./GearCrafting.vue"
import {ref, Ref} from "vue";
import {ansiToHtml, arrayToList, capital} from "../formatting";

const {
    modifiers = []
} = defineProps<{
    modifiers: Modifier[],
}>();

const selectedModifiers: Ref<string[]> = ref([]);
const showDescriptions: Ref<boolean> = ref(false);
const nameFilter: Ref<string> = ref('');

const emit = defineEmits(['update'])

const shouldShow = (modifier: Modifier): boolean => {
    if (!nameFilter.value) return true;
    return (modifier.name.toLowerCase().includes(nameFilter.value.toLowerCase()))
}

const toggleModifier = (modifier: Modifier) => {
    if (selectedModifiers.value.includes(modifier.name))
        selectedModifiers.value.splice(selectedModifiers.value.indexOf(modifier.name), 1);
    else
        selectedModifiers.value.push(modifier.name);
    emit('update', selectedModifiers.value);
}

</script>

<template>
    <template v-for="modifier in modifiers" :key="modifier.name">

        <div v-if="shouldShow(modifier)" class="card mb-2" role="button"
             v-bind:class="{ 'text-bg-primary': selectedModifiers.includes(modifier.name) }"
             @click="toggleModifier(modifier)"
        >
            <div class="card-body">
                <h5 class="card-title">{{ modifier.name }}</h5>
                <div v-if="modifier.slot" class="card-text">Slot: {{ capital(modifier.slot) }}</div>
                <p v-if="showDescriptions" class="card-text" v-html="ansiToHtml(modifier.description)"></p>
            </div>
        </div>
    </template>
    <div class="fw-bold"><span class="text-primary">Selected Modifiers:</span> {{
            arrayToList(selectedModifiers) || 'None'
        }}
    </div>
</template>

<style scoped>

</style>
