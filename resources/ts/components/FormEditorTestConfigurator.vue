<script setup lang="ts">

import {ref, Ref} from "vue";

const props = defineProps<{
    role: string // Should be 'subject' or 'other'
}>();

const sexOptions = [ // Each entry is the value and a label
    ["male", "Male"],
    ["female", "Female"],
    ["herm", "Herm"],
    ["neuter", "Neuter"],
]
if (props.role == 'subject') sexOptions.unshift(["form", "Form's Default"]);

const nameOptions = [
    props.role == 'subject' ? 'Subject' : 'Other',
    'Joe',
    'Jane',
    'Jesse'
]

const sex: Ref<string> = ref(props.role == 'subject' ? 'form' : 'male');
const name: Ref<string> = ref(props.role == 'subject' ? 'Subject' : 'Other');

</script>

<template>
    <!-- Name -->
    <div class="d-lg-flex align-items-center justify-content-center mt-2">
        <div class="me-2 text-primary">Name:</div>
        <div class="me-4 btn-group" role="group" aria-label="Name">
            <template v-for="item in nameOptions">
                <input type="radio" class="btn-check" autocomplete="off"
                       :name="role + '_name'" :id="role + '_name_' + item" v-model="name" :value="item"
                >
                <label class="btn btn-outline-secondary" :for="role + '_name_' + item">{{ item }}</label>
            </template>

        </div>
    </div>

    <!-- Sex -->
    <div class="d-lg-flex align-items-center justify-content-center mt-2">
        <div class="me-2 text-primary">Sex:</div>
        <div class="me-4 btn-group" role="group" aria-label="Sex">
            <template v-for="item in sexOptions">
                <input type="radio" class="btn-check" autocomplete="off"
                       :name="role + '_sex'" :id="role + '_sex_' + item[0]" v-model="sex" :value="item[0]"
                >
                <label class="btn btn-outline-secondary" :for="role + '_sex_' + item[0]">{{ item[1] }}</label>
            </template>

        </div>
    </div>

    <!-- Switches -->
    <div class="d-flex justify-content-center">
        <div>
            <!-- Kemo -->
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" role="switch" :id="role + '_kemo'">
                <label class="form-check-label" for="role + '_kemo'">Has 'Kemonomimi' perk?</label>
            </div>
            <!-- Satyr -->
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" role="switch" :id="role + '_satyr'">
                <label class="form-check-label" for="role + '_satyr'">Has 'Satyric' perk?</label>
            </div>
            <!-- Arm Divider -->
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" role="switch" :id="role + '_armdivider'">
                <label class="form-check-label" for="role + '_armdivider'">Has 'Arm Divider' toy?</label>
            </div>
            <!-- Leg Divider -->
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" role="switch" :id="role + '_legdivider'">
                <label class="form-check-label" for="role + '_legdivider'">Has 'Leg Divider' toy?</label>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
