# Bolão da Copa do Mundo 2026 — Plano de Implementação

## Contexto

O objetivo é um site de **bolão** (palpites) para a Copa do Mundo FIFA 2026, para uma
brincadeira entre amigos, **sem nenhuma propaganda**. O usuário se incomoda com sites
cheios de anúncios (referência: `bolaopessoal.com.br`, que também é PHP).

Cada usuário palpita o placar dos **104 jogos** da Copa (48 seleções, 12 grupos,
11/06 a 19/07/2026) dentro de um **bolão**. Palpites só podem ser alterados até **5 minutos
antes** do início de cada partida. O sistema calcula pontos por palpite e monta um **ranking**.
Há também palpites **bônus** (campeão, vice, etc.). Bolões são **privados**, acessados por
**link de convite**: o usuário pode criar o próprio bolão ou participar de um para o qual foi convidado.

> ⚠️ **Urgência:** hoje é **2026-06-05**; a Copa começa em **2026-06-11 (6 dias)**. O sorteio
> dos grupos já ocorreu, então todos os confrontos da fase de grupos já são conhecidos e podem
> ser carregados (seed) de imediato. O plano prioriza ter o essencial no ar rápido.

### Decisões já confirmadas com o usuário
- **Stack:** PHP puro (sem framework) + **MySQL**, em **hospedagem compartilhada já contratada**
  (cPanel-style). Deploy por **FTP**. Sem Node/Supabase, sem build, sem custo de servidor extra.
- **Login:** e-mail + senha.
- **Pontuação:** detalhada (ver tabela abaixo), porém **configurável por bolão**.
- **Escopo v1:** palpites de placar por jogo **+ bônus** (campeão, vice, 3º, 4º, time do artilheiro).
- **Resultados reais:** **admin digita manualmente** na v1; código preparado para ligar
  busca automática por API (via cron) depois.
- **Bolões:** apenas privados, por link de convite. **Sem dinheiro** (só ranking).
- **Sem propaganda.** Idioma pt-BR; horários exibidos no fuso de **Brasília**.

## Regra de pontuação (detalhada, configurável)

Para um jogo com palpite `(pa, pb)` e resultado real `(ra, rb)`, avaliado em ordem:

| Condição | Pontos |
|---|---|
| Placar exato (`pa==ra` e `pb==rb`) | **10** |
| Acertou o vencedor/empate **e** o saldo de gols (`sinal` igual **e** `pa-pb == ra-rb`) | **7** |
| Acertou o vencedor/empate **e** os gols de um dos times (`pa==ra` ou `pb==rb`) | **5** |
| Acertou só o vencedor/empate (mesmo `sinal` de `pa-pb` e `ra-rb`) | **3** |
| Errou | **0** |

`sinal(x)`: `>0` time da casa vence, `<0` visitante vence, `=0` empate. Os 5 valores ficam em
colunas na tabela `pools` (defaults acima) para ajuste futuro sem mexer no código.

**Bônus** (resolvidos após o fim do torneio; valores configuráveis): campeão **30**, vice **20**,
3º **15**, 4º **10**, time do artilheiro **15**. (Palpites de "quem avança de fase" ficam como
extensão opcional pós-v1 para não atrasar o lançamento.)

## Arquitetura e estrutura de arquivos

PHP puro com páginas server-rendered + um header/footer e includes compartilhados. PDO + prepared
statements, `password_hash`, sessões PHP, tokens CSRF nos formulários. CSS próprio, responsivo
(mobile-first), sem framework de front. Um pouco de JS vanilla para o "preencher tudo" e salvar palpites.

```
/ (raiz do public_html no host)
  index.php              # landing: se logado → dashboard; senão → login/registro
  login.php  registro.php  logout.php  recuperar-senha.php  verificar-email.php
  dashboard.php          # meus bolões; criar bolão; entrar por convite
  bolao.php              # visão do bolão (abas: palpites / ranking / bônus / membros)
  palpites.php           # grade de jogos para palpitar (+ palpite padrão, preencher tudo)
  ranking.php            # classificação do bolão (filtros por rodada/data)
  bonus.php              # palpites de campeão/vice/3º/4º/artilheiro
  convite.php            # entrar via token de convite
  /admin/
    index.php  resultados.php  jogos.php  selecoes.php  torneio.php  usuarios.php
  /api/                  # endpoints AJAX (retornam JSON)
    salvar_palpite.php  preencher_todos.php  salvar_bonus.php
  /includes/
    config.php           # credenciais do MySQL + constantes (proteger acesso)
    db.php               # conexão PDO
    auth.php             # sessão, login obrigatório, helpers de usuário/admin
    csrf.php  functions.php  scoring.php  email.php
    header.php  footer.php
  /assets/  css/style.css   js/app.js   img/ (escudos/bandeiras)
  /cron/  fetch_results.php  # stub para futura API automática de placares
  /sql/   schema.sql  seed_selecoes.sql  seed_jogos.sql
```

## Modelo de dados (MySQL — `/sql/schema.sql`)

- **users**: `id, nome, email (unique), senha_hash, is_admin, email_verificado, token_verificacao, criado_em`
- **teams**: `id, nome, sigla (3 letras), bandeira, grupo (A–L)`
- **matches**: `id, fase (grupos|r32|r16|qf|sf|terceiro|final), grupo, rodada_label,
  home_team_id (NULL), away_team_id (NULL), home_placeholder, away_placeholder (ex.: '1A','2B','VJ73'),
  kickoff_utc (DATETIME, UTC), local, home_score (NULL), away_score (NULL), status (agendado|encerrado)`
