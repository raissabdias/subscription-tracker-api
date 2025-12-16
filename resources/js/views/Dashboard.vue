<template>
    <div class="dashboard-container">
        <div class="header">
            <h1>Minhas Assinaturas</h1>
            <Button label="Sair" icon="pi pi-sign-out" severity="secondary" @click="handleLogout" />
        </div>
        <div class="card-table">
            <DataTable :value="subscriptions" tableStyle="min-width: 50rem" :loading="loading">
                <Column field="id" header="#"></Column>
                <Column field="name" header="Serviço"></Column>
                <Column header="Preço">
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.price) }}
                    </template>
                </Column>
                <Column header="Ciclo">
                    <template #body="slotProps">
                        {{ translateCycle(slotProps.data.cycle) }}
                    </template>
                </Column>
                <Column header="Próximo Pagto">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.next_payment) }}
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag'; 

const router = useRouter();
const subscriptions = ref([]);
const loading = ref(true);

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

// Formatação dos valores para reais
const formatCurrency = (valueInCents) => {
    if (!valueInCents) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valueInCents / 100); // Lembre-se de dividir por 100!
};

// Formatação de data no padrão pt-br
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('pt-BR');
};

// Traduz o ENUM do banco para Português
const translateCycle = (cycle) => {
    const map = {
        'monthly': 'Mensal',
        'yearly': 'Anual',
        'weekly': 'Semanal'
    };
    return map[cycle] || cycle;
};

// Cores do status
const getStatusSeverity = (status) => {
    switch (status) {
        case 'active': return 'success';
        case 'inactive': return 'danger';
        default: return 'info';
    }
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

.card-table {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
</style>