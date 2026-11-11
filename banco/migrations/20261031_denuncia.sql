BEGIN;
CREATE TABLE IF NOT EXISTS public.denuncia (
    id_denuncia integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_denunciado integer NOT NULL,
    id_denunciador integer NOT NULL REFERENCES public.usuario(id_user),
    tipo_denunciado varchar(30) NOT NULL,
    denuncia text NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'pendente'
        CHECK (status IN ('pendente', 'ignorada', 'resolvida')),
    emissao timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolvido timestamp without time zone
);
CREATE INDEX IF NOT EXISTS denuncia_pendentes_idx
    ON public.denuncia (tipo_denunciado, emissao DESC) WHERE status = 'pendente';
COMMIT;
