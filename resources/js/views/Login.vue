<template>
    <div class="login-container">
        <div class="card-login">
            <h2 style="text-align: center; margin-bottom: 2rem;">Subscription Tracker</h2>
            <form @submit.prevent="handleLogin" class="p-fluid">
                <div class="field">
                    <label for="email">E-mail</label>
                    <InputText id="email" v-model="form.email" type="email" placeholder="email@exemplo.com" />
                </div>
                <div class="field">
                    <label for="password">Senha</label>
                    <Password id="password" v-model="form.password" :feedback="false" toggleMask placeholder="Senha" />
                </div>
                <div class="field-btn">
                    <Button type="submit" label="Entrar" icon="pi pi-sign-in" :loading="loading" />
                </div>
                <Message v-if="errorMessage" severity="error" :closable="false" style="margin-top: 1rem">
                    {{ errorMessage }}
                </Message>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

const router = useRouter();
const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
    email: '',
    password: '',
    device_name: 'browser'
});

const handleLogin = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post('/api/login', form);
        const token = response.data.token;
        
        localStorage.setItem('auth_token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        
        router.push('/');
    } catch (error) {
        if (error.response?.status === 401) {
            errorMessage.value = 'Credenciais inválidas.';
        } else {
            errorMessage.value = 'Erro ao conectar ao servidor.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #f8f9fa;
}
.card-login {
    background: white;
    padding: 2.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    width: 100%;
    max-width: 400px;
}
.field { margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
.field-btn { margin-top: 2rem; }

:deep(.p-button) { width: 100%; }
:deep(.p-password), :deep(.p-inputtext) { width: 100%; }
</style>