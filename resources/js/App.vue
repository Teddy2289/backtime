<template>
    <component :is="layout">
        <router-view />
    </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import GuestLayout from '@/components/layout/GuestLayout.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'

const route = useRoute()

const layout = computed(() => {
    const layoutName = route.meta.layout || 'AppLayout'

    switch (layoutName) {
        case 'GuestLayout':
            return GuestLayout
        case 'AdminLayout':
            return AdminLayout
        default:
            return AppLayout
    }
})
</script>