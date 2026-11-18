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
    rotulo: 'Agente Claude',
    campos: [
      { chave: 'prompt', rotulo: 'Instrução', area: true, dica: 'Corrija o bug: {descricao}' },
      { chave: 'diretorio', rotulo: 'Diretório', dica: '{diretorio}' },
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
  abrir_pr: ['ok', 'sem_alteracao'],
};
