-- =====================================================================
--  GameVault - E-commerce de games
--  Script de criação do banco de dados + carga inicial de teste
--  Compatível com MySQL 8 / MariaDB 10 (XAMPP)
--
--  Como usar (phpMyAdmin): importar este arquivo.
--  Como usar (terminal):   mysql -u root -p < database/schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS gamevault
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gamevault;

-- Remove as tabelas na ordem inversa das dependências (recriação limpa)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pedido_itens;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--  USUÁRIOS: clientes e administradores
--  - e-mail único (UNIQUE)
--  - senha armazenada apenas como hash (password_hash do PHP)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  nome        VARCHAR(120)      NOT NULL,
  email       VARCHAR(160)      NOT NULL,
  senha_hash  VARCHAR(255)      NOT NULL,
  perfil      ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
  criado_em   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  CATEGORIAS
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome  VARCHAR(80)  NOT NULL,
  slug  VARCHAR(80)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_categorias_nome (nome),
  UNIQUE KEY uk_categorias_slug (slug)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  PRODUTOS: pertencem a uma categoria; controle de preço, estoque e imagem
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
  id            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  categoria_id  INT UNSIGNED   NOT NULL,
  nome          VARCHAR(150)   NOT NULL,
  descricao     TEXT           NULL,
  preco         DECIMAL(10,2)  NOT NULL,
  estoque       INT UNSIGNED   NOT NULL DEFAULT 0,
  imagem_url    VARCHAR(255)   NULL,
  ativo         TINYINT(1)     NOT NULL DEFAULT 1,
  criado_em     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_produtos_categoria (categoria_id),
  KEY idx_produtos_ativo (ativo),
  CONSTRAINT fk_produtos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_produtos_preco CHECK (preco >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  PEDIDOS: uma transação de venda por cliente
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
  id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  usuario_id       INT UNSIGNED   NOT NULL,
  data_pedido      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  forma_pagamento  ENUM('pix','boleto','cartao') NOT NULL,
  status           ENUM('pago','pendente','cancelado') NOT NULL DEFAULT 'pago',
  total            DECIMAL(10,2)  NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pedidos_usuario (usuario_id),
  CONSTRAINT fk_pedidos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_pedidos_total CHECK (total >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  ITENS DO PEDIDO: resolve a relação N:N entre pedidos e produtos
--  - quantidade comprada
--  - preco_unitario "congelado" no momento da compra
-- ---------------------------------------------------------------------
CREATE TABLE pedido_itens (
  id              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  pedido_id       INT UNSIGNED   NOT NULL,
  produto_id      INT UNSIGNED   NOT NULL,
  quantidade      INT UNSIGNED   NOT NULL,
  preco_unitario  DECIMAL(10,2)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_pedido_produto (pedido_id, produto_id),
  KEY idx_itens_produto (produto_id),
  CONSTRAINT fk_itens_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_itens_produto
    FOREIGN KEY (produto_id) REFERENCES produtos (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_itens_quantidade CHECK (quantidade > 0)
) ENGINE=InnoDB;

-- =====================================================================
--  CARGA INICIAL DE TESTE
-- =====================================================================

-- Usuários de teste (senhas geradas com password_hash / bcrypt)
--   admin@gamevault.com    senha: Admin@123    (perfil admin)
--   cliente@gamevault.com  senha: Cliente@123  (perfil cliente)
INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
  ('Administrador', 'admin@gamevault.com',   '$2y$10$1fy/Oq0Xk5ZG164L/mgXAu0F/r4tqA5497UM/Sj.X5S4RUqKrT7ei', 'admin'),
  ('Cliente Teste', 'cliente@gamevault.com', '$2y$10$NXFhYZX7lxnnq9K4u/iWYuPiNP2JVCHgeh./MShkAN0CxqiEHXQOG', 'cliente');

INSERT INTO categorias (nome, slug) VALUES
  ('Consoles',   'consoles'),
  ('Jogos',      'jogos'),
  ('Acessórios', 'acessorios'),
  ('PC Gamer',   'pc-gamer');

INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem_url, ativo) VALUES
  (1, 'PlayStation 5 Slim 1TB',
      'Console de nova geração com SSD ultrarrápido, ray tracing e controle DualSense com feedback háptico.',
      3799.90, 8,  'https://placehold.co/600x400/1b1f3b/ffffff?text=PlayStation+5', 1),
  (1, 'Xbox Series X 1TB',
      'O Xbox mais rápido e poderoso de todos os tempos, com 12 teraflops de poder gráfico e Quick Resume.',
      3999.00, 5,  'https://placehold.co/600x400/107c10/ffffff?text=Xbox+Series+X', 1),
  (1, 'Nintendo Switch OLED',
      'Tela OLED de 7 polegadas, 64 GB de armazenamento e base com porta LAN. Jogue em casa ou em qualquer lugar.',
      2499.00, 12, 'https://placehold.co/600x400/e60012/ffffff?text=Switch+OLED', 1),
  (2, 'The Legend of Zelda: Tears of the Kingdom',
      'Explore o vasto reino de Hyrule e os céus acima em uma aventura épica. Mídia física para Nintendo Switch.',
      349.90, 25, 'https://placehold.co/600x400/2d6a4f/ffffff?text=Zelda+TOTK', 1),
  (2, 'God of War Ragnarök',
      'Kratos e Atreus enfrentam o Ragnarök em uma jornada pelos Nove Reinos. Mídia física para PS5.',
      299.90, 18, 'https://placehold.co/600x400/5c2a2a/ffffff?text=God+of+War', 1),
  (2, 'Forza Horizon 5',
      'Dirija centenas de carros nas paisagens vibrantes do México. Mídia física para Xbox.',
      249.90, 0,  'https://placehold.co/600x400/c27c0e/ffffff?text=Forza+Horizon+5', 1),
  (2, 'Elden Ring',
      'Levante-se, Maculado, e seja guiado pela graça para portar o poder do Anel Prístino. Edição padrão.',
      229.90, 30, 'https://placehold.co/600x400/3d2b1f/ffffff?text=Elden+Ring', 1),
  (3, 'Controle DualSense Branco',
      'Controle sem fio com gatilhos adaptáveis, feedback háptico e microfone integrado.',
      449.90, 40, 'https://placehold.co/600x400/f4f4f4/333333?text=DualSense', 1),
  (3, 'Headset HyperX Cloud II',
      'Som surround 7.1 virtual, espuma de memória e microfone destacável com cancelamento de ruído.',
      499.00, 15, 'https://placehold.co/600x400/b91c1c/ffffff?text=HyperX+Cloud+II', 1),
  (3, 'Cartão Nintendo eShop R$ 100',
      'Crédito digital para compras na loja da Nintendo. Entrega do código por e-mail.',
      100.00, 100, 'https://placehold.co/600x400/e60012/ffffff?text=eShop+R%24100', 1),
  (4, 'Placa de Vídeo RTX 4070 12GB',
      'Ray tracing de terceira geração, DLSS 3 e 12 GB GDDR6X para jogar em 1440p com tudo no máximo.',
      4299.00, 6,  'https://placehold.co/600x400/76b900/111111?text=RTX+4070', 1),
  (4, 'Teclado Mecânico RGB Switch Red',
      'Teclado mecânico ABNT2 com switches lineares, iluminação RGB por tecla e cabo destacável.',
      389.90, 22, 'https://placehold.co/600x400/4c1d95/ffffff?text=Teclado+RGB', 1),
  (4, 'Mouse Gamer 16000 DPI',
      'Sensor óptico de alta precisão, 7 botões programáveis e peso ajustável.',
      199.90, 35, 'https://placehold.co/600x400/0f172a/ffffff?text=Mouse+Gamer', 1),
  (2, 'FIFA 23 (Edição Legado)',
      'Produto descontinuado. Mantido no banco apenas como exemplo de item desativado.',
      99.90, 3,  'https://placehold.co/600x400/555555/ffffff?text=FIFA+23', 0);

-- Um pedido de exemplo para o cliente de teste (2 itens, pago via PIX)
INSERT INTO pedidos (usuario_id, data_pedido, forma_pagamento, status, total) VALUES
  (2, '2026-11-05 14:32:00', 'pix', 'pago', 749.80);

INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES
  (1, 5, 1, 299.90),   -- God of War Ragnarök
  (1, 8, 1, 449.90);   -- Controle DualSense
