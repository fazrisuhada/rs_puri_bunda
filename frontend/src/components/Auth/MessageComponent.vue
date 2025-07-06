<template>
    <Message severity="error" variant="simple" size="small">
        {{ props.message }}
    </Message>
</template>

<script setup>
import Message from 'primevue/message';

const props = defineProps({
    message: String
})
</script>