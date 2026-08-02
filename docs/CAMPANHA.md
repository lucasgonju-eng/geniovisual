# URLs de campanha

Domínio canônico: `https://geniovisual.com.br/`

## URLs prontas

1. LinkedIn Ads — feed:
   `https://geniovisual.com.br/?utm_source=linkedin&utm_medium=cpc&utm_campaign=ago26-linkedin&utm_content=feed`
2. LinkedIn orgânico — publicação:
   `https://geniovisual.com.br/?utm_source=linkedin&utm_medium=social&utm_campaign=ago26-organico&utm_content=post`
3. Instagram Ads — feed:
   `https://geniovisual.com.br/?utm_source=instagram&utm_medium=cpc&utm_campaign=ago26-instagram&utm_content=feed`
4. Instagram Ads — stories:
   `https://geniovisual.com.br/?utm_source=instagram&utm_medium=cpc&utm_campaign=ago26-instagram&utm_content=stories`
5. Instagram orgânico — link da bio:
   `https://geniovisual.com.br/?utm_source=instagram&utm_medium=social&utm_campaign=ago26-organico&utm_content=bio`

## Convenção

Use `utm_campaign` no formato `<mes><ano>-<tema>`:

- mês com três letras;
- ano com dois dígitos;
- tudo em minúsculas;
- sem espaços e sem acentos;
- palavras separadas por hífen.

Exemplos válidos: `ago26-linkedin`, `ago26-instagram`,
`set26-varejo`.

Use `utm_content` para distinguir posição ou criativo sem criar outra campanha,
como `feed`, `stories`, `video-a` ou `imagem-b`.

## Onde consultar a origem

- Admin: coluna e card **Origem** (`utm_source / utm_campaign`).
- E-mail interno: linha `Origem: source / medium / campaign`.
- WhatsApp: sufixo curto `[via: campanha]` na mensagem pré-preenchida.

## Limitação conhecida

O clique no WhatsApp gera o evento `whatsapp_click` e leva a campanha na
mensagem, mas não cria lead no CRM. O lead só entra em `leads.json` quando o
formulário do site é enviado com sucesso.
