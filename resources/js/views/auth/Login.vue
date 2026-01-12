<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 p-4">
        <div class="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))] -z-10"></div>
        
        <!-- Background decorative elements -->
        <div class="fixed top-0 left-1/4 w-72 h-72 bg-primary/50 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse"></div>
        <div class="fixed bottom-0 right-1/4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse delay-1000"></div>
        
        <div class="max-w-md w-full space-y-8 relative">
            <!-- Card Container -->
            <div class="bg-white/90 backdrop-blur-lg rounded-2xl shadow-2xl shadow-gray-200/50 p-8 border border-gray-100">
                <!-- Header -->
                <div class="text-center space-y-3">
                    <div class="flex justify-center">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-primary to-primary-dark flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                    
                    <div>
                        <h2 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                            Bienvenue de nouveau !
                        </h2>
                        <p class="text-gray-500 mt-2 text-sm">
                            Connectez-vous à votre compte pour continuer
                        </p>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" 
                     class="mt-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-start space-x-3 animate-fade-in">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-red-700 text-sm">{{ error }}</p>
                </div>

                <!-- Login Form -->
                <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
                    <div class="space-y-5">
                        <!-- Email Input -->
                        <div class="relative group">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2 ml-1">
                             Adresse e-mail
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-secondary-dark transition-colors" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input 
                                    id="email" 
                                    v-model="form.email" 
                                    type="email" 
                                    required
                                    class="block w-full pl-10 pr-4 py-3 text-gray-900 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary focus:border-secondary focus:outline-none transition-all duration-200 placeholder-gray-400 shadow-sm hover:border-gray-400"
                                    placeholder="you@example.com"
                                    :disabled="loading"
                                />
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="relative group">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2 ml-1">
                                Mot de passe
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-secondary transition-colors" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input 
                                    id="password" 
                                    v-model="form.password" 
                                    type="password" 
                                    required
                                    class="block w-full pl-10 pr-12 py-3 text-gray-900 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary focus:border-secondary focus:outline-none transition-all duration-200 placeholder-gray-400 shadow-sm hover:border-gray-400"
                                    placeholder="••••••••"
                                    :disabled="loading"
                                />
                                <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="relative flex items-center">
                                    <input 
                                        id="remember-me" 
                                        v-model="form.remember" 
                                        type="checkbox"
                                        class="sr-only peer"
                                        :disabled="loading"
                                    />
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-md peer-checked:border-indigo-500 peer-checked:bg-indigo-500 transition-colors duration-200 flex items-center justify-center">
                                        <svg v-if="form.remember" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <label for="remember-me" class="ml-3 text-sm text-gray-700 cursor-pointer select-none">
                                        Se souvenir de moi
                                    </label>
                                </div>
                            </div>

                            <router-link 
                                :to="{ name: 'forgot-password' }"
                                class="text-sm font-medium text-primary hover:text-secondary-light transition-colors duration-200 hover:underline"
                                :class="{ 'opacity-50 pointer-events-none': loading }"
                            >
                               Mot de passe oublié?
                            </router-link>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            :disabled="loading"
                            class="group relative w-full flex justify-center items-center py-3.5 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-secondary to-secondary-dark hover:from-seconadry-light hover:to-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-300 transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl"
                        >
                            <span v-if="loading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Connexion...
                            </span>
                            <span v-else class="flex items-center">
                                Connectez-vous                                <svg class="ml-2 h-5 w-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </span>
                        </button>
                    </div>

                    <!-- Sign Up Link -->
                    <div class="text-center pt-6">
                        <p class="text-gray-600 text-sm">
                            Vous n’avez pas de compte ?
                            <router-link 
                                :to="{ name: 'register' }" 
                                class="font-semibold text-primary hover:text-primary-light ml-1 transition-colors duration-200 hover:underline"
                                :class="{ 'opacity-50 pointer-events-none': loading }"
                            >
                               inscrivez-vous dès maintenant
                            </router-link>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center">
                <p class="text-gray-400 text-xs">
                    © 2026 MBL-service/TalentTop. Tous droit reserver.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
    email: '',
    password: '',
    remember: false
})

const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
    loading.value = true
    error.value = ''

    try {
        // Validation supplémentaire
        if (!form.email.trim() || !form.password.trim()) {
            throw new Error('Please fill in all fields')
        }

        if (!isValidEmail(form.email)) {
            throw new Error('Please enter a valid email address')
        }

        await authStore.login({
            email: form.email.trim(),
            password: form.password
        })

        // Redirection basée sur le rôle ou la route précédente
       router.push('/')
    } catch (err: any) {
        error.value = err.message || 'Invalid credentials. Please try again.'
        
        // Auto-hide error after 5 seconds
        setTimeout(() => {
            error.value = ''
        }, 5000)
    } finally {
        loading.value = false
    }
}

const isValidEmail = (email: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
}
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.bg-grid-slate-100 {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(241 245 249 / 0.5)'%3e%3cpath d='M0 .5H31.5V32'/%3e%3c/svg%3e");
}
</style>