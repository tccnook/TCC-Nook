"""Smoke read-only: NOOK_TEST_URL e NOOK_TEST_SESSION apontam para servidor/sessão local."""
import os, urllib.request, urllib.error, re
base = os.environ.get('NOOK_TEST_URL', 'http://127.0.0.1:8097/TCC-Nook/paginas/')
session = os.environ.get('NOOK_TEST_SESSION', 'nookvalidation14')
owner = os.environ.get('NOOK_TEST_OWNER', '14')
for page in ['login.php', 'cadastro.php?tipo=normal', 'paginaprincipal.php', 'catalogo.php', 'perfil.php', 'preferencia_gen.php', 'perfil_user_terceiro.php?id_user='+owner]:
    req = urllib.request.Request(base+page, headers={'Cookie': 'PHPSESSID='+session})
    with urllib.request.urlopen(req) as response:
        body = re.sub('<[^>]+>', '', response.read().decode())
        assert not re.search(r'(Fatal error|Warning|Notice):', body), page+' apresentou erro PHP'
        assert response.status == 200, page
for page, expected in [('perfil_user_terceiro.php?id_user=abc',400), ('buscar-livros.php',401)]:
    try:
        urllib.request.urlopen(base+page)
        raise AssertionError(page+' deveria recusar acesso/entrada')
    except urllib.error.HTTPError as error:
        assert error.code == expected, (page,error.code)
print('PASS: páginas sem erros PHP, ID inválido 400, API sem login 401')
