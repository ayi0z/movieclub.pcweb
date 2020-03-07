function keyevent (event) {
  let e = window.event || event;
  if(e.keyCode==13){
    query();
  }
}
function query (qstr) {
  let query = qstr || document.getElementById('txt_query').value;
  query = query.trim();
  if(query==''){
    window.location.href = "/";
  }else{
    window.location.href='/q/'+encodeURI(query)+"/";
  }
}
(function(){
  let sort_url = window.location.href.substr(window.location.href.length-1,1);
  let nav_sort = document.querySelector('.nav-sort a[href="' + sort_url + '"]');
  if(nav_sort) {
    nav_sort.style.color="#bcf9d9";
    nav_sort.style.setProperty("text-shadow","0 0 1rem #46ff87");
  }
})();