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
    <div class="row">
        <!-- Name filter -->
        <div class="col-12 col-xl-6 d-flex mb-2">
            <label class="col-form-label me-2" for="nameFilter">Name</label>
            <input id="nameFilter" v-model="nameFilter" class="form-control"
                   placeholder="Filter by name"
                   type="text">
        </div>
        <!-- Description toggle -->
        <div
            class="col-12 col-xl-6 form-check form-switch mb-2 d-flex align-items-center justify-content-center">
            <input id="showDescriptionsSwitch" v-model="showDescriptions"
                   class="form-check-input me-2"
                   role="switch"
                   type="checkbox"
            >
            <label class="form-check-label" for="showDescriptionsSwitch">Show Descriptions?</label>
        </div>
    </div>
    <div class="row">
        <template v-for="modifier in modifiers" :key="modifier.name">
            <div v-if="shouldShow(modifier)" class="col-12 col-xxl-4 col-xl-6 mb-2">
                <div class="card h-100" role="button"
                     v-bind:class="{ 'text-bg-primary': selectedModifiers.includes(modifier.name) }"
                     @click="toggleModifier(modifier)"
                >
                    <div class="d-flex align-items-center p-2">
                        <div class="flex-grow-1">
                            <h5 class="card-title">{{ modifier.name }}</h5>
                            <div v-if="modifier.slot" class="card-text">Slot: {{ capital(modifier.slot) }}</div>
                            <p v-if="showDescriptions" class="card-text" v-html="ansiToHtml(modifier.description)"></p>
                        </div>
                        <div>
                            <button class="btn btn-info rounded-5 rpinfo-button" type="button">
                                <i class="fas fa-question btn-icon-left"></i>Rpinfo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </template>
    </div>
    <div class="fw-bold"><span class="text-primary">Selected Modifiers:</span> {{
            arrayToList(selectedModifiers) || 'None'
        }}
    </div>
</template>

<style scoped>
.rpinfo-button {
    min-width: 96px;
}
</style>
