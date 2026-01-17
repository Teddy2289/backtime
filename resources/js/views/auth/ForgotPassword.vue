<template>
    <div
        class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2
                    class="mt-6 text-center text-3xl font-extrabold text-gray-900"
                >
                    Forgot your password?
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Enter your email address and we'll send you a link to reset
                    your password.
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
                <div>
                    <label for="email" class="sr-only">Email address</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        required
                        class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                        placeholder="Email address"
                    />
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        <span v-if="loading">Sending...</span>
                        <span v-else>Send reset link</span>
                    </button>
                </div>

                <div class="text-center">
                    <router-link
                        :to="{ name: 'login' }"
                        class="font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        Back to login
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

const email = ref("");
const loading = ref(false);
const success = ref("");
const error = ref("");

const handleSubmit = async () => {
    loading.value = true;
    error.value = "";
    success.value = "";

    try {
        await new Promise((resolve) => setTimeout(resolve, 1000));

        success.value = "Password reset link sent! Check your email.";
        email.value = "";
    } catch (err: any) {
        error.value =
            err.message || "Failed to send reset link. Please try again.";
    } finally {
        loading.value = false;
    }
};
</script>
