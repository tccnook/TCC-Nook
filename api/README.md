# Google Books

1. Instale as dependências na raiz do projeto com `composer install`.
2. Crie um projeto no Google Cloud Console, habilite a Google Books API e gere uma chave de API.
3. Copie `.env.example` para `.env` na raiz do projeto e informe a chave em `GOOGLE_BOOKS_API_KEY`.
4. Restrinja a chave à Google Books API. Em produção, aplique também a restrição de IP do servidor.

O arquivo `.env` não deve ser versionado. A busca local continua funcionando se a API externa estiver indisponível; uma chave configurada é recomendada para evitar limites de requisição.
