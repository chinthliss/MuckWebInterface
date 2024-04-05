<script setup lang="ts">

import {ref, Ref} from "vue";

const presentFormId: Ref<string | null> = ref(null);

const channel = mwiWebsocket.channel('contribute');
const formListLoading: Ref<boolean> = ref(false);
const formList: Ref<any[] | null> = ref(null);

// Send requests for data unless we opened on a particular form
if (!presentFormId) channel.send('getFormList');

</script>

<template>
    Pending
</template>

<style scoped>

</style>
