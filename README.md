# Gênio Visual

Landing page comercial da Gênio Visual para apresentação do painel de LED e captação de leads.

## Stack

- Vite
- React
- TypeScript
- Tailwind CSS
- shadcn/ui
- PHP para captura de leads, tracking e admin

## Desenvolvimento local

```sh
cd preview
npm install
npm run dev
```

## Scripts úteis

```sh
npm run dev
npm run build
npm run lint
npm run test
```

## Estrutura relevante

- `src/`: frontend React
- `public/`: arquivos públicos e endpoints PHP
- `private/`: configurações locais e bootstrap do backend PHP

## Configuração privada

Crie ou ajuste o arquivo `preview/private/app-config.local.php` com:

- e-mail de recebimento de leads
- usuário do admin
- senha do admin
- URL pública da aplicação
