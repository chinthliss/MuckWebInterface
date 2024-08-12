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

type GroupedDifferences = {
    [header: string]: Difference[]
}

const compareProps = (propsToCheck: string[]): string[] => {
    const result = [];
    for (const prop of propsToCheck) {
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
}

const differences: ComputedRef<GroupedDifferences> = computed<GroupedDifferences>(() => {
    const result = {};

    result['Status'] = compareProps([
        'noReward', 'noExtract', 'noFunnel', 'noZap', 'noMastering', 'noNative', 'bypassImmune',
        'private', 'hidden', 'special?', 'powerset?', 'placement?'
    ]);

    result['Properties'] = compareProps([
        'height', 'mass', 'tags', 'scent', 'heat', 'say2ndPerson', 'say3rdPerson', 'sexless',
        'breastCount', 'breastSize', 'cuntCount', 'cuntSize', 'clitCount', 'clitSize',
        'cockCount', 'cockSize', 'ballCount', 'ballSize'
    ]);

    result['Skin'] = compareProps([
        'skinTemplate', 'skinFlags', 'skinShortDescription', 'skinTransformation', 'skinDescription', 'skinKemoDescription'
    ]);

    result['Head'] = compareProps([
        'headTemplate', 'headFlags', 'headTransformation', 'headDescription', 'headKemoDescription'
    ]);

    result['Torso'] = compareProps([
        'torsoTemplate', 'torsoFlags', 'torsoTransformation', 'torsoDescription', 'torsoKemoDescription'
    ]);

    result['Arms'] = compareProps([
        'armsTemplate', 'armsFlags', 'armsTransformation', 'armsDescription', 'armsKemoDescription'
    ]);

    result['Legs'] = compareProps([
        'legsTemplate', 'legsFlags', 'legsTransformation', 'legsDescription', 'legsKemoDescription'
    ]);

    result['Ass / Tail'] = compareProps([
        'assTemplate', 'assFlags', 'assTransformation', 'assDescription'
    ]);

    result['Groin'] = compareProps([
        'groinTemplate', 'groinFlags', 'groinTransformation', 'groinCockDescription', 'groinCuntDescription', 'groinClitDescription'
    ]);

    result['Victory & Defeat'] = compareProps(['victory', 'defeat', 'oDefeat']);

    return result;

})

</script>

<template>
    <div v-for="group in ['Status', 'Properties', 'Skin', 'Head', 'Torso', 'Arms', 'Legs', 'Ass / Tail', 'Groin', 'Victory & Defeat']">
        <h4>{{ group }}</h4>
        <table class="table table-dark table-hover table-striped table-responsive small">
            <thead>
            <tr>
                <th scope="col">Property</th>
                <th scope="col">Live Value</th>
                <th scope="col">Dev Value</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="difference in differences[group]">
                <td>{{ difference.prop }}</td>
                <td>{{ difference.live }}</td>
                <td>{{ difference.dev }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>

</style>
