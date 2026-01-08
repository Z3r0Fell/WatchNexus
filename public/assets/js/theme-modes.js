
(function(){
  console.log('UI Modes loading...');
  
  fetch('/assets/themes/modes.json')
    .then(r => r.json())
    .then(modes => {
      console.log('UI Modes loaded:', Object.keys(modes));
      
      // Set mode function
      window.setUIMode = function(k){
        if(!modes[k]) {
          console.warn('Unknown mode:', k);
          return;
        }
        
        // Remove all mode classes
        document.body.classList.forEach(c=>{
          if(c.startsWith('mode-')) document.body.classList.remove(c);
        });
        
        // Add new mode class
        document.body.classList.add(modes[k].bodyClass);
        localStorage.setItem('wnx_ui_mode', k);
        console.log('UI Mode set to:', k, modes[k].bodyClass);
      };
      
      // Load saved mode or default to command
      const saved = localStorage.getItem('wnx_ui_mode') || 'command';
      if(modes[saved]) {
        document.body.classList.add(modes[saved].bodyClass);
        console.log('Applied saved mode:', saved);
      }
      
      // Integrate with selector
      const selector = document.getElementById('uiModeSelect');
      if (selector) {
        // Set selector to saved mode
        selector.value = saved;
        
        // Listen for changes
        selector.addEventListener('change', function() {
          const newMode = this.value;
          console.log('Mode changed to:', newMode);
          window.setUIMode(newMode);
        });
        
        console.log('UI Mode selector integrated');
      }
    })
    .catch(err => {
      console.error('Failed to load UI modes:', err);
    });
})();
