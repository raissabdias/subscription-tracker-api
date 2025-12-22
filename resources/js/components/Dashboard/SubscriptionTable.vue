<template>
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
            <Column header="Ciclo" field="category" sortable>
                <template #body="slotProps">
                    {{ translateCycle(slotProps.data.category) }}
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
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="$emit('edit', slotProps.data)" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="$emit('delete', slotProps.data)" />
                </template>
            </Column>
            <template #empty> Nenhuma assinatura encontrada. </template>
        </DataTable>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';

const props = defineProps({
    subscriptions: { type: Array, default: () => [] },
    loading: Boolean
});

// Eventos que este componente dispara para o pai
const emit = defineEmits(['edit', 'delete']);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
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
.card-table {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
</style>