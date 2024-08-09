<script setup lang="ts">
import type Form from "./FormEditor.vue";
import {computed, ComputedRef, Ref} from "vue";

const props = defineProps<{
    devForm: Ref<Form>;
    liveForm: Ref<Form>;
}>();

type Difference = {
    prop: string,
    live: string,
    dev: string
}

const differences: ComputedRef<Difference[]> = computed<Difference[]>(() => {
    const result = [];

    const baseProps = [
        'height', 'mass', 'tags', 'say', 'oSay', 'breastCount', 'breastSize', 'cuntCount', 'cuntSize',
        'clitCount', 'clitSize', 'cockCount', 'cockSize', 'ballCount', 'ballSize', 'scent', 'heat', 'template',
        'sexless', 'noExtract', 'noReward', 'noFunnel', 'noZap', 'noMastering', 'noNative', 'bypassImmune',
        'private', 'hidden', 'special?', 'powerset?', 'placement?'
    ];
    for (const prop of baseProps) {
        if (props.liveForm.value[prop] != props.devForm.value[prop]) {
            result.push({
                    prop: prop,
                    live: props.liveForm.value[prop],
                    dev: props.devForm.value[prop]
                }
            );
        }
    }
    return result;

})

</script>

<template>
    <table class="table table-dark table-hover table-striped table-responsive small">
        <thead>
        <tr>
            <th scope="col">Prop</th>
            <th scope="col">Live</th>
            <th scope="col">Dev</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="difference in differences">
            <td>{{ difference.prop }}</td>
            <td>{{ difference.live }}</td>
            <td>{{ difference.dev }}</td>
        </tr>
        </tbody>
    </table>
</template>

<style scoped>

</style>
