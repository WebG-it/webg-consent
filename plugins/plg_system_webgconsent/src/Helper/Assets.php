<?php

/**
 * @package     WebG.Consent
 * @copyright   (C) 2026 Marco Galassi / WebG
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace WebG\Plugin\System\Webgconsent\Helper;

\defined('_JEXEC') or die;

/**
 * CSS e JS del modale, tenuti in una classe cosi' finiscono nell'OPcache.
 * Tutto in linea nella pagina: nessuna richiesta aggiuntiva, nessun file da caricare.
 */
final class Assets
{
    public static function css(): string
    {
        return <<<'CSS'
html.wgc-lock,html.wgc-lock body{overflow:hidden!important}
html.wgc-lock #cc--main,html.wgc-lock #cc_div{visibility:hidden!important}
#wgc{position:fixed;inset:0;z-index:2147483600;display:flex;align-items:center;justify-content:center;padding:16px;overflow:auto;overscroll-behavior:contain;-webkit-backdrop-filter:blur(8px);backdrop-filter:blur(8px);background:var(--wgc-ov);font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
#wgc .wgc-p{box-sizing:border-box;width:100%;max-width:520px;margin:auto;padding:28px 24px;border-radius:14px;background:var(--wgc-pn);color:var(--wgc-tx);box-shadow:0 12px 48px rgba(0,0,0,.5);outline:0}
#wgc .wgc-t{margin:0 0 14px;font-size:1.45rem;line-height:1.25;font-weight:700;color:inherit}
#wgc .wgc-b{margin:0 0 12px;color:inherit}
#wgc .wgc-a{display:flex;flex-wrap:wrap;gap:12px;margin:22px 0 4px}
#wgc button{flex:1 1 180px;min-height:48px;padding:12px 18px;border-radius:10px;font:inherit;font-weight:600;cursor:pointer}
#wgc .wgc-y{border:2px solid var(--wgc-ab);background:var(--wgc-ab);color:var(--wgc-at)}
#wgc .wgc-n{border:2px solid var(--wgc-eb);background:transparent;color:var(--wgc-et)}
#wgc button:focus-visible,#wgc a:focus-visible{outline:3px solid var(--wgc-ab);outline-offset:3px}
#wgc .wgc-f{margin:16px 0 0;font-size:.85rem;opacity:.8;color:inherit}
#wgc .wgc-f a{color:inherit;text-decoration:underline}
CSS;
    }

