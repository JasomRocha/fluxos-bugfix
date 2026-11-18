// Campos editaveis de cada tipo de no, o mesmo vocabulario do motor.
export const TIPOS = {
  entrada: { rotulo: 'Entrada', campos: [] },
  git_preparar: {
    rotulo: 'Preparar repositório',
    campos: [
      { chave: 'repositorio', rotulo: 'Repositório', dica: '{repositorio}' },
      { chave: 'branch', rotulo: 'Branch', dica: 'correcao/{bug}' },
    ],
  },
  agente: {
    rotulo: 'Agente de código',
    campos: [
      { chave: 'prompt', rotulo: 'Instrução', area: true, dica: 'Corrija o bug: {descricao}' },
      { chave: 'diretorio', rotulo: 'Diretório', dica: '{diretorio}' },
      { chave: 'provedor', rotulo: 'Provedor (ollama ou openai; vazio = padrão do servidor)', dica: 'ollama' },
      { chave: 'modelo', rotulo: 'Modelo (vazio = padrão do servidor)', dica: 'qwen2.5-coder:14b' },
      { chave: 'max_turnos', rotulo: 'Máximo de turnos', numero: true },
    ],
  },
  comando: {
    rotulo: 'Comando',
    campos: [
      { chave: 'comando', rotulo: 'Comando', dica: '{comando_testes}' },
      { chave: 'diretorio', rotulo: 'Diretório', dica: '{diretorio}' },
      { chave: 'chave', rotulo: 'Nome do resultado', dica: 'testes' },
    ],
  },
  etapa_guiada: {
    rotulo: 'Etapa guiada (Claude Code)',
    campos: [
      { chave: 'titulo', rotulo: 'Título', dica: 'Investigar o bug' },
      { chave: 'instrucao', rotulo: 'Instrução', area: true, dica: 'O que o Claude Code deve fazer. Aceita {variavel} e {etapa_resultado[campo]}' },
      { chave: 'chave', rotulo: 'Nome do resultado', dica: 'investigar' },
    ],
  },
  aprovacao: {
    rotulo: 'Aprovação',
    campos: [{ chave: 'pergunta', rotulo: 'Pergunta', area: true, dica: 'Abrir o PR?' }],
  },
  abrir_pr: {
    rotulo: 'Abrir PR',
    campos: [
      { chave: 'titulo', rotulo: 'Título', dica: 'Correção do bug {bug}' },
      { chave: 'corpo', rotulo: 'Descrição', area: true },
    ],
  },
  fim: { rotulo: 'Fim', campos: [] },
};

// Rotas que cada tipo costuma produzir, para sugerir na aresta.
export const ROTAS = {
  comando: ['ok', 'falha'],
  aprovacao: ['aprovado', 'recusado'],
  etapa_guiada: ['ok', 'falha'],
  abrir_pr: ['ok', 'sem_alteracao'],
  agente: ['ok', 'limite', 'sem_alteracao'],
};
