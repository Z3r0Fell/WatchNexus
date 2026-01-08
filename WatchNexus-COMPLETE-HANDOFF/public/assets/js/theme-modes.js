
(function(){
  fetch('/assets/themes/modes.json')
    .then(r => r.json())
    .then(modes => {
      window.setUIMode = function(k){
        if(!modes[k]) return;
        document.body.classList.forEach(c=>{
          if(c.startsWith('mode-')) document.body.classList.remove(c);
        });
        document.body.classList.add(modes[k].bodyClass);
        localStorage.setItem('wnx_ui_mode', k);
      };
      const saved = localStorage.getItem('wnx_ui_mode') || 'command';
      if(modes[saved]) document.body.classList.add(modes[saved].bodyClass);
    });
})();
