<template>
    <div class="container">
        <h1>Create a new Character</h1>
        <div class="row">
            <div class="col-12 col-md-6">
                <p>Please enter a name, it needs to be at least three letters long. A single hypen can be used in the name but no special characters.</p>
                <p>This is supposed to be a reasonable name, such as what a person would go by in real life.</p>
                <p>WARNING: Profane or copyrighted names will be changed without warning.</p>
            </div>
            <div class="col-12 col-md-6">
                <form action="" method="POST">
                    <input type="hidden" name="_token" :value="csrf()">
                    <div class="form-group">
                        <label for="characterName">New Character Name</label>
                        <input type="text" class="form-control" id="characterName" name="characterName"
                               placeholder="Enter name" v-bind:class="{ 'is-invalid' : errors.characterName }"
                               :value="old.characterName"
                        >
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.characterName">{{ error }}</p>
                        </div>
                    </div>
                    <button type="submit" class="mt-2 btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import {csrf} from "../siteutils";

defineProps({
    errors: {required: false},
    old: {type: Object, required: false}
});

</script>
<style scoped>

</style>
