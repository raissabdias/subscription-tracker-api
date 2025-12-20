<template>
    <Dialog v-model:visible="isVisible" modal :header="form.id ? 'Editar Assinatura' : 'Nova Assinatura'" :style="{ width: '400px' }">
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
            <Button label="Cancelar" icon="pi pi-times" text @click="closeModal" />
            <Button label="Salvar" icon="pi pi-check" @click="handleSave" :loading="saving" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';

const props = defineProps({
    visible: Boolean,
    initialData: Object
});

const emit = defineEmits(['update:visible', 'saved']);
const toast = useToast();
const saving = ref(false);

// Sincroniza a visibilidade com o Pai
const isVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const form = reactive({
    name: '',
    price: null,
    billing_cycle: '',
    next_payment: null
});

const resetForm = () => {
    form.id = null;
    form.name = '';
    form.price = null;
    form.billing_cycle = '';
    form.next_payment = null;
};

const cycleOptions = [
    {label: 'Mensal', value: 'monthly'},
    {label: 'Anual', value: 'yearly'},
    {label: 'Semanal', value: 'weekly'}
];

// Função auxiliar para converter String ISO -> Date Object
const parseDateISO = (dateString) => {
    if (!dateString) return null;
    const [year, month, day] = dateString.split('-');
    return new Date(Number(year), Number(month) - 1, Number(day), 12, 0, 0);
};

// Preencher form com dados de edição vindos do Pai
watch(() => props.initialData, (newData) => {
    if (newData) {
        form.id = newData.id;
        form.name = newData.name;
        form.price = newData.price / 100;
        form.billing_cycle = newData.billing_cycle;
        form.next_payment = parseDateISO(newData.next_payment);
    }
}, { immediate: true });

// Resetar form ao fechar modal
watch(() => props.visible, (isVisible) => {
    if (!isVisible) {
        resetForm();
    }
});

const closeModal = () => {
    emit('update:visible', false);
};

const handleSave = async () => {
    saving.value = true;
    try {
        // Função auxiliar para converter Date Object -> String YYYY-MM-DD
        const formatToYmdLocal = (dateObj) => {
            if (!dateObj) return null;
            const year = dateObj.getFullYear();
            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
            const day = String(dateObj.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const payload = {
            name: form.name,
            billing_cycle: form.billing_cycle,
            category: 'Outros', // Hardcoded por enquanto até implementarmos o select de categoria
            price: form.price ? Math.round(form.price * 100) : 0,
            next_payment: formatToYmdLocal(form.next_payment)
        };

        if (form.id) {
            await axios.put(`/api/subscriptions/${form.id}`, payload);
            toast.add({ severity: 'success', summary: 'Atualizado', detail: 'Assinatura atualizada!', life: 3000 });
        } else {
            await axios.post('/api/subscriptions', payload);
            toast.add({ severity: 'success', summary: 'Criado', detail: 'Assinatura criada!', life: 3000 });
        }
        
        // Avisa o pai para recarregar a lista
        emit('saved');
        closeModal();

    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Falha ao guardar dados.', life: 3000 });
        console.error(error);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
.field { margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
.field label { font-weight: bold; color: #4b5563; }
:deep(.p-inputtext), :deep(.p-inputnumber) { width: 100%; }
</style>