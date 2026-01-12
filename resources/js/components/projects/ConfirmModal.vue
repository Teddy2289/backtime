<template>
    <TransitionRoot appear :show="show" as="template">
        <Dialog as="div" class="relative z-50" @close="cancel">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black bg-opacity-25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <!-- Icon -->
                            <div class="flex justify-center mb-4">
                                <div
                                    :class="`mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-${variantColor}-100`">
                                    <component :is="variantIcon" :class="`h-6 w-6 text-${variantColor}-600`"
                                        aria-hidden="true" />
                                </div>
                            </div>

                            <!-- Title -->
                            <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 text-center">
                                {{ title }}
                            </DialogTitle>

                            <!-- Message -->
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 text-center">
                                    {{ message }}
                                </p>
                                <p v-if="subMessage" class="text-sm text-gray-500 text-center mt-1">
                                    {{ subMessage }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="mt-6 flex justify-center space-x-3">
                                <button type="button" :class="[
                                    'inline-flex justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                                    variant === 'danger'
                                        ? 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500'
                                        : 'bg-blue-600 text-white hover:bg-blue-700 focus-visible:ring-blue-500'
                                ]" @click="confirm">
                                    {{ confirmText }}
                                </button>
                                <button type="button"
                                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500"
                                    @click="cancel">
                                    {{ cancelText }}
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, defineEmits, defineProps } from 'vue';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import {
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    InformationCircleIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps<{
    show: boolean;
    title: string;
    message: string;
    subMessage?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'info' | 'success';
}>();

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

// Default values
const confirmText = props.confirmText || 'Confirm';
const cancelText = props.cancelText || 'Cancel';
const variant = props.variant || 'danger';

// Computed
const variantIcon = computed(() => {
    switch (variant) {
        case 'danger':
            return ExclamationTriangleIcon;
        case 'warning':
            return ExclamationCircleIcon;
        case 'info':
            return InformationCircleIcon;
        case 'success':
            return CheckCircleIcon;
        default:
            return ExclamationTriangleIcon;
    }
});

const variantColor = computed(() => {
    switch (variant) {
        case 'danger':
            return 'red';
        case 'warning':
            return 'yellow';
        case 'info':
            return 'blue';
        case 'success':
            return 'green';
        default:
            return 'red';
    }
});

// Methods
const confirm = () => {
    emit('confirm');
};

const cancel = () => {
    emit('cancel');
};
</script>