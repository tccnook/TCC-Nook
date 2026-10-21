# CSS do Nook

- `core.css`: reset → tokens → estilos globais → formulários/utilitários. Entrada das telas de autenticação, sem header global.
- `main.css`: base e componentes/layouts das páginas internas.
- `base/variables.css`: tokens de marca; aliases históricos preservam consumidores existentes.
- `components/`: padrões reutilizáveis.
- `layout/`: navegação, header e footer.
- `pages/`: seletores próprios de cada página, carregados depois da base.
- `utilities/`: auxiliares específicos.

Usar imports relativos. Não importar páginas em `main.css`. Evitar seletores de elementos sem escopo nos arquivos de página. Os arquivos vazios existentes foram preservados para não quebrar referências históricas.
