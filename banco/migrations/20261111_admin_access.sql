BEGIN;
ALTER TABLE admin ADD COLUMN IF NOT EXISTS nivel text NOT NULL DEFAULT 'gestor';
ALTER TABLE admin ALTER COLUMN nivel SET DEFAULT 'supervisionado';
ALTER TABLE admin ADD COLUMN IF NOT EXISTS ativo boolean NOT NULL DEFAULT true;
ALTER TABLE admin ADD COLUMN IF NOT EXISTS excluido_em timestamptz;
ALTER TABLE admin ADD COLUMN IF NOT EXISTS id_user integer REFERENCES usuario(id_user) ON DELETE SET NULL;
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS ativo boolean NOT NULL DEFAULT true;
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS excluido_em timestamptz;
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS escrita_bloqueada boolean NOT NULL DEFAULT false;
DO $$ BEGIN
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='admin_nivel_check') THEN
 ALTER TABLE admin ADD CONSTRAINT admin_nivel_check CHECK (nivel IN ('provisorio','supremo','gestor','supervisionado'));
 END IF;
END $$;
UPDATE admin SET nivel='provisorio' WHERE lower(email)='adm@adm.adm' AND nivel <> 'provisorio';
CREATE UNIQUE INDEX IF NOT EXISTS admin_supremo_unico ON admin (nivel) WHERE nivel='supremo' AND ativo AND excluido_em IS NULL;
CREATE UNIQUE INDEX IF NOT EXISTS admin_usuario_unico ON admin (id_user) WHERE id_user IS NOT NULL AND excluido_em IS NULL;
CREATE TABLE IF NOT EXISTS admin_solicitacao (
 id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 solicitante integer NOT NULL REFERENCES admin(id_admin),
 rota text NOT NULL,
 dados jsonb NOT NULL,
 assinatura text NOT NULL,
 estado text NOT NULL DEFAULT 'pendente' CHECK (estado IN ('pendente','aprovada','rejeitada','utilizada','cancelada')),
 criado_em timestamptz NOT NULL DEFAULT now(),
 decidido_por integer REFERENCES admin(id_admin),
 decidido_em timestamptz,
 expira_em timestamptz,
 justificativa text NOT NULL DEFAULT '',
 utilizado_em timestamptz
);
CREATE INDEX IF NOT EXISTS admin_solicitacao_fila ON admin_solicitacao (estado, criado_em DESC);
CREATE INDEX IF NOT EXISTS admin_solicitacao_assinatura ON admin_solicitacao (solicitante, rota, assinatura);
CREATE TABLE IF NOT EXISTS admin_auditoria (
 id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
 id_admin integer REFERENCES admin(id_admin) ON DELETE SET NULL,
 acao text NOT NULL,
 alvo text NOT NULL,
 criado_em timestamptz NOT NULL DEFAULT now()
);
COMMIT;
