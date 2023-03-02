<template>
    <div class="container">

        <h1>Character Generation</h1>

        <h2>Gender</h2>

        <h2>Birthday</h2>

        <h2>Faction</h2>

        <h2>Starting Perks</h2>

        <h2>Flaws</h2>

    </div>
</template>

<script setup>
defineProps({
    errors: {required: false},
    old: {type: Object, required: false},
    config: {type: Object, required: true}
});
</script>

<style scoped>

</style>
