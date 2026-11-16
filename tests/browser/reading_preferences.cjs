const {chromium}=require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const fs=require('fs'), assert=require('assert'), path=require('path'), os=require('os');
const root=path.resolve(__dirname,'../..');
const output=fs.mkdtempSync(path.join(os.tmpdir(),'nook-reader-test-'));
require('child_process').execFileSync(process.env.PHP_BINARY || 'php', [path.join(__dirname,'render_preferences_reader.php'),output]);
(async()=>{
 const browser=await chromium.launch({headless:true}); const page=await browser.newPage({viewport:{width:1440,height:900}});
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 const saves=[];const imageRequests=[];let failAuthors=false;
 await page.route('**/*',async route=>{
  const url=new URL(route.request().url());let path=url.pathname;
  if(url.hostname!=='nook.test')return route.abort();
  if(path.endsWith('obra_api.php')){saves.push(route.request().postDataJSON());return route.fulfill({json:{mensagem:'Progresso salvo.'}});}
  if(path.endsWith('pagina_obra.php')){imageRequests.push(Number(url.searchParams.get('pagina')));return route.fulfill({contentType:'image/svg+xml',body:`<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="1414"><rect width="1000" height="1414" fill="white"/><text x="120" y="200" font-size="50">Página ${url.searchParams.get('pagina')}</text></svg>`});}
  if(path.endsWith('buscar-autores-preferencia.php')){
   if(failAuthors){failAuthors=false;return route.fulfill({status:503,json:{mensagem:'Falha temporária. Tente novamente.'}});}
   if(url.searchParams.has('autor'))return route.fulfill({json:{autores:[]}});
   const n=Number(url.searchParams.get('pagina')||1); const q=url.searchParams.get('q');
   const ids=q?[9]:n===1?[1,2]:[2,3];
   return route.fulfill({json:{mais:!q&&n===1,autores:ids.map(id=>({id:'OL'+id+'A',nome:'Autor '+id,assuntos:['Romance'],token:'test'+id}))}});
  }
  if(path.endsWith('leitor_obra.php')||path.endsWith('preferencia_autor.php'))return route.fulfill({contentType:'text/html',body:fs.readFileSync(output+'/'+path.split('/').pop().replace('.php','.html'),'utf8')});
  const file=root+path;
  if(fs.existsSync(file)&&fs.statSync(file).isFile())return route.fulfill({path:file});
  return route.fulfill({status:404,body:''});
 });
 await page.goto('http://nook.test/paginas/leitor_obra.php');
 await page.waitForFunction(()=>document.querySelector('[data-reader-status]').textContent==='Progresso salvo.');
 assert.equal(await page.locator('.reader-footer').count(),0);
 await page.selectOption('select[data-reading-mode]','continuous');
 await page.waitForFunction(()=>document.querySelectorAll('[data-reader-continuous] img').length===142);
 assert(imageRequests.length<20,'Lazy loading should not request whole book');
 await page.locator('[data-page]').fill('50'); await page.locator('[data-page]').dispatchEvent('change');
 await page.waitForFunction(()=>document.querySelector('[data-page]').value==='50'&&document.querySelector('[data-reader-continuous] img[data-reader-page="50"]').dataset.ready==='true');
 await page.waitForTimeout(650);assert(saves.some(s=>s.pagina===50));
 await page.locator('.reader-stage').evaluate(el=>el.scrollTop+=1000);
 await page.waitForTimeout(650);
 assert(Number(await page.locator('input[data-page]').inputValue())>50,'Scrolling updates current page');
 await page.locator('input[data-page]').fill('50'); await page.locator('input[data-page]').dispatchEvent('change');
 await page.selectOption('select[data-reading-mode]','paged');
 assert((await page.locator('[data-reader-image]').getAttribute('src')).includes('pagina=50'));
 await page.locator('.reader-more summary').click();assert(await page.locator('[data-finish]').isVisible());await page.locator('.reader-more summary').click();
 await page.selectOption('select[data-reading-mode]','continuous');
 await page.screenshot({path:output+'/reader-desktop.png'});
 await page.setViewportSize({width:390,height:844});
 await page.waitForTimeout(600);
 assert.equal(await page.locator('input[data-page]').inputValue(),'50','Resize should retain current page');
 assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Mobile horizontal overflow');
 await page.selectOption('select[data-theme]','sepia');
 await page.screenshot({path:output+'/reader-mobile.png'});
 await page.goto('http://nook.test/paginas/preferencia_autor.php');
 await page.waitForSelector('.aut-card');assert.equal(await page.locator('.aut-card').count(),2);
 await page.locator('.aut-checkbox').first().check({force:true});
 failAuthors=true;await page.locator('#load-more-authors').click();
 await page.waitForFunction(()=>document.querySelector('#author-status').textContent.includes('Falha temporária'));
 assert(await page.locator('#load-more-authors').isEnabled());
 await page.locator('#load-more-authors').click();
 await page.waitForFunction(()=>document.querySelectorAll('.aut-card').length===3);
 assert.equal(await page.locator('.aut-checkbox:checked').count(),1);
 assert(await page.locator('#load-more-authors').isDisabled());
 await page.locator('#author-search').fill('novo');
 await page.waitForFunction(()=>document.querySelector('.aut-card[data-id="OL9A"]'));
 assert.equal(await page.locator('.aut-checkbox:checked').count(),1);
 await page.screenshot({path:output+'/authors-mobile.png'});
 assert.deepEqual(errors,[]);console.log('PASS: reader modes, lazy loading, page preservation, saved progress, completion menu, mobile overflow; authors pagination, deduplication, retry, retained selection and search.');
 console.log('Screenshots:', output);
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
