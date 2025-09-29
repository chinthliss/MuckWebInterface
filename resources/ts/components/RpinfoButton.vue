<script setup lang="ts">

const props = defineProps<{
    category: string,
    item: string
}>();

const emit = defineEmits<{
    rpinfo: {category: string, item: string}
}>()

const rpinfo = () => {
    emit("rpinfo", {category: props.category, item: props.item});
}

</script>

<template>
    <button class="btn btn-info rounded-5 rpinfo-button" type="button" @click.stop="rpinfo">
        <i class="fas fa-info-circle btn-icon-left"></i>RP-Info
    </button>
</template>

<style scoped>
.rpinfo-button {
    min-width: 96px;
}
</style>
