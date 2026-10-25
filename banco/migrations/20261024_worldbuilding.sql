-- Aditiva e transacional. Executar dentro de BEGIN/COMMIT após backup.
-- Personagens, cenários e relações legadas são mantidos.
ALTER TABLE personagem ADD COLUMN IF NOT EXISTS detalhes_mundo jsonb NOT NULL DEFAULT '{}'::jsonb;
ALTER TABLE cenario ADD COLUMN IF NOT EXISTS detalhes_mundo jsonb NOT NULL DEFAULT '{}'::jsonb;
CREATE TABLE IF NOT EXISTS worldbuilding_registro (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_livro integer NOT NULL REFERENCES publicacao_obra(id_livro),
    tipo text NOT NULL CHECK (tipo IN ('resumo','item','marco','nota','template')),
    nome varchar(100) NOT NULL,
    descricao text NOT NULL DEFAULT '',
    detalhes_mundo jsonb NOT NULL DEFAULT '{}'::jsonb
);
CREATE INDEX IF NOT EXISTS worldbuilding_obra_tipo ON worldbuilding_registro(id_livro,tipo);
CREATE UNIQUE INDEX IF NOT EXISTS worldbuilding_resumo_unico ON worldbuilding_registro(id_livro) WHERE tipo='resumo';
