CREATE TABLE IF NOT EXISTS public.whishlist_seguida (
    id_user integer NOT NULL REFERENCES public.usuario(id_user) ON DELETE CASCADE,
    id_whishlist integer NOT NULL REFERENCES public.whishlist(id) ON DELETE CASCADE,
    seguida_em timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user, id_whishlist)
);

CREATE INDEX IF NOT EXISTS whishlist_seguida_lista_idx
    ON public.whishlist_seguida (id_whishlist);
