-- NOOK: estrutura completa em 2026-10-20 e dados públicos de catálogo.
-- Sem contas, senhas, sessões, tokens, mensagens, interações ou rascunhos.
-- Restaurar em banco vazio; consultar banco/README.md.

--
-- PostgreSQL database dump
--

\restrict g2fzWaLHB8ftzSZaOLvgsehq0zcLRO6FZ6BxY1Cm1FvSNoaFI2JFFDEJxXkS3Aj

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA IF NOT EXISTS public;


--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS 'standard public schema';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: admin; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin (
    id_admin integer NOT NULL,
    nome_completo character varying(100),
    email character varying(100),
    senha character varying(250),
    criacao_conta timestamp without time zone DEFAULT now()
);


--
-- Name: admin_id_admin_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.admin ALTER COLUMN id_admin ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.admin_id_admin_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: autor; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.autor (
    id_autor integer NOT NULL,
    nome_autor character varying(90),
    autor character varying(90) NOT NULL,
    bio text,
    foto_url character varying(250)
);


--
-- Name: autor_id_autor_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.autor ALTER COLUMN id_autor ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.autor_id_autor_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: autor_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.autor_user (
    id_user integer,
    id_autor integer,
    id integer NOT NULL
);


--
-- Name: autor_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.autor_user ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.autor_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: bloqueio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bloqueio (
    id_bloqueador integer,
    id_bloqueado integer,
    status_bloqueio character varying(50),
    data_bloqueio timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: capitulo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.capitulo (
    id_capitulo integer NOT NULL,
    titulo_capitulo character varying(100),
    imagem_url_capitulo character varying(250),
    ordem_capitulo integer,
    id_livro integer,
    conteudo_html text DEFAULT ''::text NOT NULL
);


--
-- Name: capitulo_id_capitulo_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.capitulo ALTER COLUMN id_capitulo ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.capitulo_id_capitulo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cena; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cena (
    id_cena integer NOT NULL,
    nome_cena character varying(150),
    descricao text,
    id_livro integer
);


--
-- Name: cena_id_cena_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.cena ALTER COLUMN id_cena ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cena_id_cena_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cenario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cenario (
    id_cenario integer NOT NULL,
    nome_cenario character varying(100),
    descricao_cenario text,
    id_livro integer
);


--
-- Name: cenario_id_cenario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.cenario ALTER COLUMN id_cenario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cenario_id_cenario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: comentario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.comentario (
    id_comentario integer NOT NULL,
    id_user integer,
    categoria_curtida character varying(50),
    id_coisocurtido integer,
    nota_avaliacao real,
    comentario text,
    criado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: comentario_id_comentario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.comentario ALTER COLUMN id_comentario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.comentario_id_comentario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: comentario_obra; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.comentario_obra (
    id bigint NOT NULL,
    id_livro integer NOT NULL,
    id_user integer NOT NULL,
    texto text NOT NULL,
    criado_em timestamp with time zone DEFAULT now() NOT NULL,
    editado_em timestamp with time zone,
    estado text DEFAULT 'visivel'::text NOT NULL,
    CONSTRAINT comentario_obra_estado_check CHECK ((estado = ANY (ARRAY['visivel'::text, 'oculto'::text, 'excluido'::text]))),
    CONSTRAINT comentario_obra_texto_check CHECK (((length(texto) >= 1) AND (length(texto) <= 3000)))
);


--
-- Name: comentario_obra_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.comentario_obra ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.comentario_obra_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: configuracao_usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.configuracao_usuario (
    id_user integer NOT NULL,
    marcacoes text DEFAULT 'todos'::text NOT NULL,
    comentarios text DEFAULT 'todos'::text NOT NULL,
    permitir_sensivel boolean DEFAULT true NOT NULL,
    permitir_adulto boolean DEFAULT false NOT NULL,
    visibilidade_posts text DEFAULT 'publico'::text NOT NULL,
    atualizado_em timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT configuracao_usuario_comentarios_check CHECK ((comentarios = ANY (ARRAY['todos'::text, 'seguidores'::text, 'ninguem'::text]))),
    CONSTRAINT configuracao_usuario_marcacoes_check CHECK ((marcacoes = ANY (ARRAY['todos'::text, 'seguindo'::text, 'seguidores'::text, 'ninguem'::text]))),
    CONSTRAINT configuracao_usuario_visibilidade_posts_check CHECK ((visibilidade_posts = ANY (ARRAY['publico'::text, 'privado'::text])))
);


--
-- Name: conta; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conta (
    id_user integer,
    foto_perfil_url character varying(240),
    bio text,
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    banner_url character varying(250),
    CONSTRAINT conta_visibilidade_check CHECK (((visibilidade)::text = ANY (ARRAY[('publico'::character varying)::text, ('privado'::character varying)::text])))
);


--
-- Name: conversa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversa (
    id_conversa integer NOT NULL,
    tipo character varying(90),
    nome_conversa character varying(80),
    foto_conversa_url character varying(250),
    id_dono integer,
    criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: conversa_id_conversa_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.conversa ALTER COLUMN id_conversa ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.conversa_id_conversa_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: conversa_participante; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversa_participante (
    id_conversa integer,
    id_user integer,
    cargo character varying(100),
    joinet_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    last_read_message timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: curtida; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.curtida (
    id_curtida integer NOT NULL,
    id_user integer,
    categoria_curtido character varying(50),
    id_coisocurtido integer
);


--
-- Name: curtida_id_curtida_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.curtida ALTER COLUMN id_curtida ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.curtida_id_curtida_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: denuncia_obra; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.denuncia_obra (
    id bigint NOT NULL,
    id_livro integer NOT NULL,
    id_user integer NOT NULL,
    motivo text NOT NULL,
    detalhes text NOT NULL,
    estado text DEFAULT 'aberta'::text NOT NULL,
    criada_em timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT denuncia_obra_detalhes_check CHECK (((length(detalhes) >= 10) AND (length(detalhes) <= 3000))),
    CONSTRAINT denuncia_obra_estado_check CHECK ((estado = ANY (ARRAY['aberta'::text, 'resolvida'::text]))),
    CONSTRAINT denuncia_obra_motivo_check CHECK ((motivo = ANY (ARRAY['plagio'::text, 'direitos'::text, 'inadequado'::text, 'outro'::text])))
);


--
-- Name: denuncia_obra_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.denuncia_obra ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.denuncia_obra_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: edicao_livro; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.edicao_livro (
    id bigint NOT NULL,
    id_livro integer NOT NULL,
    numero integer NOT NULL,
    criada_em timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT edicao_livro_numero_check CHECK ((numero > 0))
);


--
-- Name: edicao_livro_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.edicao_livro ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.edicao_livro_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: email_verificacao; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_verificacao (
    id_user integer NOT NULL,
    token_hash character(64),
    expira_em timestamp without time zone,
    verificado_em timestamp without time zone,
    criado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: follow; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.follow (
    id_follower integer,
    id_following integer,
    status_follow character varying(50),
    data_follow timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: follow_autor; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.follow_autor (
    id_follower integer NOT NULL,
    id_autor integer NOT NULL
);


--
-- Name: livro; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.livro (
    id_livro integer CONSTRAINT livro_id_not_null NOT NULL,
    titulo_livro character varying(100) NOT NULL,
    resumo_livro text,
    class_ind integer NOT NULL,
    nome_autor character varying(100),
    id_user integer,
    sinopse_livro text,
    capa_url character varying(250),
    data_publi timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    idioma character varying(90),
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    paginas integer,
    nota_google numeric(3,2),
    google_books_id character varying(100),
    isbn10 character varying(20),
    isbn13 character varying(20),
    origem character varying(30),
    link_leitura text,
    link_compra text,
    CONSTRAINT livro_visibilidade_check CHECK (((visibilidade)::text = ANY (ARRAY[('publico'::character varying)::text, ('privado'::character varying)::text])))
);


--
-- Name: livro_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.livro ALTER COLUMN id_livro ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.livro_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: livros_lidos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.livros_lidos (
    id_user integer,
    id_livro integer,
    atualizado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: marcador_leitura; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.marcador_leitura (
    id bigint NOT NULL,
    id_user integer NOT NULL,
    id_livro integer NOT NULL,
    trecho text NOT NULL,
    pagina integer,
    criado_em timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT marcador_leitura_pagina_check CHECK ((pagina > 0)),
    CONSTRAINT marcador_leitura_trecho_check CHECK (((length(trecho) >= 1) AND (length(trecho) <= 2000)))
);


--
-- Name: marcador_leitura_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.marcador_leitura ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.marcador_leitura_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: mencao; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mencao (
    id bigint NOT NULL,
    id_origem integer NOT NULL,
    id_destino integer NOT NULL,
    categoria text NOT NULL,
    id_conteudo integer NOT NULL,
    criado_em timestamp with time zone DEFAULT now() NOT NULL
);


--
-- Name: mencao_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.mencao ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mencao_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: mensagem; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mensagem (
    id_mensagem integer NOT NULL,
    id_conversa integer,
    id_envio integer,
    tipo character varying(90),
    conteudo text,
    criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    editado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    delatado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: mensagem_id_mensagem_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.mensagem ALTER COLUMN id_mensagem ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mensagem_id_mensagem_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: meta_leitura; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.meta_leitura (
    id integer NOT NULL,
    id_user integer,
    periodo character varying(60),
    num_livros integer,
    criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(30) DEFAULT 'andamento'::character varying,
    expiracao date,
    nome_meta character varying(100),
    tipo_meta text DEFAULT 'livros'::text NOT NULL,
    CONSTRAINT meta_leitura_tipo_meta_check CHECK ((tipo_meta = ANY (ARRAY['livros'::text, 'listas'::text])))
);


--
-- Name: meta_leitura_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.meta_leitura ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.meta_leitura_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: meta_leitura_listas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.meta_leitura_listas (
    id_meta integer NOT NULL,
    id_lista integer NOT NULL
);


--
-- Name: moderacao_obra; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.moderacao_obra (
    id bigint NOT NULL,
    id_livro integer NOT NULL,
    id_admin integer NOT NULL,
    acao text NOT NULL,
    motivo text NOT NULL,
    criada_em timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT moderacao_obra_acao_check CHECK ((acao = ANY (ARRAY['retirar'::text, 'restaurar'::text])))
);


--
-- Name: moderacao_obra_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.moderacao_obra ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.moderacao_obra_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: paragrafo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.paragrafo (
    id_paragrafo integer NOT NULL,
    texto_paragrafo text,
    imagem_paragrafo_url character varying(250),
    ordem_paragrafo integer,
    id_capitulo integer
);


--
-- Name: paragrafo_id_paragrafo_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.paragrafo ALTER COLUMN id_paragrafo ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.paragrafo_id_paragrafo_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: paragrafo_resenha; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.paragrafo_resenha (
    id_paragrafo_resenha integer NOT NULL,
    texto_paragrafo_resenha text,
    id_resenha integer,
    ordem_paragrafo_resenha integer
);


--
-- Name: paragrafo_resenha_id_paragrafo_resenha_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.paragrafo_resenha ALTER COLUMN id_paragrafo_resenha ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.paragrafo_resenha_id_paragrafo_resenha_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: personagem; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personagem (
    id_personagem integer NOT NULL,
    nome_personagem character varying(100),
    genero character varying(20),
    idade integer,
    funcao character varying(200),
    descricao_personagem text,
    id_livro integer
);


--
-- Name: personagem_id_personagem_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.personagem ALTER COLUMN id_personagem ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.personagem_id_personagem_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: post; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.post (
    id_post integer NOT NULL,
    titulo_post character varying(90),
    id_user integer,
    url_imagem character varying(250),
    conteudo text,
    criado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    legenda text,
    conteudo_sensivel boolean DEFAULT false NOT NULL,
    conteudo_adulto boolean DEFAULT false NOT NULL,
    CONSTRAINT post_visibilidade_check CHECK (((visibilidade)::text = ANY (ARRAY[('publico'::character varying)::text, ('privado'::character varying)::text])))
);


--
-- Name: post_id_post_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.post ALTER COLUMN id_post ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.post_id_post_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: preferencia; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.preferencia (
    id_preferencia integer NOT NULL,
    nome_preferencia character varying(90),
    preferencia character varying(90) NOT NULL
);


--
-- Name: preferencia_id_preferencia_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.preferencia ALTER COLUMN id_preferencia ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.preferencia_id_preferencia_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: preferencia_livro; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.preferencia_livro (
    id integer NOT NULL,
    id_preferencia integer,
    id_livro integer
);


--
-- Name: preferencia_livro_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.preferencia_livro ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.preferencia_livro_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: preferencia_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.preferencia_user (
    id_user integer,
    id_preferencia integer,
    id integer NOT NULL
);


--
-- Name: preferencia_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.preferencia_user ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.preferencia_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: progresso_leitura; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.progresso_leitura (
    id_progresso integer NOT NULL,
    id_user integer NOT NULL,
    id_livro integer NOT NULL,
    capitulo_atual integer,
    porcentagem_progresso numeric(5,2),
    ultima_leitura timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: progresso_leitura_id_progresso_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.progresso_leitura_id_progresso_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: progresso_leitura_id_progresso_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.progresso_leitura_id_progresso_seq OWNED BY public.progresso_leitura.id_progresso;


--
-- Name: progresso_publicacao; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.progresso_publicacao (
    id_user integer NOT NULL,
    id_livro integer NOT NULL,
    id_versao bigint NOT NULL,
    pagina integer DEFAULT 1 NOT NULL,
    concluido_em timestamp with time zone,
    atualizado_em timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT progresso_publicacao_pagina_check CHECK ((pagina > 0))
);


--
-- Name: publicacao_obra; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.publicacao_obra (
    id_livro integer NOT NULL,
    id_autor integer NOT NULL,
    modalidade text NOT NULL,
    estado text DEFAULT 'rascunho'::text NOT NULL,
    titulo text DEFAULT ''::text NOT NULL,
    resumo text DEFAULT ''::text NOT NULL,
    id_genero integer,
    classificacao integer,
    capa_url text,
    pdf_rascunho text,
    revisao integer DEFAULT 1 NOT NULL,
    atualizado_em timestamp with time zone DEFAULT now() NOT NULL,
    criado_em timestamp with time zone DEFAULT now() NOT NULL,
    retirado_em timestamp with time zone,
    motivo_retirada text,
    id_versao_atual bigint,
    CONSTRAINT publicacao_obra_classificacao_check CHECK ((classificacao = ANY (ARRAY[0, 10, 12, 14, 16, 18]))),
    CONSTRAINT publicacao_obra_estado_check CHECK ((estado = ANY (ARRAY['rascunho'::text, 'publicado'::text, 'arquivado'::text, 'excluido'::text]))),
    CONSTRAINT publicacao_obra_modalidade_check CHECK ((modalidade = ANY (ARRAY['editor'::text, 'pdf'::text])))
);


--
-- Name: recuperacao_senha; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.recuperacao_senha (
    id_recuperacao integer NOT NULL,
    id_user integer NOT NULL,
    token text NOT NULL,
    expira_em timestamp without time zone NOT NULL,
    usado boolean DEFAULT false NOT NULL,
    codigo_hash text,
    tentativas smallint DEFAULT 0 NOT NULL
);


--
-- Name: recuperacao_senha_id_recuperacao_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.recuperacao_senha ALTER COLUMN id_recuperacao ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.recuperacao_senha_id_recuperacao_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: rel_worldbuild; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rel_worldbuild (
    id_cena integer,
    id_cenario integer,
    id_personagem integer
);


--
-- Name: resenha; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.resenha (
    id_resenha integer NOT NULL,
    titulo_resenha character varying(100),
    id_user integer,
    sinopse text,
    class_ind integer,
    data_publi timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    id_livro integer,
    visibilidade character varying(20) DEFAULT 'publico'::character varying NOT NULL,
    CONSTRAINT resenha_visibilidade_check CHECK (((visibilidade)::text = ANY (ARRAY[('publico'::character varying)::text, ('privado'::character varying)::text])))
);


--
-- Name: resenha_id_resenha_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.resenha ALTER COLUMN id_resenha ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.resenha_id_resenha_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: sessao_usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessao_usuario (
    assinatura text NOT NULL,
    id_user integer NOT NULL,
    dispositivo text NOT NULL,
    ultimo_acesso timestamp with time zone DEFAULT now() NOT NULL,
    revogado_em timestamp with time zone
);


--
-- Name: top5_livros; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.top5_livros (
    id_user integer,
    id_livro integer,
    posicao integer,
    atualizado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario (
    id_user integer NOT NULL,
    nome_completo character varying(90) NOT NULL,
    username character varying(90) NOT NULL,
    data_nascimento date NOT NULL,
    email character varying(90) NOT NULL,
    senha character varying(250) NOT NULL,
    criacao_conta timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    google_id character varying(255),
    autor_desde timestamp with time zone
);


--
-- Name: usuario_id_user_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.usuario ALTER COLUMN id_user ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.usuario_id_user_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: versao_publicacao; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.versao_publicacao (
    id bigint NOT NULL,
    id_edicao bigint NOT NULL,
    numero integer NOT NULL,
    arquivo text NOT NULL,
    paginas integer NOT NULL,
    snapshot jsonb NOT NULL,
    declaracao text NOT NULL,
    publicada_por integer NOT NULL,
    publicada_em timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT versao_publicacao_declaracao_check CHECK ((declaracao = ANY (ARRAY['autoria'::text, 'autorizacao'::text]))),
    CONSTRAINT versao_publicacao_numero_check CHECK ((numero > 0)),
    CONSTRAINT versao_publicacao_paginas_check CHECK ((paginas > 0))
);


--
-- Name: versao_publicacao_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.versao_publicacao ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.versao_publicacao_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: whishbook; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whishbook (
    id integer NOT NULL,
    id_livro integer,
    id_user integer,
    id_whishlist integer,
    ordem integer,
    adicionado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: whishbook_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.whishbook ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.whishbook_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: whishlist; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whishlist (
    id integer NOT NULL,
    nome_lista character varying(120),
    id_user integer,
    descricao text DEFAULT ''::text NOT NULL,
    visibilidade character varying(20) DEFAULT 'privada'::character varying NOT NULL,
    tipo_capa character varying(20) DEFAULT 'automatica'::character varying NOT NULL,
    capa_url character varying(250),
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: whishlist_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.whishlist ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.whishlist_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: whishlist_seguida; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.whishlist_seguida (
    id_user integer NOT NULL,
    id_whishlist integer NOT NULL
);


--
-- Name: progresso_leitura id_progresso; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_leitura ALTER COLUMN id_progresso SET DEFAULT nextval('public.progresso_leitura_id_progresso_seq'::regclass);


--
-- PostgreSQL database dump complete
--

\unrestrict g2fzWaLHB8ftzSZaOLvgsehq0zcLRO6FZ6BxY1Cm1FvSNoaFI2JFFDEJxXkS3Aj


COPY public."autor" ("id_autor", "nome_autor", "autor", "bio", "foto_url") FROM stdin;
1	Machado de Assis	machado-de-assis	\N	\N
2	Edgar Allan Paul	edgar-allan-paul	\N	\N
3	J.K Rowling	jk-rowling	\N	\N
4	Abel Ferreira	abel-ferreira	\N	\N
5	Rick Riordan	rick-riordan	\N	\N
6	Agatha Christie	agatha-christie	\N	\N
7	George Orwell	george-orwell	\N	\N
8	Stephen King	stephen-king	\N	\N
9	J.R.R. Tolkien	jrr-tolkien	\N	\N
\.


COPY public."preferencia" ("id_preferencia", "nome_preferencia", "preferencia") FROM stdin;
1	Terror	terror
2	Com‚dia	comedia
3	Romance	romance
6	Dark Romance	dark-romance
7	Drama	drama
8	Suspense	suspense
9	A‡Æo	acao
10	Aventura	aventura
11	Fantasia	fantasia
12	Novela	novela
13	Mang 	manga
14	Hist¢ria em Quadrinhos	hq
15	Webtoons	webtoons
16	Fic‡Æo Cient¡fica	ficcao-cientifica
17	Policial	policial
18	Medico	medico
20	Biografia	biografia
21	Document rio	documentario
23	Hist¢ria	historia
24	Filosofia	filosofia
25	Psicologia	psicologia
26	Pol¡tica	politica
27	Poesia	poesia
28	Gastronomia	gastronomia
29	Mitologia	mitologia
30	Mistério	misterio
31	Clássico	classicos
33	Distopia	distopia
\.


COPY public."livro" ("id_livro", "titulo_livro", "resumo_livro", "class_ind", "nome_autor", "id_user", "sinopse_livro", "capa_url", "data_publi", "idioma", "visibilidade", "paginas", "nota_google", "google_books_id", "isbn10", "isbn13", "origem", "link_leitura", "link_compra") FROM stdin;
1	O Alienista	O Alienista, de Machado de Assis, conta a hist¢ria de SimÆo Bacamarte, um m‚dico que decide estudar a mente humana e compreender os limites entre a razÆo e a loucura. Para realizar suas pesquisas, ele cria a Casa Verde, onde come‡a a internar pessoas consideradas mentalmente desequilibradas. Com o passar do tempo, Bacamarte amplia tanto seus crit‚rios que grande parte da popula‡Æo de Itagua¡ acaba sendo considerada louca. Depois, ele muda sua teoria e passa a acreditar que aqueles que apresentam equil¡brio perfeito sÆo os verdadeiros anormais. No final, conclui que ele pr¢prio possui essa caracter¡stica e decide se internar na Casa Verde. A obra utiliza ironia e humor para criticar o abuso da ciˆncia, do poder e a dificuldade de definir o que ‚ realmente normal.	12	Machado de Assis	\N	O Alienista, de Machado de Assis, acompanha SimÆo Bacamarte, um m‚dico que se dedica a estudar a loucura e cria a Casa Verde, um local destinado … interna‡Æo de pessoas consideradas desequilibradas. Por‚m, sua busca pela defini‡Æo da normalidade faz com que cada vez mais habitantes de Itagua¡ sejam considerados loucos. A obra apresenta, de forma ir“nica e humor¡stica, uma cr¡tica ao excesso de poder, ao cientificismo e … dificuldade de determinar os limites entre a razÆo e a loucura.	../img/capas_livros/alienista-capa.jpeg	2026-08-10 11:37:10.803001	Portugues-BR	publico	\N	\N	\N	\N	\N	\N	\N	\N
2	A Cartomante	"A Cartomante", de Machado de Assis, conta a hist¢ria de Camilo e Rita, dois amigos que se envolvem em um relacionamento amoroso proibido, apesar de Rita ser casada com Vilela, amigo de Camilo. Com medo de que o marido descubra a trai‡Æo, Rita procura uma cartomante, que lhe garante que nada de ruim acontecer . Camilo inicialmente duvida desse tipo de previsÆo, mas, diante de acontecimentos que despertam seu medo e sua inseguran‡a, tamb‚m acaba recorrendo … cartomante. Ap¢s receber uma previsÆo tranquilizadora, ele segue confiante para encontrar Vilela, sem imaginar o destino que o aguarda. A narrativa combina suspense, ironia e cr¡tica … supersti‡Æo, mostrando como os personagens tentam encontrar seguran‡a em cren‡as diante da incerteza.	12	Machado de Assis	\N	"A Cartomante", de Machado de Assis, acompanha Camilo e Rita, dois amantes que vivem um relacionamento secreto, enquanto Vilela, marido de Rita e amigo de Camilo, come‡a a despertar preocupa‡Æo no casal. Em meio ao medo de serem descobertos, Rita procura uma cartomante em busca de respostas. A partir da¡, a hist¢ria conduz os personagens por uma sequˆncia de tensÆo e acontecimentos inesperados, marcada por suspense, ironia e uma reviravolta surpreendente.	../img/capas_livros/cartomante-capa.jpeg	2026-08-10 12:42:47.362398	Portugues-BR	publico	\N	\N	\N	\N	\N	\N	\N	\N
3	A Semana	""A Semana", de Machado de Assis, re£ne uma s‚rie de cr“nicas publicadas originalmente em jornais, nas quais o autor observa acontecimentos cotidianos, pol¡ticos e sociais de seu tempo. Com uma escrita marcada pela ironia, pelo humor e pela reflexÆo, Machado transforma situa‡äes aparentemente simples em oportunidades para analisar o comportamento humano e as contradi‡äes da sociedade. Ao comentar fatos da vida p£blica e do dia a dia, o autor questiona costumes, valores e atitudes presentes na sociedade brasileira. A obra se destaca pela capacidade de unir cr¡tica social e entretenimento, apresentando uma visÆo inteligente e muitas vezes bem-humorada da realidade.	12	Machado de Assis	\N	""A Semana", de Machado de Assis, re£ne cr“nicas em que o autor aborda acontecimentos cotidianos e questäes sociais e pol¡ticas de sua ‚poca. Com ironia, humor e olhar cr¡tico, Machado transforma fatos comuns em reflexäes sobre a sociedade e o comportamento humano. A obra apresenta um retrato interessante e perspicaz do Brasil de seu tempo.	../img/capas_livros/semana-capa.jpeg	2026-08-10 12:53:45.173089	Portugues-BR	publico	\N	\N	\N	\N	\N	\N	\N	\N
4	Mensagem	Mensagem, de Fernando Pessoa, ‚ uma obra po‚tica que revisita a hist¢ria e os s¡mbolos de Portugal, destacando figuras como reis, navegadores e her¢is nacionais. Dividido em trˆs partes, o livro apresenta a forma‡Æo, a realiza‡Æo e a queda simb¢lica do imp‚rio portuguˆs, relacionando o passado glorioso do pa¡s a um futuro de renova‡Æo. Por meio de poemas marcados pelo nacionalismo, pelo simbolismo e pelo misticismo, Pessoa reflete sobre o destino de Portugal e sobre a importƒncia de sua identidade hist¢rica. A obra tamb‚m aborda o sonho de um novo per¡odo de grandeza, representado pelo retorno simb¢lico de D. SebastiÆo e pelo surgimento de um novo imp‚rio, agora ligado … cultura e ao esp¡rito	10	JoÆo Pessoa	\N	Mensagem, de Fernando Pessoa, re£ne poemas que celebram e reinterpretam a hist¢ria, os her¢is e os mitos de Portugal. A obra percorre momentos fundamentais da trajet¢ria portuguesa e transforma o passado em uma reflexÆo sobre o destino e o futuro do pa¡s. Entre simbolismo, patriotismo e misticismo, Pessoa constr¢i uma visÆo po‚tica de renascimento e de uma nova grandeza portuguesa.	../img/capas_livros/mensagem-capa.jpeg	2026-08-10 13:06:38.938784	Portugues-BR	publico	\N	\N	\N	\N	\N	\N	\N	\N
5	Guardador de Rebanhos	Guardador de Rebanhos, de Fernando Pessoa, escrito sob o heter“nimo Alberto Caeiro, ‚ um conjunto de poemas que apresenta uma visÆo simples, direta e profundamente ligada … natureza. O eu l¡rico rejeita interpreta‡äes filos¢ficas e metaf¡sicas do mundo e prefere observar as coisas exatamente como elas sÆo. Para Caeiro, pensar demais sobre a realidade pode afastar o ser humano da experiˆncia verdadeira de simplesmente ver, sentir e existir. A natureza, os animais, as  rvores, as flores, o c‚u e as sensa‡äes cotidianas tornam-se elementos centrais de sua poesia. O poema tamb‚m questiona conceitos tradicionais sobre Deus, espiritualidade e transcendˆncia, defendendo uma esp‚cie de rela‡Æo concreta e imediata com o mundo.	10	JoÆo Pessoa	\N	Guardador de Rebanhos acompanha o olhar de um eu l¡rico que se apresenta como um pastor que guarda rebanhos, embora esses rebanhos sejam, sobretudo, pensamentos e sensa‡äes. Ao observar a natureza, ele desenvolve uma filosofia baseada na simplicidade, recusando explica‡äes abstratas e procurando enxergar o mundo sem atribuir-lhe significados ocultos. Ao longo da obra, a natureza funciona como fonte de conhecimento e verdade, enquanto o pensamento excessivo ‚ visto como algo que complica aquilo que deveria ser simples. A obra constr¢i, assim, uma reflexÆo po‚tica sobre a existˆncia, a percep‡Æo e a rela‡Æo do ser humano com a realidade.	../img/capas_livros/rebanhos-capa.jpg	2026-08-10 13:21:21.325504	Portugues-BR	publico	\N	\N	\N	\N	\N	\N	\N	\N
\.


COPY public."preferencia_livro" ("id", "id_preferencia", "id_livro") FROM stdin;
1	7	1
2	8	1
3	3	2
4	7	2
5	7	3
6	8	3
7	8	4
8	7	4
9	7	5
10	8	5
\.

--
-- PostgreSQL database dump
--

\restrict XXjC7E5BM6NnkekZXEh6tP9wnqOsOL4jcoDW4bUFAF7O8BTrDWhcs9WXM0tgXae

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- PostgreSQL database dump complete
--

\unrestrict XXjC7E5BM6NnkekZXEh6tP9wnqOsOL4jcoDW4bUFAF7O8BTrDWhcs9WXM0tgXae

--
-- PostgreSQL database dump
--

\restrict ml7V8Fw1dWGlfRJv8vz2Z0L8di1xr7KepG0NrdAYWU0dKhXaJEcebvL7hmz7a6b

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

--
-- Name: admin admin_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin
    ADD CONSTRAINT admin_email_key UNIQUE (email);


--
-- Name: admin admin_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin
    ADD CONSTRAINT admin_pkey PRIMARY KEY (id_admin);


--
-- Name: autor autor_autor_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autor
    ADD CONSTRAINT autor_autor_key UNIQUE (autor);


--
-- Name: autor autor_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autor
    ADD CONSTRAINT autor_pkey PRIMARY KEY (id_autor);


--
-- Name: autor_user autor_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autor_user
    ADD CONSTRAINT autor_user_pkey PRIMARY KEY (id);


--
-- Name: capitulo capitulo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capitulo
    ADD CONSTRAINT capitulo_pkey PRIMARY KEY (id_capitulo);


--
-- Name: cena cena_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cena
    ADD CONSTRAINT cena_pkey PRIMARY KEY (id_cena);


--
-- Name: cenario cenario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cenario
    ADD CONSTRAINT cenario_pkey PRIMARY KEY (id_cenario);


--
-- Name: comentario_obra comentario_obra_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comentario_obra
    ADD CONSTRAINT comentario_obra_pkey PRIMARY KEY (id);


--
-- Name: comentario comentario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comentario
    ADD CONSTRAINT comentario_pkey PRIMARY KEY (id_comentario);


--
-- Name: configuracao_usuario configuracao_usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.configuracao_usuario
    ADD CONSTRAINT configuracao_usuario_pkey PRIMARY KEY (id_user);


--
-- Name: conversa conversa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversa
    ADD CONSTRAINT conversa_pkey PRIMARY KEY (id_conversa);


--
-- Name: curtida curtida_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curtida
    ADD CONSTRAINT curtida_pkey PRIMARY KEY (id_curtida);


--
-- Name: denuncia_obra denuncia_obra_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.denuncia_obra
    ADD CONSTRAINT denuncia_obra_pkey PRIMARY KEY (id);


--
-- Name: edicao_livro edicao_livro_id_livro_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.edicao_livro
    ADD CONSTRAINT edicao_livro_id_livro_numero_key UNIQUE (id_livro, numero);


--
-- Name: edicao_livro edicao_livro_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.edicao_livro
    ADD CONSTRAINT edicao_livro_pkey PRIMARY KEY (id);


--
-- Name: email_verificacao email_verificacao_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_verificacao
    ADD CONSTRAINT email_verificacao_pkey PRIMARY KEY (id_user);


--
-- Name: email_verificacao email_verificacao_token_hash_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_verificacao
    ADD CONSTRAINT email_verificacao_token_hash_key UNIQUE (token_hash);


--
-- Name: follow_autor follow_autor_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.follow_autor
    ADD CONSTRAINT follow_autor_pkey PRIMARY KEY (id_follower, id_autor);


--
-- Name: livro livro_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.livro
    ADD CONSTRAINT livro_pkey PRIMARY KEY (id_livro);


--
-- Name: marcador_leitura marcador_leitura_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marcador_leitura
    ADD CONSTRAINT marcador_leitura_pkey PRIMARY KEY (id);


--
-- Name: mencao mencao_id_origem_id_destino_categoria_id_conteudo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mencao
    ADD CONSTRAINT mencao_id_origem_id_destino_categoria_id_conteudo_key UNIQUE (id_origem, id_destino, categoria, id_conteudo);


--
-- Name: mencao mencao_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mencao
    ADD CONSTRAINT mencao_pkey PRIMARY KEY (id);


--
-- Name: mensagem mensagem_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_pkey PRIMARY KEY (id_mensagem);


--
-- Name: meta_leitura_listas meta_leitura_listas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meta_leitura_listas
    ADD CONSTRAINT meta_leitura_listas_pkey PRIMARY KEY (id_meta, id_lista);


--
-- Name: meta_leitura meta_leitura_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meta_leitura
    ADD CONSTRAINT meta_leitura_pkey PRIMARY KEY (id);


--
-- Name: moderacao_obra moderacao_obra_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.moderacao_obra
    ADD CONSTRAINT moderacao_obra_pkey PRIMARY KEY (id);


--
-- Name: paragrafo paragrafo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.paragrafo
    ADD CONSTRAINT paragrafo_pkey PRIMARY KEY (id_paragrafo);


--
-- Name: paragrafo_resenha paragrafo_resenha_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.paragrafo_resenha
    ADD CONSTRAINT paragrafo_resenha_pkey PRIMARY KEY (id_paragrafo_resenha);


--
-- Name: personagem personagem_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personagem
    ADD CONSTRAINT personagem_pkey PRIMARY KEY (id_personagem);


--
-- Name: post post_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.post
    ADD CONSTRAINT post_pkey PRIMARY KEY (id_post);


--
-- Name: preferencia_livro preferencia_livro_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia_livro
    ADD CONSTRAINT preferencia_livro_pkey PRIMARY KEY (id);


--
-- Name: preferencia preferencia_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia
    ADD CONSTRAINT preferencia_pkey PRIMARY KEY (id_preferencia);


--
-- Name: preferencia preferencia_preferencia_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia
    ADD CONSTRAINT preferencia_preferencia_key UNIQUE (preferencia);


--
-- Name: preferencia_user preferencia_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT preferencia_user_pkey PRIMARY KEY (id);


--
-- Name: progresso_leitura progresso_leitura_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_leitura
    ADD CONSTRAINT progresso_leitura_pkey PRIMARY KEY (id_progresso);


--
-- Name: progresso_publicacao progresso_publicacao_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_publicacao
    ADD CONSTRAINT progresso_publicacao_pkey PRIMARY KEY (id_user, id_livro);


--
-- Name: publicacao_obra publicacao_obra_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicacao_obra
    ADD CONSTRAINT publicacao_obra_pkey PRIMARY KEY (id_livro);


--
-- Name: recuperacao_senha recuperacao_senha_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recuperacao_senha
    ADD CONSTRAINT recuperacao_senha_pkey PRIMARY KEY (id_recuperacao);


--
-- Name: resenha resenha_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_pkey PRIMARY KEY (id_resenha);


--
-- Name: sessao_usuario sessao_usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessao_usuario
    ADD CONSTRAINT sessao_usuario_pkey PRIMARY KEY (assinatura);


--
-- Name: usuario usuario_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_email_key UNIQUE (email);


--
-- Name: usuario usuario_google_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_google_id_key UNIQUE (google_id);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id_user);


--
-- Name: usuario usuario_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_username_key UNIQUE (username);


--
-- Name: versao_publicacao versao_publicacao_id_edicao_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.versao_publicacao
    ADD CONSTRAINT versao_publicacao_id_edicao_numero_key UNIQUE (id_edicao, numero);


--
-- Name: versao_publicacao versao_publicacao_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.versao_publicacao
    ADD CONSTRAINT versao_publicacao_pkey PRIMARY KEY (id);


--
-- Name: whishbook whishbook_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT whishbook_pkey PRIMARY KEY (id);


--
-- Name: whishlist whishlist_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishlist
    ADD CONSTRAINT whishlist_pkey PRIMARY KEY (id);


--
-- Name: whishlist_seguida whishlist_seguida_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishlist_seguida
    ADD CONSTRAINT whishlist_seguida_pkey PRIMARY KEY (id_user, id_whishlist);


--
-- Name: comentario_obra_livro; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX comentario_obra_livro ON public.comentario_obra USING btree (id_livro, criado_em DESC);


--
-- Name: comentario_obra_usuario; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX comentario_obra_usuario ON public.comentario_obra USING btree (id_user, criado_em DESC);


--
-- Name: denuncia_obra_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX denuncia_obra_estado ON public.denuncia_obra USING btree (estado, criada_em DESC);


--
-- Name: livro_google_books_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX livro_google_books_idx ON public.livro USING btree (google_books_id) WHERE (google_books_id IS NOT NULL);


--
-- Name: marcador_leitura_usuario_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX marcador_leitura_usuario_idx ON public.marcador_leitura USING btree (id_user, criado_em DESC);


--
-- Name: mencao_destino_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mencao_destino_idx ON public.mencao USING btree (id_destino, criado_em DESC);


--
-- Name: publicacao_autor_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX publicacao_autor_estado ON public.publicacao_obra USING btree (id_autor, estado, atualizado_em DESC);


--
-- Name: sessao_usuario_user_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessao_usuario_user_idx ON public.sessao_usuario USING btree (id_user, ultimo_acesso DESC);


--
-- Name: bloqueio bloqueio_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bloqueio
    ADD CONSTRAINT bloqueio_usuario FOREIGN KEY (id_bloqueador) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: bloqueio bloqueio_usuario2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bloqueio
    ADD CONSTRAINT bloqueio_usuario2 FOREIGN KEY (id_bloqueado) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: capitulo capitulo_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capitulo
    ADD CONSTRAINT capitulo_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: cena cena_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cena
    ADD CONSTRAINT cena_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild cena_rel; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT cena_rel FOREIGN KEY (id_cena) REFERENCES public.cena(id_cena) ON DELETE CASCADE;


--
-- Name: cenario cenario_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cenario
    ADD CONSTRAINT cenario_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild cenario_rel; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT cenario_rel FOREIGN KEY (id_cenario) REFERENCES public.cenario(id_cenario) ON DELETE CASCADE;


--
-- Name: comentario_obra comentario_obra_id_livro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comentario_obra
    ADD CONSTRAINT comentario_obra_id_livro_fkey FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: comentario_obra comentario_obra_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comentario_obra
    ADD CONSTRAINT comentario_obra_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: configuracao_usuario configuracao_usuario_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.configuracao_usuario
    ADD CONSTRAINT configuracao_usuario_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: conversa conversa_dono; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversa
    ADD CONSTRAINT conversa_dono FOREIGN KEY (id_dono) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: denuncia_obra denuncia_obra_id_livro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.denuncia_obra
    ADD CONSTRAINT denuncia_obra_id_livro_fkey FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: denuncia_obra denuncia_obra_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.denuncia_obra
    ADD CONSTRAINT denuncia_obra_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: edicao_livro edicao_livro_id_livro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.edicao_livro
    ADD CONSTRAINT edicao_livro_id_livro_fkey FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: email_verificacao email_verificacao_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_verificacao
    ADD CONSTRAINT email_verificacao_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: autor_user fk_autor; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autor_user
    ADD CONSTRAINT fk_autor FOREIGN KEY (id_autor) REFERENCES public.autor(id_autor) ON DELETE CASCADE;


--
-- Name: conta fk_conta_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conta
    ADD CONSTRAINT fk_conta_usuario FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishbook fk_livro_book; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT fk_livro_book FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: preferencia_livro fk_livro_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia_livro
    ADD CONSTRAINT fk_livro_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: preferencia_user fk_preferencia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT fk_preferencia FOREIGN KEY (id_preferencia) REFERENCES public.preferencia(id_preferencia) ON DELETE CASCADE;


--
-- Name: preferencia_livro fk_preferencia_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia_livro
    ADD CONSTRAINT fk_preferencia_livro FOREIGN KEY (id_preferencia) REFERENCES public.preferencia(id_preferencia);


--
-- Name: recuperacao_senha fk_recuperacao_senha; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.recuperacao_senha
    ADD CONSTRAINT fk_recuperacao_senha FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: autor_user fk_user_autor; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.autor_user
    ADD CONSTRAINT fk_user_autor FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishbook fk_user_book; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT fk_user_book FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: comentario fk_user_comentario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comentario
    ADD CONSTRAINT fk_user_comentario FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: curtida fk_user_curtida; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.curtida
    ADD CONSTRAINT fk_user_curtida FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: preferencia_user fk_user_pref; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT fk_user_pref FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishlist fk_user_whish; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishlist
    ADD CONSTRAINT fk_user_whish FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishbook fk_whish_book; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishbook
    ADD CONSTRAINT fk_whish_book FOREIGN KEY (id_whishlist) REFERENCES public.whishlist(id);


--
-- Name: follow_autor follow_autor_id_autor_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.follow_autor
    ADD CONSTRAINT follow_autor_id_autor_fkey FOREIGN KEY (id_autor) REFERENCES public.autor(id_autor) ON DELETE CASCADE;


--
-- Name: follow_autor follow_autor_id_follower_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.follow_autor
    ADD CONSTRAINT follow_autor_id_follower_fkey FOREIGN KEY (id_follower) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: follow follow_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.follow
    ADD CONSTRAINT follow_usuario FOREIGN KEY (id_follower) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: follow follow_usuario2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.follow
    ADD CONSTRAINT follow_usuario2 FOREIGN KEY (id_following) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: livros_lidos lidos_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.livros_lidos
    ADD CONSTRAINT lidos_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: livros_lidos lidos_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.livros_lidos
    ADD CONSTRAINT lidos_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: progresso_leitura livro_progresso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_leitura
    ADD CONSTRAINT livro_progresso FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: livro livro_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.livro
    ADD CONSTRAINT livro_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: marcador_leitura marcador_leitura_id_livro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marcador_leitura
    ADD CONSTRAINT marcador_leitura_id_livro_fkey FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: marcador_leitura marcador_leitura_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.marcador_leitura
    ADD CONSTRAINT marcador_leitura_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: mencao mencao_id_destino_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mencao
    ADD CONSTRAINT mencao_id_destino_fkey FOREIGN KEY (id_destino) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: mencao mencao_id_origem_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mencao
    ADD CONSTRAINT mencao_id_origem_fkey FOREIGN KEY (id_origem) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: mensagem mensagem_conversa; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_conversa FOREIGN KEY (id_conversa) REFERENCES public.conversa(id_conversa) ON DELETE CASCADE;


--
-- Name: mensagem mensagem_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_user FOREIGN KEY (id_envio) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: meta_leitura_listas meta_leitura_listas_id_lista_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meta_leitura_listas
    ADD CONSTRAINT meta_leitura_listas_id_lista_fkey FOREIGN KEY (id_lista) REFERENCES public.whishlist(id) ON DELETE CASCADE;


--
-- Name: meta_leitura_listas meta_leitura_listas_id_meta_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meta_leitura_listas
    ADD CONSTRAINT meta_leitura_listas_id_meta_fkey FOREIGN KEY (id_meta) REFERENCES public.meta_leitura(id) ON DELETE CASCADE;


--
-- Name: meta_leitura meta_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meta_leitura
    ADD CONSTRAINT meta_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: moderacao_obra moderacao_obra_id_admin_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.moderacao_obra
    ADD CONSTRAINT moderacao_obra_id_admin_fkey FOREIGN KEY (id_admin) REFERENCES public.admin(id_admin);


--
-- Name: moderacao_obra moderacao_obra_id_livro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.moderacao_obra
    ADD CONSTRAINT moderacao_obra_id_livro_fkey FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: paragrafo paragrafo_capitulo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.paragrafo
    ADD CONSTRAINT paragrafo_capitulo FOREIGN KEY (id_capitulo) REFERENCES public.capitulo(id_capitulo) ON DELETE CASCADE;


--
-- Name: paragrafo_resenha paragrafo_idresenha; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.paragrafo_resenha
    ADD CONSTRAINT paragrafo_idresenha FOREIGN KEY (id_resenha) REFERENCES public.resenha(id_resenha) ON DELETE CASCADE;


--
-- Name: conversa_participante participante_conversa; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversa_participante
    ADD CONSTRAINT participante_conversa FOREIGN KEY (id_conversa) REFERENCES public.conversa(id_conversa) ON DELETE CASCADE;


--
-- Name: conversa_participante participante_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversa_participante
    ADD CONSTRAINT participante_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: personagem personagem_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personagem
    ADD CONSTRAINT personagem_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild personagem_rel; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT personagem_rel FOREIGN KEY (id_personagem) REFERENCES public.personagem(id_personagem) ON DELETE CASCADE;


--
-- Name: post post_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.post
    ADD CONSTRAINT post_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: progresso_publicacao progresso_publicacao_id_livro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_publicacao
    ADD CONSTRAINT progresso_publicacao_id_livro_fkey FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: progresso_publicacao progresso_publicacao_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_publicacao
    ADD CONSTRAINT progresso_publicacao_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: progresso_publicacao progresso_publicacao_id_versao_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_publicacao
    ADD CONSTRAINT progresso_publicacao_id_versao_fkey FOREIGN KEY (id_versao) REFERENCES public.versao_publicacao(id);


--
-- Name: publicacao_obra publicacao_obra_id_autor_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicacao_obra
    ADD CONSTRAINT publicacao_obra_id_autor_fkey FOREIGN KEY (id_autor) REFERENCES public.usuario(id_user);


--
-- Name: publicacao_obra publicacao_obra_id_genero_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicacao_obra
    ADD CONSTRAINT publicacao_obra_id_genero_fkey FOREIGN KEY (id_genero) REFERENCES public.preferencia(id_preferencia);


--
-- Name: publicacao_obra publicacao_obra_id_livro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicacao_obra
    ADD CONSTRAINT publicacao_obra_id_livro_fkey FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: publicacao_obra publicacao_obra_id_versao_atual_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.publicacao_obra
    ADD CONSTRAINT publicacao_obra_id_versao_atual_fkey FOREIGN KEY (id_versao_atual) REFERENCES public.versao_publicacao(id);


--
-- Name: resenha resenha_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: resenha resenha_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: sessao_usuario sessao_usuario_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessao_usuario
    ADD CONSTRAINT sessao_usuario_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: top5_livros top5_livro; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.top5_livros
    ADD CONSTRAINT top5_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: top5_livros top5_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.top5_livros
    ADD CONSTRAINT top5_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: progresso_leitura user_progresso; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.progresso_leitura
    ADD CONSTRAINT user_progresso FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: versao_publicacao versao_publicacao_id_edicao_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.versao_publicacao
    ADD CONSTRAINT versao_publicacao_id_edicao_fkey FOREIGN KEY (id_edicao) REFERENCES public.edicao_livro(id);


--
-- Name: versao_publicacao versao_publicacao_publicada_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.versao_publicacao
    ADD CONSTRAINT versao_publicacao_publicada_por_fkey FOREIGN KEY (publicada_por) REFERENCES public.usuario(id_user);


--
-- Name: whishlist_seguida whishlist_seguida_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishlist_seguida
    ADD CONSTRAINT whishlist_seguida_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishlist_seguida whishlist_seguida_id_whishlist_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.whishlist_seguida
    ADD CONSTRAINT whishlist_seguida_id_whishlist_fkey FOREIGN KEY (id_whishlist) REFERENCES public.whishlist(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict ml7V8Fw1dWGlfRJv8vz2Z0L8di1xr7KepG0NrdAYWU0dKhXaJEcebvL7hmz7a6b

