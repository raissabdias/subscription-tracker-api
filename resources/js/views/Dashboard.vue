<template>
    <div class="dashboard-container">
        <div class="header">
            <h1>Minhas Assinaturas</h1>
            <div class="actions">
                <Button label="Nova Assinatura" icon="pi pi-plus" @click="openNewSubscription" />
                <Button label="Sair" icon="pi pi-sign-out" severity="secondary" @click="handleLogout" class="ml-2" />
            </div>
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
                <Column header="Ações" :exportable="false" style="min-width:8rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteSubscription(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>
        </div>
        <Dialog v-model:visible="dialogVisible" modal header="Nova Assinatura" :style="{ width: '400px' }">
            <div class="form-content">
                
                <div class="field">
                    <label for="name">Nome do Serviço</label>
                    <InputText id="name" v-model="form.name" required autofocus />
                </div>

                <div class="field">
                    <label for="price">Preço</label>
                    <InputNumber id="price" v-model="form.price" mode="currency" currency="BRL" locale="pt-BR" />
                </div>

                <div class="field">
                    <label for="cycle">Ciclo</label>
                    <Select id="cycle" v-model="form.billing_cycle" :options="cycleOptions" optionLabel="label" optionValue="value" placeholder="Selecione" />
                </div>

                <div class="field">
                    <label for="date">Primeiro Pagamento</label>
                    <DatePicker id="date" v-model="form.next_payment" dateFormat="dd/mm/yy" showIcon fluid />
                </div>

            </div>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" text @click="dialogVisible = false" />
                <Button label="Salvar" icon="pi pi-check" @click="saveSubscription" :loading="saving" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const subscriptions = ref([]);
const loading = ref(true);
const saving = ref(false);
const dialogVisible = ref(false);

const form = reactive({
    name: '',
    price: null,
    billing_cycle: '',
    next_billing_date: null
});
const cycleOptions = [
    {label: 'Mensal', value: 'monthly'},
    {label: 'Anual', value: 'yearly'},
    {label: 'Semanal', value: 'weekly'}
];

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

// Ativar modal de adição
const openNewSubscription = () => {
    // Limpa o formulário antes de abrir
    form.name = '';
    form.price = null;
    form.billing_cycle = '';
    form.next_billing_date = null;
    dialogVisible.value = true;
};

// Salvar nova assinatura
const saveSubscription = async () => {
    saving.value = true;

    try {
        const payload = {
            ...form,
            price: form.price ? Math.round(form.price * 100) : 0, // Alterar para inteiro (29.90 -> 2990)
            next_billing_date: form.next_billing_date ? form.next_billing_date.toISOString().split('T')[0] : null // Extrair YYYY-MM-DD
        };

        await axios.post('/api/subscriptions', payload);
        
        dialogVisible.value = false;
        await fetchSubscriptions();

        toast.add({
            severity: 'success',
            detail: form.name + ' adicionado com sucesso.',
            life: 5000
        });

    } catch (error) {
        toast.add({
            severity: 'error',
            detail: 'Erro ao salvar assinatura. Verifique os campos.',
            life: 5000
        });
    } finally {
        saving.value = false;
    }
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

.field { margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
.field label { font-weight: bold; color: #4b5563; }
:deep(.p-inputtext), :deep(.p-inputnumber) { width: 100%; }
.ml-2 { margin-left: 0.5rem; }
</style>