<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    description: ''
})

const submit = () => {
    form.post(route('channels.store'))
}
</script>

<template>
    TEST CHANNEL CREATE
</template>