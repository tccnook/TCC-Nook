import urllib.request,re
req=urllib.request.Request('http://127.0.0.1:8097/TCC-Nook/paginas/visao_livro.php?id_livro=2',headers={'Cookie':'PHPSESSID=nookvalidation14'})
with urllib.request.urlopen(req) as r:
 body=re.sub('<[^>]+>','',r.read().decode())
 assert not re.search(r'(Warning|Fatal error|Notice):',body),'Visão do livro apresenta erro PHP'
 assert 'Voltar ao catálogo' in body
print('PASS: visão do livro local sem erros PHP')
