<script setup lang="ts">

const props = defineProps<{
    category: string,
    item: string
}>();

const emit = defineEmits<{
    rpinfo: [{category: string, item: string}]
}>()

const launchRpInfo = () => {
    emit("rpinfo", {category: props.category, item: props.item});
}

</script>

<template>
    <button class="btn btn-info rounded-5 rpinfo-button" type="button" @click.stop="launchRpInfo">
        <i class="fas fa-question btn-icon-left"></i>Rpinfo
    </button>
</template>

<style scoped>
.rpinfo-button {
    min-width: 96px;
}
</style>
