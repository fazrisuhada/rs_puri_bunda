<template>
    <div class="card flex justify-center">
        <Dialog 
            v-model:visible="show"
            pt:root:class="!border-0 !bg-transparent" 
            pt:mask:class="backdrop-blur-sm"
        >
            <template #container="{ closeCallback }">
                <form @submit.prevent="handleSubmit">
                    <div class="flex flex-col px-8 py-8 gap-6 rounded-2xl" style="background-image: radial-gradient(circle at left top, var(--p-primary-400), var(--p-primary-700))">
                        <h1 class="text-2xl font-bold text-primary-50">{{ isLogin ? 'Sign In' : 'Sign Up' }}</h1>

                        <div class="inline-flex flex-col gap-2" v-if="!isLogin">
                            <label for="username" class="text-primary-50 font-semibold">Username</label>
                            <InputText 
                                id="username" 
                                type="text" 
                                v-model="input.username" 
                                class="!bg-white/20 !border-0 !p-4 !text-primary-50 w-80"
                                :class="{ '!border-red-500 !border-2': authStore.errors.username }"
                                @input="clearFieldError('username')"
                            />
                            <MessageComponent 
                                v-if="authStore.errors.username" 
                                :message="authStore.errors.username"
                            />
                        </div>

                        <div class="inline-flex flex-col gap-2">
                            <label for="email" class="text-primary-50 font-semibold">Email</label>
                            <InputText 
                                id="email" 
                                type="email" 
                                v-model="input.email" 
                                class="!bg-white/20 !border-0 !p-4 !text-primary-50 w-80"
                                :class="{ '!border-red-500 !border-2': authStore.errors.email }"
                                @input="clearFieldError('email')"
                            />
                            <MessageComponent 
                                v-if="authStore.errors.email" 
                                :message="authStore.errors.email"
                            />
                        </div>

                        <div class="inline-flex flex-col gap-2">
                            <label for="password" class="text-primary-50 font-semibold">Password</label>
                            <InputText 
                                id="password" 
                                v-model="input.password" 
                                class="!bg-white/20 !border-0 !p-4 !text-primary-50 w-80" 
                                type="password"
                                :class="{ '!border-red-500 !border-2': authStore.errors.password }"
                                @input="clearFieldError('password')"
                            />
                            <MessageComponent 
                                v-if="authStore.errors.password" 
                                :message="authStore.errors.password"
                            />
                        </div>

                        <!-- General Error Message -->
                        <div class="flex justify-center items-center">
                            <MessageComponent 
                                v-if="authStore.errors.general" 
                                :message="authStore.errors.general"
                                class="!mb-2"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button 
                                label="Cancel" 
                                @click="handleCancel(closeCallback)" 
                                text 
                                class="!p-4 w-full !text-slate-100 !border !border-white/30 hover:!bg-white/10"
                                :disabled="authStore.isLoading"
                            />
                            <Button 
                                :label="isLogin ? 'Sign-In' : 'Sign-Up'" 
                                type="submit" 
                                text 
                                class="!p-4 w-full !text-slate-100 !border !border-white/30 hover:!bg-white/10"
                                :disabled="authStore.isLoading"
                                :loading="authStore.isLoading"
                            />
                        </div>

                        <div class="flex justify-center items-center mt-4">
                            <p v-if="isLogin">
                                Don't have an account ? 
                                <span class="text-primary-50 cursor-pointer hover:text-emerald-300" @click="handleSiginUpClick" >Sign Up</span>
                            </p>
                            <p v-else>
                                Already have an account ? 
                                <span class="text-primary-50 cursor-pointer hover:text-emerald-300" @click="handleSiginInClick">Sign In</span>
                            </p>
                        </div>
                    </div>
                </form>
            </template>
        </Dialog>
    </div>
</template>

<script setup>
    import { reactive, ref, watch } from "vue";
    import { useAuthenticationStore } from "@/stores/authStore.js";
    import MessageComponent from "./MessageComponent.vue";

    // props
    const show = defineModel('visible', {
        type: Boolean,
        default: false
    });

    // state
    const input = reactive({
        username: '',
        email: '',
        password: ''
    });
    const isLogin = ref(true);

    const authStore = useAuthenticationStore();
    const { loginStore, registerStore, setFieldError, clearErrors } = authStore;

    function clearFieldError(field) {
        authStore.errors[field] = '';
    }

    function clearForm() {
        input.username = '';
        input.email = '';
        input.password = '';
    }

    function handleCancel(closeCallback) {
        clearForm();
        clearErrors();
        closeCallback();
    }

    function handleSiginInClick() {
        isLogin.value = true;
        clearErrors();
        clearForm();
    }

    function handleSiginUpClick() {
        isLogin.value = false;
        clearErrors();
        clearForm();
    }
    
    async function handleSubmit() {
        try {
            if(isLogin.value === true) {
                await loginStore(input);
            } else {
                await registerStore(input);
            }
            
            // Perbaikan: Cek apakah login/register berhasil
            if (authStore.currentUser) {
                clearForm();
                clearErrors();
            }
        } catch (error) {
            console.error('Auth error:', error);
        }
    }

    // Perbaikan: Watch untuk menutup dialog jika login berhasil
    watch(() => authStore.currentUser, (newUser) => {
        if (newUser) {
            show.value = false;
        }
    });

    // Perbaikan: Reset form saat dialog ditutup
    watch(() => show.value, (newValue) => {
        if (!newValue) {
            clearForm();
            clearErrors();
            isLogin.value = true;
        }
    });
</script>