    public static function js(): string
    {
        return <<<'JS'
// WebG Consent: modale di eta' e registro del consenso. Questo blocco e' in linea, leggibile e senza dipendenze.
// Nomi: C configurazione da PHP, d document, h elemento html, Q chiave localStorage per il reinvio, T token, TT ora del token, TP code in attesa.
(function(){
var C=__CFG__,d=document,h=d.documentElement,Q='wgc_q';
// legge il cookie di consenso (wgc_ok)
function gc(){try{var m=d.cookie.match(/(?:^|;\s*)wgc_ok=([^;]*)/);return m?decodeURIComponent(m[1]):''}catch(e){return ''}}
// scrive il cookie tecnico: durata, percorso, SameSite, Secure e dominio opzionale
function sc(v){try{d.cookie='wgc_ok='+encodeURIComponent(v)+'; Max-Age='+(C.days*86400)+'; Path=/; SameSite=Lax'+(C.dom?'; Domain='+C.dom:'')+(location.protocol==='https:'?'; Secure':'')}catch(e){}}
// genera l'ID visitatore casuale a 32 caratteri esadecimali
function rid(){var a=new Uint8Array(16),s='',i;try{(window.crypto||window.msCrypto).getRandomValues(a)}catch(e){for(i=0;i<16;i++){a[i]=Math.floor(Math.random()*256)}}for(i=0;i<16;i++){s+=('0'+a[i].toString(16)).slice(-2)}return s}
var m=gc().match(/^([0-9a-f]{32})\.([A-Za-z0-9_-]{1,32})$/),vid=m?m[1]:'',ok=!!m&&m[2]===C.v,T=null,TT=0,TP=null,i,p=location.pathname;
// chiede un token firmato al server solo quando serve (mai in cache, senza cookie)
function tok(cb){
if(T&&Date.now()-TT<1200000){cb(T);return}
if(TP){TP.push(cb);return}
TP=[cb];
function fl(v){var q=TP;TP=null;for(var k=0;k<q.length;k++){q[k](v)}}
fetch(C.ep+'&task=token',{credentials:'omit',cache:'no-store'}).then(function(r){return r.json()}).then(function(j){T=j.t;TT=Date.now();fl(T)},function(){fl('')});
}
// invia la registrazione: attende almeno 1,1 secondi dal token, poi POST senza credenziali
function post(r,cb){
if(!C.log||!window.fetch||!window.URLSearchParams){cb(false);return}
tok(function(t){
if(!t){cb(false);return}
setTimeout(function(){
var b=new URLSearchParams();b.set('vid',r.vid);b.set('ver',r.ver);b.set('out',r.out);b.set('url',r.url);b.set('t',t);
fetch(C.ep+'&task=log',{method:'POST',body:b,credentials:'omit',keepalive:true}).then(function(x){cb(x.ok)},function(){cb(false)});
},Math.max(0,1100-(Date.now()-TT)));
});
}
// registra l'esito; se l'invio fallisce lo riprova al caricamento successivo (localStorage)
function send(o,fn){
var done=false,r={vid:vid,ver:C.v,out:o,url:p};
function fin(){if(!done){done=true;if(fn){fn()}}}
setTimeout(fin,2500);
try{localStorage.setItem(Q,JSON.stringify(r))}catch(e){}
post(r,function(g){if(g){try{localStorage.removeItem(Q)}catch(e){}}fin()});
}
// reinvio di una registrazione rimasta in sospeso
function retry(){
try{var s=localStorage.getItem(Q);if(!s){return}var r=JSON.parse(s);
if(r&&/^[0-9a-f]{32}$/.test(r.vid)&&(r.out==='accepted'||r.out==='rejected')){post(r,function(g){if(g){try{localStorage.removeItem(Q)}catch(e){}}})}else{localStorage.removeItem(Q)}}catch(e){}
}
// niente modale dentro un iframe (anteprima del customizer o del builder di YOOtheme)
try{if(window.top!==window.self){return}}catch(e){return}
// percorsi esclusi (prefisso)
for(i=0;i<C.ex.length;i++){if(p.indexOf(C.ex[i])===0){return}}
// consenso gia' dato per questa versione del testo: nessun modale
if(ok){if(C.log){setTimeout(retry,3000)}return}
if(!/^[0-9a-f]{32}$/.test(vid)){vid=rid()}
// stato del modale: overlay, pannello, pulsanti, link, elementi resi inerti
var ov,pn,ba,br,lk,inr=[],closed=false;
// crea un elemento con classe e testo (sempre textContent, mai HTML)
function el(t,c,x){var e=d.createElement(t);if(c){e.className=c}if(x){e.textContent=x}return e}
// rende inerte il resto della pagina (solo gli elementi che non lo erano gia')
function inert(){
if(closed){return}
var k=d.body.children,e;
for(var n=0;n<k.length;n++){e=k[n];if(e===ov||e.tagName==='SCRIPT'||e.tagName==='STYLE'||e.hasAttribute('inert')){continue}e.setAttribute('inert','');inr.push(e)}
}
// costruisce il dialogo accessibile e blocca lo scroll solo a costruzione riuscita
function build(){
ov=el('div');ov.id='wgc';ov.setAttribute('data-nosnippet','');
pn=el('div','wgc-p');pn.setAttribute('role','dialog');pn.setAttribute('aria-modal','true');pn.setAttribute('aria-labelledby','wgc-t');pn.setAttribute('aria-describedby','wgc-d');pn.tabIndex=-1;
var t=el('h2','wgc-t',C.t.title),b=el('div');t.id='wgc-t';b.id='wgc-d';
for(var n=0;n<C.t.body.length;n++){b.appendChild(el('p','wgc-b',C.t.body[n]))}
var a=el('div','wgc-a');
ba=el('button','wgc-y',C.t.acc);ba.type='button';
br=el('button','wgc-n',C.t.rej);br.type='button';
a.appendChild(ba);a.appendChild(br);
pn.appendChild(t);pn.appendChild(b);pn.appendChild(a);
if(C.t.note||C.t.pu){
var f=el('p','wgc-f',C.t.note?C.t.note+' ':'');
if(C.t.pu){lk=el('a','',C.t.pl||C.t.pu);lk.href=C.t.pu;lk.target='_blank';lk.rel='noopener';f.appendChild(lk)}
pn.appendChild(f);
}
ov.appendChild(pn);d.body.appendChild(ov);
h.className+=' wgc-lock';
inert();
ba.addEventListener('click',yes);
br.addEventListener('click',no);
d.addEventListener('keydown',kd,true);
d.addEventListener('focusin',fi,true);
['pointermove','touchstart','keydown','focusin'].forEach(function(ev){d.addEventListener(ev,warm,{once:true,passive:true})});
try{ba.focus({preventScroll:true})}catch(e){}
}
// scalda il token alla prima interazione, cosi' i bot che non interagiscono non lo chiedono
function warm(){if(C.log&&window.fetch){tok(function(){})}}
// tastiera: Esc non chiude, Tab resta dentro il dialogo
function kd(e){
if(e.key==='Escape'){e.preventDefault();e.stopPropagation();return}
if(e.key!=='Tab'){return}
var f=[ba,br];if(lk){f.push(lk)}
var x=f.indexOf(d.activeElement);
if(x<0){e.preventDefault();ba.focus();return}
e.preventDefault();
if(e.shiftKey){x=(x+f.length-1)%f.length}else{x=(x+1)%f.length}
f[x].focus();
}
// se il focus esce dal dialogo lo riporta sul pulsante di accettazione
function fi(e){if(ov&&!ov.contains(e.target)){ba.focus()}}
// chiusura pulita: toglie ascoltatori, inert, overlay e blocco scroll
function done(){
closed=true;
d.removeEventListener('keydown',kd,true);d.removeEventListener('focusin',fi,true);
for(var n=0;n<inr.length;n++){inr[n].removeAttribute('inert')}
if(ov&&ov.parentNode){ov.parentNode.removeChild(ov)}
h.className=h.className.replace(/(?:^|\s)wgc-lock(?=\s|$)/g,'');
}
// accetta: cookie, chiusura, evento wgc, registrazione
function yes(){
sc(vid+'.'+C.v);done();
try{d.dispatchEvent(new CustomEvent('webgconsent:accepted',{detail:{version:C.v}}))}catch(e){}
send('accepted');
setTimeout(function(){var c=d.querySelector('#cc--main #c-p-bn');if(c&&c.offsetParent!==null){try{c.focus({preventScroll:true})}catch(e){}}},350);
}
// rifiuta: registra e poi esce verso l'indirizzo configurato
function no(){
ba.disabled=true;br.disabled=true;
send('rejected',function(){location.replace(C.exit)});
}
function safe(){try{build()}catch(e){done()}}
if(d.body){safe();d.addEventListener('DOMContentLoaded',inert);window.addEventListener('load',inert)}else{d.addEventListener('DOMContentLoaded',safe)}
})();
JS;
    }
}
