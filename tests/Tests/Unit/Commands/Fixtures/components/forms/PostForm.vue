<script setup lang="ts">
import ModelForm from "@/components/data/ModelForm.vue";

const props = defineProps<{
    id: string,
}>();
</script>

<template>
    <ModelForm :id="props.id" collection="posts" v-slot="{ data }">

    </ModelForm>
</template>

<style scoped lang="scss">

</style>
