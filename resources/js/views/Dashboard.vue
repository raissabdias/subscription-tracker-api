<template>
    <div class="dashboard-container">
        <div class="header">
            <h1>Minhas Assinaturas</h1>
            <div class="actions">
                <Button label="Nova Assinatura" icon="pi pi-plus" @click="openNewSubscription" />
                <Button label="Sair" icon="pi pi-sign-out" severity="secondary" @click="handleLogout" class="ml-2" />
            </div>
        </div>
        <div class="stats-container">
            <div class="kpi-column">
                <Card style="background: #eff6ff; border-left: 4px solid #3b82f6">
                    <template #title>Assinaturas Ativas</template>
                    <template #content>
                        <p class="kpi-value">{{ totalActive }}</p>
                    </template>
                </Card>
                <Card style="background: #f0fdf4; border-left: 4px solid #22c55e">
                    <template #title>Custo Mensal Estimado</template>
                    <template #content>
                        <p class="kpi-value">{{ formatCurrency(monthlyCost) }}</p>
                    </template>
                </Card>
            </div>
            <div class="chart-column">
                <Card class="h-full">
                    <template #title>Distribuição de Gastos</template>
                    <template #content>
                        <div class="chart-wrapper" v-if="totalActive > 0">
                            <Chart type="doughnut" :data="chartData" :options="chartOptions" class="w-full" />
                        </div>
                        <div v-else class="text-center text-gray-500 py-4">
                            Sem dados para exibir
                        </div>
                    </template>
                </Card>
            </div>
        </div>
        <div class="card-table">
            <DataTable :value="subscriptions" tableStyle="min-width: 50rem" :loading="loading" paginator :rows="5" :rowsPerPageOptions="[5, 10, 20]" v-model:filters="filters" :globalFilterFields="['name']">
                <template #header>
                    <div class="flex justify-end">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Pesquisar serviço..." />
                        </IconField>
                    </div>
                </template>
                <Column field="id" header="#" sortable></Column>
                <Column field="name" header="Serviço" sortable></Column>
                <Column header="Preço" field="price" sortable>
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.price) }}
                    </template>
                </Column>
                <Column header="Ciclo" field="cycle" sortable>
                    <template #body="slotProps">
                        {{ translateCycle(slotProps.data.cycle) }}
                    </template>
                </Column>
                <Column header="Próximo Pagto" field="next_payment" sortable>
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.next_payment) }}
                    </template>
                </Column>
                <Column header="Status" field="status" sortable>
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                    </template>
                </Column>
                <Column header="Ações" :exportable="false" style="min-width:8rem">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editSubscription(slotProps.data)" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteSubscription(slotProps.data)" />
                    </template>
                </Column>
                <template #empty> Nenhuma assinatura encontrada. </template>
            </DataTable>
        </div>
        <Dialog v-model:visible="dialogVisible" modal :header="form.id ? 'Editar Assinatura' : 'Nova Assinatura'" :style="{ width: '400px' }">
            <div class="form-content">
                <div class="field">
                    <label for="name">Nome do Serviço</label>
                    <InputText id="name" v-model="form.name" required autofocus fluid />
                </div>
                <div class="field">
                    <label for="price">Preço</label>
                    <InputNumber id="price" v-model="form.price" mode="currency" currency="BRL" locale="pt-BR" fluid />
                </div>
                <div class="field">
                    <label for="cycle">Ciclo</label>
                    <Select id="cycle" v-model="form.billing_cycle" :options="cycleOptions" optionLabel="label" optionValue="value" placeholder="Selecione" fluid />
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
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode } from '@primevue/core/api';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Card from 'primevue/card';
import Chart from 'primevue/chart';

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

