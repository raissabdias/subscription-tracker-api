<template>
    <div class="dashboard-container">
        <div class="header">
            <h1>Minhas Assinaturas</h1>
            <div class="actions">
                <Button label="Nova Assinatura" icon="pi pi-plus" @click="openCreateModal" />
                <Button label="Sair" icon="pi pi-sign-out" severity="secondary" @click="handleLogout" class="ml-2" />
            </div>
        </div>
        <DashboardStats :subscriptions="subscriptions" />
        <SubscriptionTable :subscriptions="subscriptions" :loading="loading" @edit="openEditModal"@delete="confirmDeleteSubscription" />
        <SubscriptionForm v-model:visible="dialogVisible" :initial-data="editingSubscription" @saved="fetchSubscriptions"/>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import Button from 'primevue/button';

import DashboardStats from '../components/Dashboard/DashboardStats.vue';
import SubscriptionTable from '../components/Dashboard/SubscriptionTable.vue';
import SubscriptionForm from '../components/Dashboard/SubscriptionForm.vue';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const subscriptions = ref([]);
const loading = ref(true);
const dialogVisible = ref(false);
const editingSubscription = ref(null);

// Validar autenticação/token
onMounted(async () => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        router.push('/login');
        return;
    }

    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    await fetchSubscriptions();
});

// Popular listagem de assinaturas
const fetchSubscriptions = async () => {
    try {
        const response = await axios.get('/api/subscriptions');
        // O Laravel Resource retorna os dados dentro de "data"
        subscriptions.value = response.data.data; 
    } catch (error) {
        if (error.response?.status === 401) {
            handleLogout(); // Token expirou
        }
        console.error('Erro ao buscar assinaturas:', error);
    } finally {
        loading.value = false;
    }
};

// Deslogar
const handleLogout = async () => {
    await axios.post('/api/logout');

    localStorage.removeItem('auth_token');
    router.push('/login');
};

// Ativar modal de adição
const openCreateModal = () => {
    editingSubscription.value = null;
    dialogVisible.value = true;
};

// Deletar assinatura
const confirmDeleteSubscription = (subscription) => {
    confirm.require({
        message: `Tem a certeza que deseja apagar ${subscription.name}?`,
        header: 'Confirmação',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancelar',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Apagar',
            severity: 'danger'
        },
        accept: async () => {
            try {
                await axios.delete(`/api/subscriptions/${subscription.id}`);
                toast.add({
                    severity: 'success',
                    summary: 'Apagado',
                    detail: 'Assinatura removida com sucesso.',
                    life: 3000
                });
                await fetchSubscriptions();
            } catch (error) {
                toast.add({
                    severity: 'error',
                    summary: 'Erro',
                    detail: 'Erro ao remover assinatura.',
                    life: 3000
                });
            }
        }
    });
};

// Editar assinatura
const openEditModal = (subscription) => {
    editingSubscription.value = subscription; 
    dialogVisible.value = true;
};
</script>

<style scoped>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    font-family: sans-serif;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}
</style>