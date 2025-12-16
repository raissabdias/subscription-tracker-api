<template>
    <div style="font-family: sans-serif; text-align: center; padding: 50px;">
        <h1>Olá, Vue 3 no Laravel! 🚀</h1>
        <p>Se você está vendo isso, o front-end funcionou.</p>
        
        <button @click="count++">Cliques: {{ count }}</button>
    </div>
</template>

<script setup>
    import { ref } from 'vue';

    const count = ref(0);
</script>