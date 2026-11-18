<script setup>
import { useForm } from '@inertiajs/vue3';
const form = useForm({ email: '', password: '' });
</script>

<template>
  <div class="mx-auto mt-24 max-w-sm rounded border bg-white p-6">
    <h1 class="mb-4 text-lg font-semibold">Entrar</h1>
    <form class="space-y-3" @submit.prevent="form.post('/login')">
      <input v-model="form.email" type="email" placeholder="E-mail" class="w-full rounded border px-3 py-2" />
      <input v-model="form.password" type="password" placeholder="Senha" class="w-full rounded border px-3 py-2" />
      <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
      <button class="w-full rounded bg-slate-900 py-2 text-white" :disabled="form.processing">Entrar</button>
    </form>
  </div>
</template>
