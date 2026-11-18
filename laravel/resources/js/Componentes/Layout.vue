<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
const pagina = usePage();
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <Link href="/fluxos" class="font-semibold">Fluxos de correção</Link>
        <div class="flex items-center gap-3 text-sm text-slate-600">
          <span>{{ pagina.props.usuario?.email }}</span>
          <button class="rounded border px-2 py-1 hover:bg-slate-100" @click="router.post('/sair')">Sair</button>
        </div>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6"><slot /></main>
  </div>
</template>
