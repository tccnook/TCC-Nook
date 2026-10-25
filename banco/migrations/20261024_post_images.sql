-- Galeria ordenada, preservando post.url_imagem como primeira imagem para consumidores legados.
CREATE TABLE IF NOT EXISTS post_imagem (
 id_post integer NOT NULL REFERENCES post(id_post) ON DELETE CASCADE,
 ordem smallint NOT NULL CHECK (ordem BETWEEN 1 AND 10),
 url_imagem text NOT NULL,
 PRIMARY KEY(id_post,ordem)
);