- **pools**: `id, nome, owner_user_id, invite_token (unique), pts_exato=10, pts_saldo=7,
  pts_gols_um=5, pts_vencedor=3, pts_campeao=30, pts_vice=20, pts_terceiro=15, pts_quarto=10,
  pts_artilheiro=15, criado_em`
- **pool_members**: `id, pool_id, user_id, papel (owner|membro), palpite_padrao_home,
  palpite_padrao_away, entrou_em` — UNIQUE(`pool_id,user_id`)
- **predictions**: `id, pool_id, user_id, match_id, home_pred, away_pred, pontos (NULL),
  atualizado_em` — UNIQUE(`pool_id,user_id,match_id`)
- **bonus_predictions**: `id, pool_id, user_id, chave (campeao|vice|terceiro|quarto|artilheiro),
  team_id, pontos (NULL)` — UNIQUE(`pool_id,user_id,chave`)
- **tournament_results**: linha única global com os gabaritos dos bônus (`campeao_team_id`, etc.),
  preenchida pelo admin ao fim do torneio.

**Palpite padrão / preencher tudo:** o palpite efetivo de um jogo é o registro em `predictions`
se existir; senão, o `palpite_padrao_*` do `pool_member` (se definido). "Definir palpite padrão"
salva o padrão no membro; "Preencher esta página com X" cria/atualiza `predictions` em massa para
os jogos **ainda não travados** (o usuário pode editar antes de salvar). No cálculo de pontos,
jogo sem registro usa o padrão do membro.

## Travamento (5 min antes do jogo)

`kickoff_utc` em UTC. Um jogo está **travado** se `agora_utc >= kickoff_utc - 5min`. Validação no
**servidor** ao salvar (rejeita palpite travado) e indicação visual na UI (campo desabilitado +
cadeado). Exibição de horários convertida para **America/Sao_Paulo**.

## Cálculo do ranking

Ao admin registrar o placar de um jogo (`/admin/resultados.php` → status `encerrado`), recalcular
`pontos` de **todas** as `predictions` daquele `match_id` em todos os bolões, aplicando a config de
pontuação de cada bolão (`/includes/scoring.php`). Ranking = `SUM(pontos)` por usuário no bolão,
com contagem de "placar exato" como critério de desempate. Bônus recalculados quando
`tournament_results` é preenchido.

## Seed dos dados da Copa

Gerar `seed_selecoes.sql` (48 seleções, grupos A–L) e `seed_jogos.sql` (104 jogos com `kickoff_utc`,
local e fase). Durante a implementação, buscar a tabela oficial já sorteada (FIFA/ESPN) e converter
para SQL. Jogos do mata-mata entram com `*_placeholder` ("1A", "2B", "Vencedor 73"…) e o admin (ou,
no futuro, o cron) preenche `home_team_id/away_team_id` conforme as fases se definem.

## Pacote de etapas (ordem de execução)

1. **Base do projeto:** `config.php`, `db.php` (PDO), `header/footer`, `style.css`, CSRF, helpers.
2. **Auth:** registro (com `password_hash`), login, logout, sessão, recuperação de senha.
   Verificação de e-mail via `mail()` — **com flag para desligar** (entrega em hospedagem
   compartilhada é incerta; para brincadeira entre amigos pode ser opcional).
3. **Schema + seed:** criar `schema.sql`; gerar e importar seleções e os 104 jogos reais.
4. **Bolões:** criar bolão (gera `invite_token`), entrar por `convite.php?t=TOKEN`, listar membros,
   dashboard com "meus bolões".
5. **Palpites:** `palpites.php` com grade por data/rodada, salvar (AJAX), travamento 5 min,
   palpite padrão + "preencher tudo".
6. **Admin de resultados:** `jogos.php`/`resultados.php` para digitar placares e definir
   `home/away_team_id` do mata-mata; dispara recálculo.
7. **Scoring + ranking:** `scoring.php` (regra detalhada), recálculo on-save, `ranking.php`
   com filtros e desempate por placar exato.
8. **Bônus:** `bonus.php` (seletor de seleções) + `torneio.php` (gabarito) + pontuação dos bônus.
9. **Acabamento:** responsivo, mensagens de travado/erro, página de admin para gerenciar usuários,
   stub `cron/fetch_results.php` documentado para a futura API.

## Verificação (como testar ponta a ponta)

- **Local (Mac):** subir MySQL local (MAMP ou `brew install mysql`), importar
  `schema.sql` + seeds, e rodar `php -S localhost:8050` na raiz. (Confirmar versão do PHP do host
  para casar localmente.)
- **Fluxos manuais:** registrar 2 usuários → user A cria bolão → copiar link de convite →
  user B entra → ambos palpitam alguns jogos → definir palpite padrão e "preencher tudo" →
  conferir travamento (ajustar `kickoff_utc` de um jogo para o passado e confirmar bloqueio) →
  admin lança placares → conferir pontos por palpite e ordem do ranking (incl. casos 10/7/5/3/0) →
  preencher gabarito de bônus e conferir pontos.
- **Deploy:** subir arquivos por FTP, criar o banco MySQL no painel do host, importar os `.sql`,
  ajustar `config.php` com as credenciais, criar o primeiro usuário admin (flag `is_admin`).

## Pontos em aberto (decidir durante a execução)
- Versão do PHP/MySQL do host (afeta sintaxe/funções) — confirmar no painel.
- Verificação de e-mail ligada ou desligada na v1 (sugestão: desligada, dado o prazo).
- Confirmar valores finais dos pontos de bônus (defaults propostos acima).
