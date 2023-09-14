<script setup lang="ts">
import {carbonToString} from "../formatting";
import {AvatarItem} from "../defs";


type FileUsage = {
    filename: string
    inUse: boolean
}

const props = defineProps<{
    items: AvatarItem[],
    fileUsage: FileUsage[]
}>();

const unusedFiles: FileUsage[] = [];
props.fileUsage.forEach((file: FileUsage) => {
    if (!file.inUse) unusedFiles.push(file);
});

</script>

<template>
    <div class="container">
        <h2>Avatar Items</h2>
        <div v-for="category in [
            {type: 'item', label: 'Items'},
            {type: 'background', label: 'Backgrounds'}
        ]"
        >
            <h3>{{ category.label }}</h3>
            <table class="table table-responsive small">
                <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col">Id</th>
                    <th scope="col">Name</th>
                    <th scope="col">Filename</th>
                    <th scope="col">Requirement</th>
                    <th scope="col">Created</th>
                    <th scope="col">Owner</th>
                    <th scope="col">Cost</th>
                    <th scope="col">X</th>
                    <th scope="col">Y</th>
                    <th scope="col">Rotate</th>
                    <th scope="col">Scale</th>
                </tr>
                </thead>
                <template v-for="item in items">
                    <tr v-if="item.type === category.type">
                        <td><img :src="item.url" alt="Image for an avatar item"></td>
                        <td>{{ item.id }}</td>
                        <td class="text-wrap">{{ item.name }}</td>
                        <td>{{ item.filename }}</td>
                        <td>{{ item.requirement }}</td>
                        <td>{{ carbonToString(item.created_at) }}</td>
                        <td><a v-if="item.owner" :href="item.owner.url">Account #{{ item.owner.id }}</a></td>
                        <td>{{ item.cost }}</td>
                        <td>{{ item.x }}</td>
                        <td>{{ item.y }}</td>
                        <td>{{ item.rotate }}</td>
                        <td>{{ item.scale }}</td>
                    </tr>
                </template>
            </table>
        </div>

        <h2>Unused files</h2>
        <p>Any files found in the items folder that aren't used by an avatar item will be listed here.</p>
        <div v-if="unusedFiles.length === 0">No unused files found in the items folder.</div>
        <div v-else v-for="file in unusedFiles">{{ file.filename }}</div>
    </div>
</template>

<style scoped>

</style>
