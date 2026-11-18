<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
const pagina = usePage();
const menu = [
  { href: '/', rotulo: 'Visão geral' },
  { href: '/fluxos', rotulo: 'Fluxos' },
  { href: '/resolucoes', rotulo: 'Resoluções' },
];
const ativo = (href) => (href === '/' ? pagina.url === '/' : pagina.url.startsWith(href));
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <nav class="flex items-center gap-4 text-sm">
          <span class="font-semibold">Orquestrador de bugs</span>
          <Link v-for="m in menu" :key="m.href" :href="m.href" class="hover:underline"
            :class="ativo(m.href) ? 'font-medium text-slate-900' : 'text-slate-500'">{{ m.rotulo }}</Link>
        </nav>
        <div class="flex items-center gap-3 text-sm text-slate-600">
          <span>{{ pagina.props.usuario?.email }}</span>
          <button class="rounded border px-2 py-1 hover:bg-slate-100" @click="router.post('/sair')">Sair</button>
        </div>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6"><slot /></main>
  </div>
</template>
