<script setup lang="ts">
    import {Ref, ref} from "vue";

    const props = defineProps<{
        links: {
            formEdit: string
        }
    }>();

    const channel = mwiWebsocket.channel('admin');
    const formsForReview: Ref<string[]> = ref([]);
    const booted: Ref<boolean> = ref(false);

    const linkToForm = (form: string) => {
        return props.links.formEdit + '?form=' + encodeURIComponent(form)
    }

    channel.on('bootAdminDashboard', (response: {formsForReview : string[]}) => {
        booted.value = true;
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
                <li v-for="name in formsForReview">
                    <a :href="linkToForm(name)">{{name}}</a>
                </li>
            </ul>
        </div>
        <div v-else-if="booted">There are no forms waiting for review.</div>
        <div v-else>Loading...</div>

    </div>
</template>

<style scoped>

</style>
