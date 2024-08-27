<script setup lang="ts">
    import {Ref, ref} from "vue";

    const channel = mwiWebsocket.channel('admin');
    const formsForReview: Ref<string[]> = ref([]);

    channel.on('bootAdminDashboard', (response: {formsForReview : string[]}) => {
        formsForReview.value = response.formsForReview;
    });

    channel.send('bootAdminDashboard');
</script>

<template>
    <div>
        <h2>Forms for Review</h2>
        <div v-if="formsForReview.length">
            <div>The following forms are awaiting review:</div>
            <ul>
                <li v-for="name in formsForReview">{{name}}</li>
            </ul>
        </div>
        <div v-else>There are no forms waiting for review.</div>

    </div>
</template>

<style scoped>

</style>
