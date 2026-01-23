<template>
    <TransitionRoot as="template" :show="isOpen">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div
                    class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
                >
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                        >
                            <!-- Header -->
                            <div class="mb-6">
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                                >
                                    <svg
                                        class="h-6 w-6 text-primary"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-5">
                                    <DialogTitle
                                        as="h3"
                                        class="text-lg font-semibold leading-6 text-gray-900"
                                    >
                                        Changer le mot de passe
                                    </DialogTitle>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Veuillez remplir tous les champs
                                            pour modifier votre mot de passe.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Form -->
                            <form
                                @submit.prevent="submitForm"
                                class="space-y-4"
                            >
                                <!-- Current Password -->
                                <div>
                                    <label
                                        for="current_password"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Mot de passe actuel
                                    </label>
                                    <input
                                        id="current_password"
                                        v-model="form.current_password"
                                        type="password"
                                        required
                                        :class="[
                                            'block w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-offset-1',
                                            errors.current_password
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 focus:border-primary focus:ring-primary',
                                        ]"
                                        :disabled="isLoading"
                                    />
                                    <p
                                        v-if="errors.current_password"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.current_password }}
                                    </p>
                                </div>

                                <!-- New Password -->
                                <div>
                                    <label
                                        for="new_password"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Nouveau mot de passe
                                    </label>
                                    <input
                                        id="new_password"
                                        v-model="form.new_password"
                                        type="password"
                                        required
                                        :class="[
                                            'block w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-offset-1',
                                            errors.new_password
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 focus:border-primary focus:ring-primary',
                                        ]"
                                        :disabled="isLoading"
                                    />
                                    <p
                                        v-if="errors.new_password"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.new_password }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Le mot de passe doit contenir au moins 6
                                        caractères.
                                    </p>
                                </div>

                                <!-- Confirm New Password -->
                                <div>
                                    <label
                                        for="new_password_confirmation"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Confirmer le nouveau mot de passe
                                    </label>
                                    <input
                                        id="new_password_confirmation"
                                        v-model="form.new_password_confirmation"
                                        type="password"
                                        required
                                        :class="[
                                            'block w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-offset-1',
                                            errors.new_password_confirmation
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 focus:border-primary focus:ring-primary',
                                        ]"
                                        :disabled="isLoading"
                                    />
                                    <p
                                        v-if="errors.new_password_confirmation"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.new_password_confirmation }}
                                    </p>
                                </div>

                                <!-- Error Message -->
                                <div
                                    v-if="errorMessage"
                                    class="rounded-lg bg-red-50 p-4"
                                >
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg
                                                class="h-5 w-5 text-red-400"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p
                                                class="text-sm font-medium text-red-800"
                                            >
                                                {{ errorMessage }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Success Message -->
                                <div
                                    v-if="successMessage"
                                    class="rounded-lg bg-green-50 p-4"
                                >
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg
                                                class="h-5 w-5 text-green-400"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p
                                                class="text-sm font-medium text-green-800"
                                            >
                                                {{ successMessage }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div
                                    class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3"
                                >
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        :disabled="isLoading"
                                        class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary sm:mt-0 sm:w-auto"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="isLoading"
                                        :class="[
                                            'inline-flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto',
                                            isLoading
                                                ? 'cursor-not-allowed bg-primary/70'
                                                : 'bg-primary hover:bg-primary-dark',
                                        ]"
                                    >
                                        <span
                                            v-if="isLoading"
                                            class="flex items-center"
                                        >
                                            <svg
                                                class="mr-2 h-4 w-4 animate-spin text-white"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                ></circle>
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                ></path>
                                            </svg>
                                            Modification...
                                        </span>
                                        <span v-else>
                                            Modifier le mot de passe
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionRoot,
    TransitionChild,
} from "@headlessui/vue";
import { useProfileStore } from "@/stores/profile";

interface Props {
    isOpen: boolean;
}

interface Emits {
    (e: "close"): void;
    (e: "success"): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const profileStore = useProfileStore();

// Form state
const form = reactive({
    current_password: "",
    new_password: "",
    new_password_confirmation: "",
});

// UI state
const isLoading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const errors = reactive({
    current_password: "",
    new_password: "",
    new_password_confirmation: "",
});

// Validation
const validateForm = (): boolean => {
    let isValid = true;

    // Reset errors
    errors.current_password = "";
    errors.new_password = "";
    errors.new_password_confirmation = "";
    errorMessage.value = "";

    // Validate current password
    if (!form.current_password.trim()) {
        errors.current_password = "Le mot de passe actuel est requis";
        isValid = false;
    }

    // Validate new password
    if (!form.new_password.trim()) {
        errors.new_password = "Le nouveau mot de passe est requis";
        isValid = false;
    } else if (form.new_password.length < 6) {
        errors.new_password =
            "Le mot de passe doit contenir au moins 6 caractères";
        isValid = false;
    }

    // Validate password confirmation
    if (!form.new_password_confirmation.trim()) {
        errors.new_password_confirmation =
            "La confirmation du mot de passe est requise";
        isValid = false;
    } else if (form.new_password !== form.new_password_confirmation) {
        errors.new_password_confirmation =
            "Les mots de passe ne correspondent pas";
        isValid = false;
    }

    return isValid;
};

// Submit form
const submitForm = async () => {
    if (!validateForm()) return;

    try {
        isLoading.value = true;
        errorMessage.value = "";
        successMessage.value = "";

        await profileStore.changePassword({
            current_password: form.current_password,
            new_password: form.new_password,
            new_password_confirmation: form.new_password_confirmation,
        });

        successMessage.value = "Mot de passe modifié avec succès";

        // Reset form
        form.current_password = "";
        form.new_password = "";
        form.new_password_confirmation = "";

        // Close modal after success
        setTimeout(() => {
            emit("success");
            closeModal();
        }, 2000);
    } catch (error: any) {
        console.error("Error changing password:", error);

        // Handle validation errors
        if (error.response?.data?.errors) {
            const apiErrors = error.response.data.errors;

            if (apiErrors.current_password) {
                errors.current_password = Array.isArray(
                    apiErrors.current_password,
                )
                    ? apiErrors.current_password[0]
                    : apiErrors.current_password;
            }

            if (apiErrors.new_password) {
                errors.new_password = Array.isArray(apiErrors.new_password)
                    ? apiErrors.new_password[0]
                    : apiErrors.new_password;
            }

            if (apiErrors.new_password_confirmation) {
                errors.new_password_confirmation = Array.isArray(
                    apiErrors.new_password_confirmation,
                )
                    ? apiErrors.new_password_confirmation[0]
                    : apiErrors.new_password_confirmation;
            }
        } else {
            errorMessage.value =
                error.response?.data?.message ||
                "Une erreur est survenue lors de la modification du mot de passe";
        }
    } finally {
        isLoading.value = false;
    }
};

// Close modal
const closeModal = () => {
    if (isLoading.value) return;

    // Reset form and messages
    form.current_password = "";
    form.new_password = "";
    form.new_password_confirmation = "";
    errorMessage.value = "";
    successMessage.value = "";
    errors.current_password = "";
    errors.new_password = "";
    errors.new_password_confirmation = "";

    emit("close");
};
</script>
