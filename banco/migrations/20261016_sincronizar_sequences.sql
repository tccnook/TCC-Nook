DO $$
DECLARE
    coluna RECORD;
    maior_id BIGINT;
    ultimo_valor BIGINT;
BEGIN
    FOR coluna IN
        SELECT
            c.table_schema,
            c.table_name,
            c.column_name,
            pg_get_serial_sequence(
                format('%I.%I', c.table_schema, c.table_name),
                c.column_name
            ) AS sequence_name
        FROM information_schema.columns c
        WHERE c.table_schema = 'public'
          AND (c.is_identity = 'YES' OR c.column_default LIKE 'nextval%')
    LOOP
        IF coluna.sequence_name IS NULL THEN
            CONTINUE;
        END IF;

        EXECUTE format(
            'SELECT COALESCE(MAX(%I), 0) FROM %I.%I',
            coluna.column_name,
            coluna.table_schema,
            coluna.table_name
        ) INTO maior_id;
        EXECUTE format('SELECT last_value FROM %s', coluna.sequence_name) INTO ultimo_valor;

        IF maior_id > ultimo_valor THEN
            PERFORM setval(coluna.sequence_name::regclass, maior_id, true);
        ELSIF maior_id = 0 AND ultimo_valor = 1 THEN
            PERFORM setval(coluna.sequence_name::regclass, 1, false);
        ELSE
            PERFORM setval(coluna.sequence_name::regclass, ultimo_valor, true);
        END IF;
    END LOOP;
END
$$;
