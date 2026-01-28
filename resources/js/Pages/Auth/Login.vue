<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useTranslations } from '@/composables/useTranslations';

const { __ } = useTranslations();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 text-center">{{ __('login') }}</h2>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('email') }}</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="vas@email.com"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('password') }}</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    placeholder="••••••••"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-2.5 px-4 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <span v-if="form.processing">{{ __('saving') }}</span>
                <span v-else>{{ __('login') }}</span>
            </button>
        </form>
    </GuestLayout>
</template>
