-- Incremental e idempotente. Preserva tabelas, registros e a privacidade das listas antigas.
BEGIN;
CREATE TABLE IF NOT EXISTS configuracao_usuario (
    id_user integer PRIMARY KEY REFERENCES usuario(id_user) ON DELETE CASCADE,
    marcacoes text NOT NULL DEFAULT 'todos' CHECK (marcacoes IN ('todos','seguindo','seguidores','ninguem')),
    comentarios text NOT NULL DEFAULT 'todos' CHECK (comentarios IN ('todos','seguidores','ninguem')),
    permitir_sensivel boolean NOT NULL DEFAULT true,
    permitir_adulto boolean NOT NULL DEFAULT false,
    visibilidade_posts text NOT NULL DEFAULT 'publico' CHECK (visibilidade_posts IN ('publico','privado')),
    atualizado_em timestamptz NOT NULL DEFAULT now()
);
ALTER TABLE post ADD COLUMN IF NOT EXISTS conteudo_sensivel boolean NOT NULL DEFAULT false;
ALTER TABLE post ADD COLUMN IF NOT EXISTS conteudo_adulto boolean NOT NULL DEFAULT false;
ALTER TABLE recuperacao_senha ADD COLUMN IF NOT EXISTS codigo_hash text;
ALTER TABLE recuperacao_senha ADD COLUMN IF NOT EXISTS tentativas smallint NOT NULL DEFAULT 0;
ALTER TABLE meta_leitura ADD COLUMN IF NOT EXISTS tipo_meta text NOT NULL DEFAULT 'livros' CHECK (tipo_meta IN ('livros','listas'));
CREATE TABLE IF NOT EXISTS meta_leitura_listas (
    id_meta integer NOT NULL REFERENCES meta_leitura(id) ON DELETE CASCADE,
    id_lista integer NOT NULL REFERENCES whishlist(id) ON DELETE CASCADE,
    PRIMARY KEY(id_meta,id_lista)
);
ALTER TABLE whishlist ADD COLUMN IF NOT EXISTS descricao text NOT NULL DEFAULT '';
ALTER TABLE whishlist ADD COLUMN IF NOT EXISTS visibilidade varchar(20) NOT NULL DEFAULT 'privada';
ALTER TABLE whishlist ADD COLUMN IF NOT EXISTS tipo_capa varchar(20) NOT NULL DEFAULT 'automatica';
ALTER TABLE whishlist ADD COLUMN IF NOT EXISTS capa_url varchar(250);
ALTER TABLE whishlist ADD COLUMN IF NOT EXISTS data_criacao timestamp;
ALTER TABLE whishlist ALTER COLUMN data_criacao SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE whishbook ADD COLUMN IF NOT EXISTS ordem integer;
ALTER TABLE whishbook ADD COLUMN IF NOT EXISTS adicionado_em timestamp;
ALTER TABLE whishbook ALTER COLUMN adicionado_em SET DEFAULT CURRENT_TIMESTAMP;
CREATE TABLE IF NOT EXISTS whishlist_seguida (
    id_user integer NOT NULL REFERENCES usuario(id_user) ON DELETE CASCADE,
    id_whishlist integer NOT NULL REFERENCES whishlist(id) ON DELETE CASCADE,
    PRIMARY KEY(id_user,id_whishlist)
);
CREATE TABLE IF NOT EXISTS follow_autor (
    id_follower integer NOT NULL REFERENCES usuario(id_user) ON DELETE CASCADE,
    id_autor integer NOT NULL REFERENCES autor(id_autor) ON DELETE CASCADE,
    PRIMARY KEY(id_follower,id_autor)
);
ALTER TABLE livro ADD COLUMN IF NOT EXISTS paginas integer;
ALTER TABLE livro ADD COLUMN IF NOT EXISTS nota_google numeric(3,2);
ALTER TABLE livro ADD COLUMN IF NOT EXISTS google_books_id varchar(100);
ALTER TABLE livro ADD COLUMN IF NOT EXISTS isbn10 varchar(20);
ALTER TABLE livro ADD COLUMN IF NOT EXISTS isbn13 varchar(20);
ALTER TABLE livro ADD COLUMN IF NOT EXISTS origem varchar(30);
ALTER TABLE livro ADD COLUMN IF NOT EXISTS link_leitura text;
ALTER TABLE livro ADD COLUMN IF NOT EXISTS link_compra text;
CREATE INDEX IF NOT EXISTS livro_google_books_idx ON livro(google_books_id) WHERE google_books_id IS NOT NULL;
CREATE TABLE IF NOT EXISTS mencao (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_origem integer NOT NULL REFERENCES usuario(id_user) ON DELETE CASCADE,
    id_destino integer NOT NULL REFERENCES usuario(id_user) ON DELETE CASCADE,
    categoria text NOT NULL,
    id_conteudo integer NOT NULL,
    criado_em timestamptz NOT NULL DEFAULT now(),
    UNIQUE(id_origem,id_destino,categoria,id_conteudo)
);
CREATE INDEX IF NOT EXISTS mencao_destino_idx ON mencao(id_destino,criado_em DESC);
COMMIT;
