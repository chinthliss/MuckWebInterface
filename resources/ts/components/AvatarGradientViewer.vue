<script setup lang="ts">

import {carbonToString} from "../formatting";
import {AvatarGradient} from "../defs";

const props = defineProps<{
    adminMode?: boolean
    gradients: AvatarGradient[]
}>();

const admin = props.adminMode ?? false;

</script>

<template>
    <div class="container">
        <h2>Avatar Gradients</h2>
        <div v-if="!admin">To purchase a gradient, simply use them in the avatar editor.</div>
        <div>
            <table class="table table-dark table-hover table-striped table-responsive small">
                <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Description</th>
                    <th scope="col" v-if="admin">Created</th>
                    <th scope="col" v-if="admin">Owner</th>
                    <th scope="col">Ownership</th>
                    <th scope="col">Preview</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="gradient in gradients">
                    <td class="text-wrap">{{ gradient.name }}</td>
                    <td>{{ gradient.desc }}</td>
                    <td v-if="admin">
                        {{ carbonToString(gradient.created_at) }}
                    </td>
                    <td v-if="admin">
                        <a v-if="gradient.owner_url" :href="gradient.owner_url">Account #{{ gradient.owner_aid }}</a>
                        <span v-else-if="gradient.owner_aid">{{ gradient.owner_aid }} </span>
                    </td>
                    <td>{{ gradient.free ? 'Free' : '' }}</td>
                    <td><img class="gradient-preview" :src="gradient.url" alt="Gradient Preview"></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
.gradient-preview {
    width: 256px;
    height: 32px;
}
</style>