const filters = ref({
    global: {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    }
});

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
        const formatDatePayload = (date) => {
            if (!date) return null;
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const payload = {
            ...form,
            price: form.price ? Math.round(form.price * 100) : 0, // Alterar para inteiro (29.90 -> 2990)
            next_payment: formatDatePayload(form.next_payment)
        };

        if (form.id) {
            await axios.put(`/api/subscriptions/${form.id}`, payload);
            toast.add({
                severity: 'success',
                detail: form.name + ' alterado com sucesso.',
                life: 5000
            });
        } else {
            await axios.post('/api/subscriptions', payload);
            toast.add({
                severity: 'success',
                detail: form.name + ' adicionado com sucesso.',
                life: 5000
            });
        }
        
        dialogVisible.value = false;
        await fetchSubscriptions();

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

// Editar assinatura
const editSubscription = (subscription) => {
    form.id = subscription.id;
    form.name = subscription.name;
    form.billing_cycle = subscription.cycle;
    form.price = subscription.price / 100; 

    if (subscription.next_payment) {
        const [year, month, day] = subscription.next_payment.split('-');
        form.next_payment = new Date(Number(year), Number(month) - 1, Number(day), 12, 0, 0);
    } else {
        form.next_payment = null;
    }
    
    dialogVisible.value = true;
};

// KPI de assinaturas ativas
const totalActive = computed(() => {
    return subscriptions.value.filter(sub => sub.status === 'active').length;
});

// KPI de custos mensais
const monthlyCost = computed(() => {
    const activeSubs = subscriptions.value.filter(sub => sub.status === 'active');
    
    // Soma calculando a proporção mensal
    const total = activeSubs.reduce((acc, sub) => {
        const price = Number(sub.price); // Em centavos
        
        if (sub.cycle === 'yearly') {
            return acc + (price / 12);
        }
        if (sub.cycle === 'weekly') {
            return acc + (price * 4);
        }
        return acc + price;
    }, 0);

    return total; 
});

// Popular dados do gráfico de assinaturas
const chartData = computed(() => {
    const activeSubs = subscriptions.value.filter(s => s.status === 'active');
    const labels = activeSubs.map(s => s.name);
    const data = activeSubs.map(s => {
        const price = Number(s.price);
        if (s.cycle === 'yearly') return price / 12;
        if (s.cycle === 'weekly') return price * 4;
        return price;
    });

    return {
        labels: labels,
        datasets: [
            {
                data: data,
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                    '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'
                ],
                hoverBackgroundColor: [
                    '#2563eb', '#059669', '#d97706', '#dc2626', 
                    '#7c3aed', '#db2777', '#4f46e5', '#0d9488'
                ]
            }
        ]
    };
});

// Configurações do gráfico
const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                usePointStyle: true,
                padding: 15 
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.label || '';
                    if (label) {
                        label += ': ';
                    }

                    // Converter os centavos para reais
                    if (context.parsed !== null) {
                        label += new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(context.raw / 100);
                    }

                    return label;
                }
            }
        }
    },
    layout: {
        padding: {
            left: 0, 
            right: 30,
            top: 0,
            bottom: 0
        }
    },
    cutout: '65%'
});

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
    const [year, month, day] = dateString.split('-');
    const date = new Date(Number(year), Number(month) - 1, Number(day), 12, 0, 0);
    return date.toLocaleDateString('pt-BR');
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

.stats-container {
    display: grid;
    grid-template-columns: 1fr 2fr; 
    gap: 1.5rem;
    margin-bottom: 2rem;
    align-items: stretch;
}

.kpi-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    height: 100%;
}

.kpi-column .p-card {
    flex: 1; 
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.chart-column .p-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.chart-wrapper {
    flex-grow: 1;
    position: relative;
    min-height: 200px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.kpi-value {
    font-size: 2rem;
    font-weight: bold;
    color: #1f2937;
    margin: 0;
}

.field { margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
.field label { font-weight: bold; color: #4b5563; }
:deep(.p-inputtext), :deep(.p-inputnumber) { width: 100%; }
.ml-2 { margin-left: 0.5rem; }
.h-full { height: 100%; }
.text-center { text-align: center; }
.text-gray-500 { color: #6b7280; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
</style>