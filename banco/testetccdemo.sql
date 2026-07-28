--
-- PostgreSQL database dump
--

\restrict AMZ82CpuB3g8no3MgAUgJw0sNfOyGmpGqmj0od0SteoGKkYvgk5xtL8S4YzEUMB

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

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

SET default_table_access_method = heap;

--
-- Name: autor; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.autor (
    id_autor integer NOT NULL,
    nome_autor character varying(90),
    autor character varying(90) NOT NULL
);


ALTER TABLE public.autor OWNER TO postgres;

--
-- Name: autor_id_autor_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: bloqueio; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bloqueio (
    id_bloqueador integer,
    id_bloqueado integer,
    status_bloqueio character varying(50),
    data_bloqueio timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.bloqueio OWNER TO postgres;

--
-- Name: capitulo; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.capitulo (
    id_capitulo integer NOT NULL,
    titulo_capitulo character varying(100),
    imagem_url_capitulo character varying(250),
    ordem_capitulo integer,
    id_livro integer
);


ALTER TABLE public.capitulo OWNER TO postgres;

--
-- Name: capitulo_id_capitulo_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: cena; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cena (
    id_cena integer NOT NULL,
    nome_cena character varying(150),
    descricao text,
    id_livro integer
);


ALTER TABLE public.cena OWNER TO postgres;

--
-- Name: cena_id_cena_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: cenario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cenario (
    id_cenario integer NOT NULL,
    nome_cenario character varying(100),
    descricao_cenario text,
    id_livro integer
);


ALTER TABLE public.cenario OWNER TO postgres;

--
-- Name: cenario_id_cenario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: comentario_livro; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.comentario_livro (
    id_comentario integer NOT NULL,
    id_user integer,
    id_livro integer,
    nota_avaliacao real,
    comentario text
);


ALTER TABLE public.comentario_livro OWNER TO postgres;

--
-- Name: comentario_livro_id_comentario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.comentario_livro ALTER COLUMN id_comentario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.comentario_livro_id_comentario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: conta; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conta (
    id_user integer,
    foto_perfil_url character varying(240),
    bio text
);


ALTER TABLE public.conta OWNER TO postgres;

--
-- Name: conversa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conversa (
    id_conversa integer NOT NULL,
    tipo character varying(90),
    nome_conversa character varying(80),
    foto_conversa_url character varying(250),
    id_dono integer,
    criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.conversa OWNER TO postgres;

--
-- Name: conversa_id_conversa_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: conversa_participante; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conversa_participante (
    id_conversa integer,
    id_user integer,
    cargo character varying(100),
    joinet_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    last_read_message timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.conversa_participante OWNER TO postgres;

--
-- Name: follow; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.follow (
    id_follower integer,
    id_following integer,
    status_follow character varying(50),
    data_follow timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.follow OWNER TO postgres;

--
-- Name: livro; Type: TABLE; Schema: public; Owner: postgres
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
    idioma character varying(90)
);


ALTER TABLE public.livro OWNER TO postgres;

--
-- Name: livro_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: livros_lidos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.livros_lidos (
    id_user integer,
    id_livro integer,
    atualizado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.livros_lidos OWNER TO postgres;

--
-- Name: mensagem; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.mensagem OWNER TO postgres;

--
-- Name: mensagem_id_mensagem_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: paragrafo; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.paragrafo (
    id_paragrafo integer NOT NULL,
    texto_paragrafo text,
    imagem_paragrafo_url character varying(250),
    ordem_paragrafo integer,
    id_capitulo integer
);


ALTER TABLE public.paragrafo OWNER TO postgres;

--
-- Name: paragrafo_id_paragrafo_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: paragrafo_resenha; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.paragrafo_resenha (
    id_paragrafo_resenha integer NOT NULL,
    texto_paragrafo_resenha text,
    id_resenha integer,
    ordem_paragrafo_resenha integer
);


ALTER TABLE public.paragrafo_resenha OWNER TO postgres;

--
-- Name: paragrafo_resenha_id_paragrafo_resenha_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: personagem; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.personagem OWNER TO postgres;

--
-- Name: personagem_id_personagem_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: post; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.post (
    id_post integer NOT NULL,
    titulo_post character varying(90),
    id_user integer,
    url_imagem character varying(250),
    conteudo text,
    criado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.post OWNER TO postgres;

--
-- Name: post_id_post_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: preferencia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.preferencia (
    id_preferencia integer NOT NULL,
    nome_preferencia character varying(90),
    preferencia character varying(90) NOT NULL
);


ALTER TABLE public.preferencia OWNER TO postgres;

--
-- Name: preferencia_id_preferencia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: preferencia_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.preferencia_user (
    id_user integer,
    id_preferencia integer,
    id_autor integer
);


ALTER TABLE public.preferencia_user OWNER TO postgres;

--
-- Name: rel_worldbuild; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rel_worldbuild (
    id_cena integer,
    id_cenario integer,
    id_personagem integer
);


ALTER TABLE public.rel_worldbuild OWNER TO postgres;

--
-- Name: resenha; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.resenha (
    id_resenha integer NOT NULL,
    titulo_resenha character varying(100),
    id_user integer,
    sinopse text,
    class_ind integer,
    data_publi timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    id_livro integer
);


ALTER TABLE public.resenha OWNER TO postgres;

--
-- Name: resenha_id_resenha_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: top5_livros; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.top5_livros (
    id_user integer,
    id_livro integer,
    posicao integer,
    atualizado_em timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.top5_livros OWNER TO postgres;

--
-- Name: usuario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuario (
    id_user integer NOT NULL,
    nome_completo character varying(90) NOT NULL,
    username character varying(90) NOT NULL,
    data_nascimento date NOT NULL,
    email character varying(90) NOT NULL,
    senha character varying(90) NOT NULL,
    criacao_conta timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.usuario OWNER TO postgres;

--
-- Name: usuario_id_user_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Name: whishlist; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.whishlist (
    id integer NOT NULL,
    nome_lista character varying(100),
    id_user integer,
    id_livro integer
);


ALTER TABLE public.whishlist OWNER TO postgres;

--
-- Name: whishlist_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
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
-- Data for Name: autor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.autor (id_autor, nome_autor, autor) FROM stdin;
1	Machado de Assis	machado
2	Edgar Allan Paul	edgar
3	J.K Rowling	jkrowling
4	Abel Ferreira	abel
\.


--
-- Data for Name: bloqueio; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bloqueio (id_bloqueador, id_bloqueado, status_bloqueio, data_bloqueio) FROM stdin;
2	1	OK	2026-07-21 22:10:48.130379
3	1	OK	2026-07-21 22:10:52.955569
4	1	OK	2026-07-21 22:10:56.403813
1	2	OK	2026-07-21 22:11:02.811531
1	3	OK	2026-07-21 22:11:06.052014
1	4	OK	2026-07-21 22:11:08.891494
\.


--
-- Data for Name: capitulo; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.capitulo (id_capitulo, titulo_capitulo, imagem_url_capitulo, ordem_capitulo, id_livro) FROM stdin;
\.


--
-- Data for Name: cena; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cena (id_cena, nome_cena, descricao, id_livro) FROM stdin;
\.


--
-- Data for Name: cenario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cenario (id_cenario, nome_cenario, descricao_cenario, id_livro) FROM stdin;
\.


--
-- Data for Name: comentario_livro; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.comentario_livro (id_comentario, id_user, id_livro, nota_avaliacao, comentario) FROM stdin;
\.


--
-- Data for Name: conta; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.conta (id_user, foto_perfil_url, bio) FROM stdin;
2	foto_do_flaco	Atacante argentino, amigo pr¢ximo de Lionel Messi, MVP FIFA World Cup 2026
3	foto_do_gomez	Zagueiro Paraguaio, dono da sele‡Æo paraguaia e maior capitÆo da hist¢ria do Palmeiras
4	foto_do_roque	Atacante Brasileiro, jovem promessa e futuro da sele‡Æo
\.


--
-- Data for Name: conversa; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.conversa (id_conversa, tipo, nome_conversa, foto_conversa_url, id_dono, criacao) FROM stdin;
\.


--
-- Data for Name: conversa_participante; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.conversa_participante (id_conversa, id_user, cargo, joinet_at, last_read_message) FROM stdin;
\.


--
-- Data for Name: follow; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.follow (id_follower, id_following, status_follow, data_follow) FROM stdin;
2	3	OK	2026-07-21 22:09:31.436505
3	2	OK	2026-07-21 22:09:36.387145
2	4	OK	2026-07-21 22:09:41.420055
4	2	OK	2026-07-21 22:09:46.108366
3	4	OK	2026-07-21 22:09:50.116518
4	3	OK	2026-07-21 22:09:54.927026
\.


--
-- Data for Name: livro; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.livro (id_livro, titulo_livro, resumo_livro, class_ind, nome_autor, id_user, sinopse_livro, capa_url, data_publi, idioma) FROM stdin;
\.


--
-- Data for Name: livros_lidos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.livros_lidos (id_user, id_livro, atualizado_em) FROM stdin;
\.


--
-- Data for Name: mensagem; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mensagem (id_mensagem, id_conversa, id_envio, tipo, conteudo, criacao, editado_em, delatado_em) FROM stdin;
\.


--
-- Data for Name: paragrafo; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.paragrafo (id_paragrafo, texto_paragrafo, imagem_paragrafo_url, ordem_paragrafo, id_capitulo) FROM stdin;
\.


--
-- Data for Name: paragrafo_resenha; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.paragrafo_resenha (id_paragrafo_resenha, texto_paragrafo_resenha, id_resenha, ordem_paragrafo_resenha) FROM stdin;
\.


--
-- Data for Name: personagem; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.personagem (id_personagem, nome_personagem, genero, idade, funcao, descricao_personagem, id_livro) FROM stdin;
\.


--
-- Data for Name: post; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.post (id_post, titulo_post, id_user, url_imagem, conteudo, criado_em) FROM stdin;
\.


--
-- Data for Name: preferencia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.preferencia (id_preferencia, nome_preferencia, preferencia) FROM stdin;
1	Terror	terror
2	Com‚dia	comedia
3	Romance	romance
4	Fic‡Æo	ficcao
\.


--
-- Data for Name: preferencia_user; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.preferencia_user (id_user, id_preferencia, id_autor) FROM stdin;
1	1	1
2	3	4
3	2	4
4	4	4
4	3	2
4	2	1
3	2	1
2	2	3
\.


--
-- Data for Name: rel_worldbuild; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rel_worldbuild (id_cena, id_cenario, id_personagem) FROM stdin;
\.


--
-- Data for Name: resenha; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.resenha (id_resenha, titulo_resenha, id_user, sinopse, class_ind, data_publi, id_livro) FROM stdin;
\.


--
-- Data for Name: top5_livros; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.top5_livros (id_user, id_livro, posicao, atualizado_em) FROM stdin;
\.


--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuario (id_user, nome_completo, username, data_nascimento, email, senha, criacao_conta) FROM stdin;
1	Joao Silva	Josilva	1990-08-31	joaosilva@gmail.com	joao123	2026-07-21 21:57:04.454206
2	Jose Emanuel Lopez	Flaco Lopez	2000-05-23	flacolopez@gmail.com	flaquito123	2026-07-21 21:57:55.607953
3	Gustavo Gomez	Xerife	1994-07-13	gugo@gmail.com	gomez15	2026-07-21 21:58:42.374901
4	Vitor Roque	Tigrinho	2003-06-28	vitorroque@gmail.com	tigrinho9	2026-07-21 21:59:48.855492
\.


--
-- Data for Name: whishlist; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.whishlist (id, nome_lista, id_user, id_livro) FROM stdin;
\.


--
-- Name: autor_id_autor_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.autor_id_autor_seq', 4, true);


--
-- Name: capitulo_id_capitulo_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.capitulo_id_capitulo_seq', 1, false);


--
-- Name: cena_id_cena_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cena_id_cena_seq', 1, false);


--
-- Name: cenario_id_cenario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cenario_id_cenario_seq', 1, false);


--
-- Name: comentario_livro_id_comentario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.comentario_livro_id_comentario_seq', 1, false);


--
-- Name: conversa_id_conversa_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.conversa_id_conversa_seq', 1, false);


--
-- Name: livro_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.livro_id_seq', 1, false);


--
-- Name: mensagem_id_mensagem_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mensagem_id_mensagem_seq', 1, false);


--
-- Name: paragrafo_id_paragrafo_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.paragrafo_id_paragrafo_seq', 1, false);


--
-- Name: paragrafo_resenha_id_paragrafo_resenha_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.paragrafo_resenha_id_paragrafo_resenha_seq', 1, false);


--
-- Name: personagem_id_personagem_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.personagem_id_personagem_seq', 1, false);


--
-- Name: post_id_post_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.post_id_post_seq', 1, false);


--
-- Name: preferencia_id_preferencia_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.preferencia_id_preferencia_seq', 4, true);


--
-- Name: resenha_id_resenha_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.resenha_id_resenha_seq', 1, false);


--
-- Name: usuario_id_user_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuario_id_user_seq', 4, true);


--
-- Name: whishlist_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.whishlist_id_seq', 1, false);


--
-- Name: autor autor_autor_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autor
    ADD CONSTRAINT autor_autor_key UNIQUE (autor);


--
-- Name: autor autor_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autor
    ADD CONSTRAINT autor_pkey PRIMARY KEY (id_autor);


--
-- Name: capitulo capitulo_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capitulo
    ADD CONSTRAINT capitulo_pkey PRIMARY KEY (id_capitulo);


--
-- Name: cena cena_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cena
    ADD CONSTRAINT cena_pkey PRIMARY KEY (id_cena);


--
-- Name: cenario cenario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cenario
    ADD CONSTRAINT cenario_pkey PRIMARY KEY (id_cenario);


--
-- Name: comentario_livro comentario_livro_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.comentario_livro
    ADD CONSTRAINT comentario_livro_pkey PRIMARY KEY (id_comentario);


--
-- Name: conversa conversa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa
    ADD CONSTRAINT conversa_pkey PRIMARY KEY (id_conversa);


--
-- Name: livro livro_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livro
    ADD CONSTRAINT livro_pkey PRIMARY KEY (id_livro);


--
-- Name: mensagem mensagem_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_pkey PRIMARY KEY (id_mensagem);


--
-- Name: paragrafo paragrafo_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo
    ADD CONSTRAINT paragrafo_pkey PRIMARY KEY (id_paragrafo);


--
-- Name: paragrafo_resenha paragrafo_resenha_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo_resenha
    ADD CONSTRAINT paragrafo_resenha_pkey PRIMARY KEY (id_paragrafo_resenha);


--
-- Name: personagem personagem_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personagem
    ADD CONSTRAINT personagem_pkey PRIMARY KEY (id_personagem);


--
-- Name: post post_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post
    ADD CONSTRAINT post_pkey PRIMARY KEY (id_post);


--
-- Name: preferencia preferencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia
    ADD CONSTRAINT preferencia_pkey PRIMARY KEY (id_preferencia);


--
-- Name: preferencia preferencia_preferencia_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia
    ADD CONSTRAINT preferencia_preferencia_key UNIQUE (preferencia);


--
-- Name: resenha resenha_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_pkey PRIMARY KEY (id_resenha);


--
-- Name: usuario usuario_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_email_key UNIQUE (email);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id_user);


--
-- Name: usuario usuario_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_username_key UNIQUE (username);


--
-- Name: whishlist whishlist_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishlist
    ADD CONSTRAINT whishlist_pkey PRIMARY KEY (id);


--
-- Name: bloqueio bloqueio_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bloqueio
    ADD CONSTRAINT bloqueio_usuario FOREIGN KEY (id_bloqueador) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: bloqueio bloqueio_usuario2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bloqueio
    ADD CONSTRAINT bloqueio_usuario2 FOREIGN KEY (id_bloqueado) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: capitulo capitulo_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.capitulo
    ADD CONSTRAINT capitulo_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: cena cena_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cena
    ADD CONSTRAINT cena_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild cena_rel; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT cena_rel FOREIGN KEY (id_cena) REFERENCES public.cena(id_cena) ON DELETE CASCADE;


--
-- Name: cenario cenario_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cenario
    ADD CONSTRAINT cenario_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild cenario_rel; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT cenario_rel FOREIGN KEY (id_cenario) REFERENCES public.cenario(id_cenario) ON DELETE CASCADE;


--
-- Name: comentario_livro coment_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.comentario_livro
    ADD CONSTRAINT coment_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro);


--
-- Name: comentario_livro coment_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.comentario_livro
    ADD CONSTRAINT coment_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user);


--
-- Name: conversa conversa_dono; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa
    ADD CONSTRAINT conversa_dono FOREIGN KEY (id_dono) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: conta fk_conta_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conta
    ADD CONSTRAINT fk_conta_usuario FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: follow follow_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.follow
    ADD CONSTRAINT follow_usuario FOREIGN KEY (id_follower) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: follow follow_usuario2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.follow
    ADD CONSTRAINT follow_usuario2 FOREIGN KEY (id_following) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: livros_lidos lidos_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livros_lidos
    ADD CONSTRAINT lidos_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: livros_lidos lidos_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livros_lidos
    ADD CONSTRAINT lidos_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: livro livro_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.livro
    ADD CONSTRAINT livro_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: mensagem mensagem_conversa; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_conversa FOREIGN KEY (id_conversa) REFERENCES public.conversa(id_conversa) ON DELETE CASCADE;


--
-- Name: mensagem mensagem_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mensagem
    ADD CONSTRAINT mensagem_user FOREIGN KEY (id_envio) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: paragrafo paragrafo_capitulo; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo
    ADD CONSTRAINT paragrafo_capitulo FOREIGN KEY (id_capitulo) REFERENCES public.capitulo(id_capitulo) ON DELETE CASCADE;


--
-- Name: paragrafo_resenha paragrafo_idresenha; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.paragrafo_resenha
    ADD CONSTRAINT paragrafo_idresenha FOREIGN KEY (id_resenha) REFERENCES public.resenha(id_resenha) ON DELETE CASCADE;


--
-- Name: conversa_participante participante_conversa; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa_participante
    ADD CONSTRAINT participante_conversa FOREIGN KEY (id_conversa) REFERENCES public.conversa(id_conversa) ON DELETE CASCADE;


--
-- Name: conversa_participante participante_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversa_participante
    ADD CONSTRAINT participante_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: personagem personagem_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personagem
    ADD CONSTRAINT personagem_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: rel_worldbuild personagem_rel; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rel_worldbuild
    ADD CONSTRAINT personagem_rel FOREIGN KEY (id_personagem) REFERENCES public.personagem(id_personagem) ON DELETE CASCADE;


--
-- Name: post post_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.post
    ADD CONSTRAINT post_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: preferencia_user preferenciauser_autor; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT preferenciauser_autor FOREIGN KEY (id_autor) REFERENCES public.autor(id_autor) ON DELETE CASCADE;


--
-- Name: preferencia_user preferenciauser_preferencia; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT preferenciauser_preferencia FOREIGN KEY (id_preferencia) REFERENCES public.preferencia(id_preferencia) ON DELETE CASCADE;


--
-- Name: preferencia_user preferenciauser_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.preferencia_user
    ADD CONSTRAINT preferenciauser_usuario FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: resenha resenha_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: resenha resenha_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.resenha
    ADD CONSTRAINT resenha_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: top5_livros top5_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.top5_livros
    ADD CONSTRAINT top5_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: top5_livros top5_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.top5_livros
    ADD CONSTRAINT top5_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- Name: whishlist whishlist_livro; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishlist
    ADD CONSTRAINT whishlist_livro FOREIGN KEY (id_livro) REFERENCES public.livro(id_livro) ON DELETE CASCADE;


--
-- Name: whishlist whishlist_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.whishlist
    ADD CONSTRAINT whishlist_user FOREIGN KEY (id_user) REFERENCES public.usuario(id_user) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict AMZ82CpuB3g8no3MgAUgJw0sNfOyGmpGqmj0od0SteoGKkYvgk5xtL8S4YzEUMB

