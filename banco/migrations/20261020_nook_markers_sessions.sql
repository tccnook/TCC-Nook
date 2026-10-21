BEGIN;
CREATE TABLE IF NOT EXISTS marcador_leitura (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_user integer NOT NULL REFERENCES usuario(id_user) ON DELETE CASCADE,
    id_livro integer NOT NULL REFERENCES livro(id_livro) ON DELETE CASCADE,
    trecho text NOT NULL CHECK(length(trecho) BETWEEN 1 AND 2000),
    pagina integer CHECK(pagina>0),
    criado_em timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS marcador_leitura_usuario_idx ON marcador_leitura(id_user,criado_em DESC);
CREATE TABLE IF NOT EXISTS sessao_usuario (
    assinatura text PRIMARY KEY,
    id_user integer NOT NULL REFERENCES usuario(id_user) ON DELETE CASCADE,
    dispositivo text NOT NULL,
    ultimo_acesso timestamptz NOT NULL DEFAULT now(),
    revogado_em timestamptz
);
CREATE INDEX IF NOT EXISTS sessao_usuario_user_idx ON sessao_usuario(id_user,ultimo_acesso DESC);
COMMIT;
