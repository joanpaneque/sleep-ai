<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    channel: Object
})

const form = useForm({
    title: '',
    description: '',
    channel_id: props.channel.id
})

const submit = () => {
    form.post(route('videos.store'))
}
</script>

<template>
    TEST VIDEO CREATE
</template>