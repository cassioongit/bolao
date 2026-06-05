# ⚽ Bolão da Paz — Copa do Mundo 2026

Site de bolão (palpites) para a Copa do Mundo FIFA 2026, feito para uma brincadeira
entre amigos. **Sem propaganda.** PHP puro + MySQL — roda em qualquer hospedagem
compartilhada (cPanel, Locaweb, Hostinger, Hostgator…), sem Node e sem build.

## Funcionalidades
- Cadastro/login por e-mail e senha.
- Palpite do placar dos **104 jogos** da Copa (já cadastrados).
- Palpite trava **5 minutos antes** de cada jogo.
- **Palpite padrão**: defina um placar (ex.: 1×1) e aplique a todos os jogos abertos de uma vez.
- Pontuação detalhada (10 / 7 / 5 / 3 / 0) — veja em `regras.php`.
- Palpites de **bônus**: campeão, vice, 3º, 4º e time do artilheiro.
- **Bolões privados** por link de convite; cada um pode criar o seu.
- **Ranking** em tempo real, com filtro por fase e desempate por placares exatos.
- Painel **admin** para lançar resultados (calcula os pontos), ajustar mata-mata e o gabarito dos bônus.

## Estrutura
```
includes/   config, db, auth, scoring, pools, header/footer  (lógica compartilhada)
admin/      painel do administrador
api/        endpoints AJAX (salvar palpite)
assets/     css e js
sql/        schema.sql, seed.sql (seleções + 104 jogos), generate_seed.php
cron/       fetch_results.php (stub para automação futura)
```

## Rodar localmente (Mac/PC)
Requer PHP 8+ e um MySQL acessível (local ou remoto).
1. Copie `.env.example` para `.env` e preencha as credenciais do banco.
2. Importe o banco:
   ```bash
   mysql -h HOST -u USUARIO -p NOME_DO_BANCO < sql/schema.sql
   mysql -h HOST -u USUARIO -p NOME_DO_BANCO < sql/seed.sql
   ```
3. Suba o servidor de desenvolvimento:
   ```bash
   php -S localhost:8050
   ```
4. Acesse http://localhost:8050 — o **primeiro usuário cadastrado vira admin**.

> Em ambiente `local` (APP_ENV=local no .env), os e-mails de recuperação de senha são
> salvos como arquivo em `nimbalyst-local/outbox/` em vez de enviados.

## Publicar na hospedagem (FTP)
1. No painel da hospedagem, crie um **banco MySQL** e um usuário com acesso a ele.
2. Importe `sql/schema.sql` e depois `sql/seed.sql` pelo **phpMyAdmin** do painel.
3. Envie todos os arquivos do projeto para a pasta pública (ex.: `public_html`) por FTP.
4. Crie o arquivo `.env` no servidor (a partir do `.env.example`) com:
   - `DB_*` do seu banco; `APP_URL=https://seudominio.com.br`;
   - `APP_ENV=production` e `SHOW_ERRORS=false`.
5. Acesse o site e **cadastre-se** — sua conta será o admin.
6. Crie o bolão "Bolão da Paz", copie o link de convite e mande para a galera. 🎉

## Atualizar os jogos
- A fase de grupos já vem com seleções, datas (horário de Brasília) e sedes reais.
- O **mata-mata** entra com vagas ("1A", "Venc. 73"…). Conforme os classificados,
  o admin define as seleções em **Admin → Jogos & mata-mata**.
- Resultados: **Admin → Lançar resultados** (digita o placar e os pontos são calculados na hora).
- Ao fim da Copa: **Admin → Gabarito dos bônus** (campeão etc.).

## Regenerar o seed dos jogos
Se precisar ajustar a tabela de jogos, edite `sql/generate_seed.php` e rode:
```bash
php sql/generate_seed.php > sql/seed.sql
```
