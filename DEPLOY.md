# Publicação da Gênio Visual

## Estado atual

- Domínio: `https://geniovisual.com.br/`
- Docroot real: `/home/u183246221/domains/geniovisual.com.br/public_html`
- Configuração privada: `/home/u183246221/domains/geniovisual.com.br/private`
- Método principal: GitHub Actions por SSH/rsync
- Método de contingência: publicação manual por SSH/SCP
- Bundle em produção em 01/08/2026: `assets/index-oveIpfQa.js`

As credenciais ficam exclusivamente em `D:\GenioVisual\.deploy-credentials`, fora
do repositório. Nunca copie senhas, chaves ou `app-config.local.php` para o Git.

O Actions usa uma chave SSH dedicada nos secrets `HOSTINGER_SSH_*`. O workflow
roda em pushes para `main` e também pode ser iniciado manualmente. Antes do
rsync, ele exige lint, testes, build e sintaxe PHP válidos. Depois do envio,
compara checksums e confirma por HTTP que produção serve o bundle gerado.

## Pré-publicação

Execute na raiz do repositório:

```bash
npm ci
npm run lint
npm run test
npm run build
php -l private/bootstrap.php
php -l public/send.php
php -l public/track.php
php -l public/admin.php
```

Avise Lucas antes de alterar produção.

## Publicação automática

O workflow `.github/workflows/deploy-hostinger.yml`:

1. gera um bundle identificado pelo SHA do commit;
2. valida `../private/` fora do docroot e `crm-data/.htaccess` no destino;
3. executa rsync sem `--delete`;
4. exclui completamente `private/` e `crm-data/` do envio;
5. compara os checksums local e remoto;
6. compara o bundle do HTML público com o bundle gerado;
7. falha se a proteção de `leads.json` não retornar `403` ou `404`.

## Publicação manual de contingência

1. Conecte por SSH usando host, porta e usuário do arquivo de credenciais local.
2. Confirme com `pwd` e `ls` que o destino é o docroot `.com.br` acima.
3. Envie o conteúdo de `dist/` para o docroot, sem opção de exclusão.
4. Não use `rsync --delete` sem revisar e obter confirmação explícita.

Exemplo conceitual:

```bash
scp -P <porta> -r dist/. <usuario>@<host>:/home/u183246221/domains/geniovisual.com.br/public_html/
```

## Arquivos que devem ser preservados

Nunca apagar nem sobrescrever:

- `private/`
- `private/app-config.local.php`
- `crm-data/leads.json`
- `crm-data/analytics.json`
- demais arquivos `crm-data/*.json`

Nunca apagar:

- `crm-data/.htaccess`

O build contém `crm-data/.htaccess`, mas o pipeline exclui toda a pasta
`crm-data/` para não tocar na proteção nem nos dados vivos.

## Prova de publicação

1. Leia em `dist/index.html` o arquivo `/assets/index-*.js` gerado.
2. Consulte o HTML publicado sem cache.
3. Confirme que o nome do bundle remoto é exatamente o nome local.
4. Confirme que ele não é o bundle antigo `index-B8E9Velq.js`.
5. Valide os endpoints:

```bash
curl -I https://geniovisual.com.br/
curl -i https://geniovisual.com.br/crm-data/leads.json
curl -i https://geniovisual.com.br/send.php
```

Resultados esperados: homepage `200`, leads `403` ou `404`, e GET em
`send.php` igual a `405`.
