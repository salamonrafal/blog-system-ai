(function(){
  var body = document.body;

  if(!(body instanceof HTMLBodyElement) || body.dataset.adminPage !== 'true'){
    return;
  }

  var released = false;
  var release = function(){
    if(released){
      return;
    }

    released = true;
    body.classList.remove('admin-preload-pending');
  };

  body.classList.add('admin-preload-pending');
  window.addEventListener('load', release, { once: true });
  window.setTimeout(release, 5000);
})();
