/** Cross-browser smoke. Install Playwright outside this PHP project's dependencies.
 * NOOK_TEST_URL, NOOK_TEST_SESSION, NOOK_TEST_BOOK, NOOK_PLAYWRIGHT_PATH.
 * Writes only when NOOK_TEST_MUTATIONS=1; use a disposable database/account.
 * Reports the real host separately from user-agent/device emulation.
 */
'use strict';
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),assert=require('node:assert/strict');
if(process.argv.includes('--help')){
 console.log('Run: node tests/browser-compat.cjs\nRequired: NOOK_TEST_URL (ending /paginas/), NOOK_TEST_SESSION, NOOK_TEST_BOOK.\nOptional: NOOK_PLAYWRIGHT_PATH, NOOK_TEST_BROWSERS=chromium,firefox,webkit,chrome,msedge; NOOK_TEST_MUTATIONS=1 (disposable test data only); NOOK_TEST_OUTPUT; NOOK_CHROMIUM_EXECUTABLE.\nNo Windows claim is made unless os.platform() is win32.');process.exit(0);
}
const pw=require(process.env.NOOK_PLAYWRIGHT_PATH||'playwright');
const base=process.env.NOOK_TEST_URL,session=process.env.NOOK_TEST_SESSION,book=Number(process.env.NOOK_TEST_BOOK);
assert(base&&session&&Number.isSafeInteger(book)&&book>0,'Set NOOK_TEST_URL, NOOK_TEST_SESSION and NOOK_TEST_BOOK');
assert(base.endsWith('/'),'NOOK_TEST_URL must end in /');
const output=process.env.NOOK_TEST_OUTPUT||path.join(os.tmpdir(),'nook-browser-compat-'+Date.now());fs.mkdirSync(output,{recursive:true});
const mutate=process.env.NOOK_TEST_MUTATIONS==='1';
const requested=(process.env.NOOK_TEST_BROWSERS||(process.platform==='win32'?'chromium,chrome,msedge,firefox':'chromium,firefox,webkit')).split(',');
const winChrome='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36';
const winFirefox='Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0';
const cases=[];
for(const engine of requested){
 cases.push({name:engine+'-native-desktop',engine,viewport:{width:1366,height:768}});
 if(['chromium','chrome','msedge'].includes(engine)){
  cases.push({name:engine+'-windows-ua-hidpi',engine,viewport:{width:1536,height:864},deviceScaleFactor:1.25,userAgent:winChrome});
  cases.push({name:engine+'-windows-ua-narrow',engine,viewport:{width:683,height:600},userAgent:winChrome,reducedMotion:'reduce',forcedColors:'active'});
  cases.push({name:engine+'-android-emulated',engine,viewport:{width:390,height:844},isMobile:true,hasTouch:true,deviceScaleFactor:3,userAgent:'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36'});
 }
 if(engine==='firefox')cases.push({name:'firefox-windows-ua',engine,viewport:{width:1366,height:768},userAgent:winFirefox});
 if(engine==='webkit')cases.push({name:'webkit-iphone-emulated',engine,viewport:{width:390,height:844},isMobile:true,hasTouch:true,deviceScaleFactor:3,userAgent:'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'});
}
const report={startedAt:new Date().toISOString(),host:{platform:os.platform(),release:os.release(),arch:os.arch()},windowsNative:os.platform()==='win32',mutations:mutate,cases:[]};
async function main(){
 for(const config of cases){
  const {name,engine,...options}=config;const result={name,engine,emulation:!!options.userAgent,checks:[],pageErrors:[],httpErrors:[],status:'failed'};report.cases.push(result);let browser,context,page;
  const check=async(label,fn)=>{await fn();result.checks.push(label);console.log(' CHECK',name,label);};
  try{
   const type=['chrome','msedge'].includes(engine)?'chromium':engine;
   const launch={headless:true};if(['chrome','msedge'].includes(engine))launch.channel=engine;
   if(engine==='chromium'&&process.env.NOOK_CHROMIUM_EXECUTABLE)launch.executablePath=process.env.NOOK_CHROMIUM_EXECUTABLE;
   browser=await pw[type].launch(launch);result.version=browser.version();
   context=await browser.newContext({...options,locale:'pt-BR',acceptDownloads:true});
   page=await context.newPage();page.setDefaultTimeout(12000);
   page.on('pageerror',error=>result.pageErrors.push(error.message));
   page.on('response',response=>{if(new URL(response.url()).origin===new URL(base).origin&&response.status()>=400)result.httpErrors.push({status:response.status(),path:new URL(response.url()).pathname});});
   async function navigate(route){const r=await page.goto(base+route,{waitUntil:'domcontentloaded',timeout:30000});assert.equal(r.status(),200,route);await page.waitForLoadState('networkidle',{timeout:2500}).catch(()=>{});assert(!/Fatal error:|Warning:|Notice:/.test(await page.locator('body').innerText()),'PHP error on '+route);}
   async function noOverflow(){const dims=await page.evaluate(()=>({width:innerWidth,document:document.documentElement.scrollWidth}));assert(dims.document<=dims.width+1,JSON.stringify(dims));}
   await check('login anônimo e formulários',async()=>{await navigate('login.php');await page.locator('#email').fill('leitura@example.invalid');assert.equal(await page.locator('#email').inputValue(),'leitura@example.invalid');});
   await context.clearCookies({name:'PHPSESSID'});await context.addCookies([{name:'PHPSESSID',value:session,domain:new URL(base).hostname,path:'/',httpOnly:true,sameSite:'Lax',secure:new URL(base).protocol==='https:'}]);
   for(const route of ['paginaprincipal.php','catalogo.php','perfil.php','minhas_obras.php'])await check('navegação '+route,async()=>{await navigate(route);assert(!page.url().endsWith('/login.php'),'Lost authenticated session');await noOverflow();});
   await check('API autenticada de worldbuilding',async()=>{const r=await context.request.get(base+'worldbuilding_api.php?id_livro='+book);assert.equal(r.status(),200);assert((await r.json()).mundo.personagem.length>=1,'Fixture must contain a character');});
   await check('personagens: busca, grade/lista e detalhes',async()=>{
    await navigate('worldbuilding.php?id_livro='+book+'&secao=personagem');await page.locator('[data-cards] .world-card-open').first().waitFor();await noOverflow();
    await page.locator('[data-search]').fill('zzzznenhumresultado');assert.equal(await page.locator('[data-cards] .world-card').count(),0);
    await page.locator('[data-search]').fill('');await page.locator('[data-view="list"]').click();await page.locator('[data-cards].world-list').waitFor();await page.locator('[data-view="grid"]').click();
    await page.locator('[data-cards] .world-card-open').first().click();await page.locator('[data-detail]:not([hidden])').waitFor();await noOverflow();await page.locator('[data-close-detail]').click();
   });
   await check('dialog: foco, Escape, prévia e upload Unicode',async()=>{
    await page.locator('[data-create]').click();await page.locator('[data-world-dialog][open]').waitFor();await page.locator('#world-field-nome').fill('Personagem de compatibilidade');
    await page.locator('#world-field-descricao').fill('Descrição com acentos: ação, coração, histórias.');await page.locator('[data-live-preview]').getByRole('heading',{name:'Personagem de compatibilidade'}).waitFor();
    await page.locator('#world-image').setInputFiles({name:'Retrato João – Windows.PNG',mimeType:'image/png',buffer:Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jK1sAAAAASUVORK5CYII=','base64')});
    await page.locator('[data-live-preview] img').scrollIntoViewIfNeeded();await page.waitForFunction(()=>document.querySelector('[data-live-preview] img')?.naturalWidth===1);
    await page.screenshot({path:path.join(output,name+'-form.png')});await page.keyboard.press('Escape');assert.equal(await page.locator('[data-world-dialog]').evaluate(e=>e.open),false);assert.equal(await page.locator('[data-create]').evaluate(e=>e===document.activeElement),true);
   });
   if(mutate)await check('criar ficha, recarregar, editar e excluir',async()=>{
    const record='Compatibilidade '+name+' '+Date.now();await page.locator('[data-create]').click();await page.locator('#world-field-nome').fill(record);
    await page.locator('#world-field-descricao').fill('Criação pelo navegador em revisão isolada.');
    await page.locator('#world-image').setInputFiles({name:'Capa ação.PNG',mimeType:'image/png',buffer:Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jK1sAAAAASUVORK5CYII=','base64')});
    const saved=page.waitForResponse(r=>r.url().includes('worldbuilding_api.php')&&r.request().method()==='POST');await page.locator('[data-submit-ficha]').click();assert.equal((await saved).status(),200);
    await page.locator('[data-world-dialog]').waitFor({state:'hidden'});await page.reload({waitUntil:'domcontentloaded'});await page.getByRole('button',{name:'Ver '+record,exact:true}).click();
    await page.locator('[data-edit]').click();await page.locator('#world-field-nome').fill(record+' editado');const edited=page.waitForResponse(r=>r.url().includes('worldbuilding_api.php')&&r.request().method()==='POST');await page.locator('[data-submit-ficha]').click();assert.equal((await edited).status(),200);await page.locator('[data-world-dialog]').waitFor({state:'hidden'});
    page.once('dialog',d=>d.accept());const deleted=page.waitForResponse(r=>r.url().includes('worldbuilding_api.php')&&r.request().method()==='POST');await page.locator('[data-delete]').click();assert.equal((await deleted).status(),200);
   });
   await check('template centralizado e campos editáveis',async()=>{
    await navigate('worldbuilding.php?id_livro='+book+'&secao=template');await page.locator('[data-create]').click();await page.locator('#world-field-nome').fill('Modelo de compatibilidade');
    await page.locator('[data-template-fields] input').first().fill('Motivação');await page.locator('[data-add-field]').click();assert.equal(await page.locator('[data-template-fields]>div').count(),2);
    const r=await page.locator('[data-world-dialog]').boundingBox();assert(Math.abs(r.x+r.width/2-options.viewport.width/2)<2,'Dialog off center');await page.keyboard.press('Escape');
   });
   await check('marcos: ano zero e ordenação numérica',async()=>{await navigate('worldbuilding.php?id_livro='+book+'&secao=marco');assert((await page.locator('[data-create]').innerText()).includes('Adicionar marco temporal'));const years=await page.locator('[data-cards] .world-year').allTextContents();assert(years.some(s=>/Ano 0\b/.test(s)));await noOverflow();});
   await check('compositor: Unicode, formatação, imagens e foco',async()=>{
    const opener=options.viewport.width<=720?'#mobileCriarPost':'#btnAbrirCriarPost';await page.locator(opener).click();await page.locator('#conteudoPostGlobal').fill('Leitura no Windows: **ação e coração**.\n\nUma história para compartilhar.');
    await page.locator('#postPreviewText strong').getByText('ação e coração',{exact:true}).waitFor();
    const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jK1sAAAAASUVORK5CYII=','base64');
    await page.locator('#fotoPostGlobal').setInputFiles([{name:'Leitura João.PNG',mimeType:'image/png',buffer:png},{name:'Segunda imagem.PNG',mimeType:'image/png',buffer:png}]);
    assert.equal(await page.locator('#postImageThumbnails button').count(),2);assert.match(await page.locator('#postImageCount').innerText(),/2\/10/);await page.locator('#postImageThumbnails button').nth(1).click();assert.match(await page.locator('#postPreviewImagePosition').innerText(),/2 de 2/);
    await page.locator('#postAudienceGlobal').selectOption('privado');assert.equal(await page.locator('#postPreviewAudience').innerText(),'Somente eu');await page.screenshot({path:path.join(output,name+'-composer.png')});
    if(mutate){const sent=page.waitForResponse(r=>r.url().includes('criar-post.php')&&r.request().method()==='POST');await page.locator('#modalCriarPost button[type=submit]').click();const response=await sent;assert.equal(response.status(),201,await response.text());}
    await page.locator('#modalCriarPost').evaluate(d=>{if(d.open)d.close();});
   });
   await check('editor: teclado, formatação e salvamento',async()=>{
    await navigate('editar_obra.php?id_livro='+book+'&modo=escrever');await page.locator('[data-content]').waitFor();await noOverflow();
    if(mutate){await page.locator('[data-content]').fill('História de compatibilidade — ação, coração e criação.');await page.locator('[data-content]').press('ControlOrMeta+a');await page.locator('[data-command=removeFormat]').click();await page.locator('[data-content]').press('ControlOrMeta+a');await page.locator('[data-command=bold]').click();assert(await page.locator('[data-content]').evaluate(e=>!!e.querySelector('b,strong')),'Bold did not change the editor');
     const saved=page.waitForResponse(r=>r.url().includes('obra_api.php')&&r.request().method()==='POST');await page.keyboard.press(process.platform==='darwin'?'Meta+s':'Control+s');assert.equal((await saved).status(),200);await page.reload({waitUntil:'domcontentloaded'});assert((await page.locator('[data-content]').innerText()).includes('História de compatibilidade'));
    }
    await page.locator('[data-expand-editor]').click();assert.equal(await page.locator('.world-sidebar').isVisible(),false);await page.locator('[data-expand-editor]').click();
   });
   if(mutate)await check('leitor: imagem da página, sumário e zoom',async()=>{
    await navigate('leitor_obra.php?id_livro='+book);await page.waitForFunction(()=>document.querySelector('[data-reader-image]')?.naturalWidth>0,{timeout:15000});
    await page.locator('[data-open-summary]').click();await page.locator('[data-summary-dialog][open]').waitFor();await page.keyboard.press('Escape');await page.locator('[data-zoom-in]').click();await noOverflow();
   });
   result.capabilities=await page.evaluate(()=>({dialog:typeof HTMLDialogElement!=='undefined'&&typeof HTMLDialogElement.prototype.showModal==='function',inert:'inert' in HTMLElement.prototype,has:CSS.supports('selector(:has(*))'),dvh:CSS.supports('height','100dvh'),navigatorPlatform:navigator.platform}));
   assert.deepEqual(result.pageErrors,[],'JavaScript page errors');assert.deepEqual(result.httpErrors,[],'Local HTTP errors');result.status='passed';
  }catch(error){if(!browser)result.status='unavailable';result.error=error.message;if(page)await page.screenshot({path:path.join(output,name+'-failure.png')}).catch(()=>{});}
  finally{await context?.close();await browser?.close();fs.writeFileSync(path.join(output,'report.json'),JSON.stringify(report,null,2));}
  console.log(result.status.toUpperCase(),name,result.checks.length,'checks',result.error||'');
 }
 report.finishedAt=new Date().toISOString();fs.writeFileSync(path.join(output,'report.json'),JSON.stringify(report,null,2));console.log('Report:',path.join(output,'report.json'));if(report.cases.some(r=>r.status==='failed'))process.exitCode=1;else if(report.cases.some(r=>r.status==='unavailable'))process.exitCode=2;
}
main().catch(error=>{console.error(error.message);process.exitCode=1;});
