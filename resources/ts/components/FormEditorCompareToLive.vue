<script setup lang="ts">
import {computed, ComputedRef} from "vue";
import {Form} from "./FormEditor.vue";

const props = defineProps<{
    devForm: Form,
    liveForm: Form
}>();

type Difference = {
    prop: string,
    live: string,
    dev: string
}

type GroupedDifferences = {
    [header: string]: Difference[]
}

const compareProps = (propsToCheck: string[]): Difference[] => {
    const result = [];
    for (const prop of propsToCheck) {
        if (props.liveForm[prop as keyof Form] !== props.devForm[prop as keyof Form]) {
            result.push({
                    prop: prop,
                    live: props.liveForm[prop as keyof Form] as string,
                    dev: props.devForm[prop as keyof Form] as string
                }
            );
        }
    }
    return result;
}

const differences: ComputedRef<GroupedDifferences> = computed<GroupedDifferences>(() => {
    const result: { [group: string]: Difference[] } = {};

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
    <div
        v-for="group in ['Status', 'Properties', 'Skin', 'Head', 'Torso', 'Arms', 'Legs', 'Ass / Tail', 'Groin', 'Victory & Defeat']"
    >
        <h4 class="mt-1 mb-0">{{ group }}</h4>
        <table v-if="differences.length" class="table table-dark table-hover table-striped table-responsive small">
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
        <div v-else>No differences</div>
    </div>
</template>

<style scoped>

</style>
