(function(){
var btn=document.querySelector('.ov-menu'),nav=document.getElementById('ov-nav');
if(btn&&nav){btn.addEventListener('click',function(){var o=nav.classList.toggle('is-open');btn.setAttribute('aria-expanded',o?'true':'false');});}
function fit(){document.querySelectorAll('.ov-frame__fit').forEach(function(f){var w=+f.dataset.w,art=f.firstElementChild;if(art&&w){art.style.transform='scale('+(f.clientWidth/w)+')';}});}
fit();window.addEventListener('resize',fit);if('ResizeObserver' in window){var ro=new ResizeObserver(fit);document.querySelectorAll('.ov-frame__fit').forEach(function(f){ro.observe(f);});}
document.querySelectorAll('.ov-check input[data-key]').forEach(function(c){
  try{var v=localStorage.getItem('ovcheck-'+c.dataset.key);if(v!==null){c.checked=v==='1';}}catch(e){}
  c.addEventListener('change',function(){try{localStorage.setItem('ovcheck-'+c.dataset.key,c.checked?'1':'0');}catch(e){}});
});
})();
