---
planStatus:
  planId: plan-launch-board-bolao
  title: "Lançamento funcional do bolão"
  status: in-development
  planType: initiative
  priority: high
  owner: cassio
  stakeholders: []
  tags: [launch, php, mysql, auth, pools, predictions, admin]
  created: "2026-06-05"
  startDate: "2026-06-05"
  updated: "2026-06-05T17:45:00.000Z"
  progress: 22
---

# Lançamento funcional do bolão

## Objetivo

Colocar uma versão funcional do bolão no ar rapidamente, usando a stack atual e evitando escopo que não impacta a estreia.

## Cards do board

- `task_19e98c47f9571b8` Runtime local padronizado para o bolão
- `task_19e98c47f96ee99` Pacote de configuração de produção fechado
- `task_19e98c47f98d725` Banco bootstrapado com schema e seed da Copa
- `task_19e98c47f996162` Fluxos de autenticação e entrada validados
- `task_19e98c47f9ab26c` Fluxos de bolão privado e convite validados
- `task_19e98c47f9b4152` Fluxos de palpites e bônus validados
- `task_19e98c47f9dd2dc` Fluxos administrativos críticos validados
- `task_19e98c47f9e9233` Smoke test integrado de lançamento executado
- `task_19e98c47f9f46f4` Handoff final de release preparado

## Decisões já fechadas

- `dec_19e98d6278b6a6d` Manter lançamento em PHP puro e MySQL na hospedagem compartilhada já validada
- `dec_19e98d6278c76f1` Operar a v1 com lançamento manual de resultados pelo admin
- `dec_19e98d6278d54b2` Tratar automação de resultados e melhorias cosméticas como pós-lançamento

## Ideias fora do caminho crítico

- `ida_19e98d6278e2ab4` Integrar captura automática de resultados via API de futebol no cron
- `ida_19e98d6278f1908` Evoluir o board com tags funcionais e owners por área após a estreia

## Dependências

- `task_19e98c47f9571b8` -> `task_19e98c47f96ee99`
- `task_19e98c47f96ee99` -> `task_19e98c47f98d725`
- `task_19e98c47f98d725` -> `task_19e98c47f996162`
- `task_19e98c47f98d725` -> `task_19e98c47f9ab26c`
- `task_19e98c47f98d725` -> `task_19e98c47f9b4152`
- `task_19e98c47f98d725` -> `task_19e98c47f9dd2dc`
- `task_19e98c47f996162` -> `task_19e98c47f9e9233`
- `task_19e98c47f9ab26c` -> `task_19e98c47f9e9233`
- `task_19e98c47f9b4152` -> `task_19e98c47f9e9233`
- `task_19e98c47f9dd2dc` -> `task_19e98c47f9e9233`
- `task_19e98c47f9e9233` -> `task_19e98c47f9f46f4`

## Trilhas paralelas seguras

Depois de `task_19e98c47f98d725`, estes quatro cards podem rodar em paralelo sem interferir estruturalmente uns nos outros:

- `task_19e98c47f996162`
- `task_19e98c47f9ab26c`
- `task_19e98c47f9b4152`
- `task_19e98c47f9dd2dc`

## Fora do caminho crítico

- Automação de resultados via API
- Melhorias cosméticas
- Refactors sem impacto em lançamento
