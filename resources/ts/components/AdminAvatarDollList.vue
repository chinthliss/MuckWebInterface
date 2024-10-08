<script setup lang="ts">

type Doll = {
    name: string,
    url: string,
    edit: string,
    usage: string[]
}
const props = defineProps<{
    dolls: Doll[],
    invalid: string[]
}>();

</script>

<template>
    <div class="container">
        <h2>Avatar Paper Doll List</h2>
        <div v-if="Object.values(props.invalid).length > 0" class="alert alert-warning">
            The following avatar dolls were referenced but couldn't be found:
            <div v-for="(forms, avatar) in props.invalid">
                {{ avatar }}, referenced by: {{ Object.values(forms).join(', ') }}
            </div>
        </div>

        <div>Dolls are rendered here without any colors applied and should appear in grayscale unless deliberate tinting
            is being used.
        </div>
        <div>The number after each doll is a count of how many infections use it - the full breakdown of such is at the
            bottom of this page.
        </div>
        <div>Clicking on a doll will start an instance of the tester using that doll as the base.</div>
        <div>
            <template v-for="doll in props.dolls">
                <a :href="doll.edit">
                    <div class="card doll-card">
                        <div class="card-img-top">
                            <img :src="doll.url" alt="Avatar Doll Thumbnail" class="d-block m-auto">
                        </div>
                        <div class="card-body">
                            <div class="text-center small">{{ doll.name }}</div>
                            <div class="text-center"><span class="badge badge-pill badge-info"
                                                           v-bind:class="[doll.usage.length ? 'badge-info' : 'badge-warning']">{{
                                    doll.usage.length
                                }}</span></div>
                        </div>
                    </div>
                </a>
            </template>
        </div>

        <h3>Usage Breakdown</h3>
        <table class="table table-dark table-hover table-striped table-responsive small">
            <thead>
            <tr>
                <th>Doll</th>
                <th>Used by</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="doll in props.dolls">
                <tr v-if="doll.usage.length > 0">
                    <td>{{ doll.name }}</td>
                    <td>{{ doll.usage.join(', ') }}</td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.doll-card {
    width: 185px;
    height: 260px;
    display: inline-block;
}
</style>
