# GameVault – E-commerce de games

Projeto desenvolvido para o **Challenge do 4º Bimestre (Senac – 3TI02)**: uma loja virtual completa com
fluxo de venda ponta a ponta, construída em **PHP + MySQL (PDO) + Bootstrap**.

## Funcionalidades

| Módulo | O que faz |
| --- | --- |
| Vitrine | Listagem dinâmica de produtos do banco, filtro por categoria e busca por texto |
| Produto | Página individual com código, preço, descrição, imagem e indicador de estoque |
| Carrinho | Gerenciado em `$_SESSION`: adicionar, alterar quantidade (limitada ao estoque), remover, subtotal/total dinâmicos |
| Autenticação | Cadastro e login com `password_hash()` / `password_verify()`, e-mail único |
| Checkout | Restrito a usuários logados; pagamento simulado (PIX, Boleto ou Cartão) com confirmação imediata |
| Pedido | Transação que cria o pedido, grava os itens com preço congelado, baixa o estoque e limpa o carrinho |
| Painel `/admin` | Acesso só para perfil `admin`: CRUD de produtos (cadastrar, listar, editar preço/estoque, desativar/excluir) e visualização dos pedidos com itens e total |

## Tecnologias

- HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, JavaScript puro
- PHP 8.1+ (compatível com XAMPP)
- MySQL / MariaDB via **PDO** com prepared statements
- Variáveis de ambiente em arquivo `.env`

## Como executar (XAMPP)

1. Copie a pasta do projeto para `C:\xampp\htdocs\gamevault`.
2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. Crie o banco importando o script:
   - phpMyAdmin → *Importar* → selecione `database/schema.sql`, **ou**
   - terminal: `C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql`
4. Copie `.env.example` para `.env` e ajuste as credenciais (no XAMPP padrão: usuário `root`, senha vazia).
5. Acesse `http://localhost/gamevault`.

### Contas de teste (criadas pelo `schema.sql`)

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | admin@gamevault.com | Admin@123 |
| Cliente | cliente@gamevault.com | Cliente@123 |

## Estrutura do projeto

```
gamevault/
├── admin/               # Painel administrativo (rotas protegidas)
│   ├── index.php        # Dashboard
│   ├── produtos.php     # Listagem de produtos
│   ├── produto_form.php # Cadastro / edição
│   ├── produto_acao.php # Ativar/desativar/excluir
│   ├── pedidos.php      # Lista de pedidos
│   └── pedido.php       # Itens de um pedido
├── assets/              # CSS e JS próprios
├── config/
│   ├── env.php          # Carregador do .env
│   └── database.php     # Conexão PDO (singleton)
├── database/
│   └── schema.sql       # Criação das tabelas + carga inicial
├── includes/
│   ├── init.php         # Bootstrap da aplicação (sessão, CSRF, requires)
│   ├── helpers.php      # Funções utilitárias (escape, moeda, flash, CSRF...)
│   ├── auth.php         # Login, cadastro, controle de acesso
│   ├── cart.php         # Carrinho em sessão
│   ├── models.php       # Consultas (CRUD) com prepared statements
│   └── layout/          # header.php e footer.php
├── index.php            # Vitrine
├── produto.php          # Detalhe do produto
├── carrinho.php         # Carrinho
├── login.php / cadastro.php / logout.php
├── checkout.php         # Pagamento simulado + finalização
├── pedido.php           # Confirmação do pedido
├── meus_pedidos.php     # Histórico do cliente
├── .env.example         # Modelo das variáveis de ambiente
└── .gitignore
```

## Modelagem do banco

- **usuarios** – `perfil` ENUM(cliente, admin), e-mail UNIQUE, `senha_hash`
- **categorias** – nome e slug únicos
- **produtos** – FK `categoria_id`, `preco DECIMAL(10,2)`, `estoque INT UNSIGNED`, `imagem_url`, `ativo`
- **pedidos** – FK `usuario_id`, `data_pedido`, `forma_pagamento` ENUM(pix, boleto, cartao), `status`, `total DECIMAL(10,2)`
- **pedido_itens** – resolve o N:N entre pedidos e produtos: `quantidade` e `preco_unitario` congelado na compra

## Segurança aplicada

- Todas as consultas usam **prepared statements** (sem concatenação de variáveis em SQL).
- Senhas armazenadas apenas com **bcrypt** (`password_hash`), verificação com `password_verify`.
- Rotas do `/admin` validam a sessão **e** reconsultam o perfil no banco a cada requisição.
- Token **CSRF** em todos os formulários POST; cookies de sessão `HttpOnly` + `SameSite=Lax`.
- Saída HTML sempre escapada com `htmlspecialchars` (prevenção de XSS).
- Credenciais fora do código: `.env` ignorado pelo Git, `.env.example` versionado.
- Finalização do pedido em **transação** com `SELECT ... FOR UPDATE` para evitar venda acima do estoque.
