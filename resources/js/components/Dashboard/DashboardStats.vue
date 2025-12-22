<template>
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
</template>

<script setup>
import { computed, ref } from 'vue';
import Card from 'primevue/card';
import Chart from 'primevue/chart';

// Recebe os dados do Pai com proteção de array vazio
const props = defineProps({
    subscriptions: {
        type: Array,
        default: () => [] 
    }
});

// Helper seguro para pegar a lista
const getSubs = () => props.subscriptions || [];

// KPI de assinaturas ativas
const totalActive = computed(() => {
    return getSubs().filter(sub => sub.status === 'active').length;
});

// KPI de custos mensais
const monthlyCost = computed(() => {
    const activeSubs = getSubs().filter(sub => sub.status === 'active');
    
    return activeSubs.reduce((acc, sub) => {
        const price = Number(sub.price);
        if (sub.cycle === 'yearly') return acc + (price / 12);
        if (sub.cycle === 'weekly') return acc + (price * 4);
        return acc + price;
    }, 0);
});

// Popular dados do gráfico de assinaturas
const chartData = computed(() => {
    const activeSubs = getSubs().filter(s => s.status === 'active');

    const groups = activeSubs.reduce((acc, sub) => {
        const category = sub.category || 'Outros';
        const priceInCents = Number(sub.price || 0);
        let monthlyCents = priceInCents;
        
        // Ajusta conforme o ciclo
        if (sub.cycle === 'yearly') {
            monthlyCents = priceInCents / 12;
        } else if (sub.cycle === 'weekly') {
            monthlyCents = priceInCents * 4;
        }

        acc[category] = (acc[category] || 0) + monthlyCents;
        
        return acc;
    }, {});
    
    const labels = Object.keys(groups);
    const data = Object.values(groups).map(cents => (cents / 100).toFixed(2));

    return {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'],
            hoverBackgroundColor: ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#db2777', '#4f46e5', '#0d9488']
        }]
    };
});

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { usePointStyle: true, padding: 20 } },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.label || '';
                    if (label) label += ': ';
                    if (context.parsed !== null) {
                        label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.raw);
                    }
                    return label;
                }
            }
        }
    },
    layout: { padding: { left: 0, right: 20, top: 0, bottom: 0 } },
    cutout: '70%'
});

const formatCurrency = (value) => {
    if (!value && value !== 0) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value / 100);
};
</script>

<style scoped>
    
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

.h-full { height: 100%; }
.text-center { text-align: center; }
.text-gray-500 { color: #6b7280; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
</style>